const express = require('express');
const mysql = require('mysql2/promise'); 
require('dotenv').config();

const app = express();
const PORT = process.env.PORT || 3000;

const mysqlConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '12345',
    database: process.env.DB_NAME || 'library_db',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

let mysqlPool = null;

async function connectToMySQL() {
    try {
        mysqlPool = mysql.createPool(mysqlConfig);
        
        const connection = await mysqlPool.getConnection();
        console.log('✅ Подключение к MySQL успешно');
        
        const [tables] = await connection.query('SHOW TABLES');
        console.log(`📊 Найдено таблиц: ${tables.length}`);
        
        const [books] = await connection.query('SELECT COUNT(*) as count FROM physical_books');
        console.log(`📚 Книг в базе: ${books[0].count}`);
        
        connection.release();
        return true;
    } catch (error) {
        console.error('❌ Ошибка подключения к MySQL:', error.message);
        return false;
    }
}

app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    if (req.method === 'OPTIONS') {
        return res.status(200).end();
    }
    
    next();
});

app.use(express.json());
app.use((req, res, next) => {
    console.log(`${new Date().toISOString()} ${req.method} ${req.url}`);
    if (req.body && Object.keys(req.body).length > 0) {
        console.log('📦 Body:', req.body);
    }
    next();
});

class MySQLClient {
    constructor(pool) {
        this.pool = pool;
        this.isConnected = false;
    }

    async getAllBooks() {
        try {
            const [rows] = await this.pool.query(`
                SELECT 
                    pb.id,
                    pb.inventory_number,
                    pb.title,
                    pb.author,
                    pb.year,
                    pb.location,
                    pb.status,
                    pb.created_at,
                    pb.updated_at,
                    (SELECT COUNT(*) FROM physical_loans pl 
                     WHERE pl.book_id = pb.id AND pl.date_returned IS NULL) as active_loans
                FROM physical_books pb
                ORDER BY pb.title
            `);
            return rows;
        } catch (error) {
            console.error('Ошибка получения книг из MySQL:', error);
            throw error;
        }
    }

    async searchBooks(query) {
        try {
            const searchTerm = `%${query}%`;
            const [rows] = await this.pool.query(`
                SELECT * FROM physical_books 
                WHERE title LIKE ? OR author LIKE ? OR inventory_number LIKE ?
                ORDER BY title
            `, [searchTerm, searchTerm, searchTerm]);
            return rows;
        } catch (error) {
            console.error('Ошибка поиска книг:', error);
            throw error;
        }
    }

    async getBookByInventory(inventoryNumber) {
        try {
            const [rows] = await this.pool.query(
                'SELECT * FROM physical_books WHERE inventory_number = ?',
                [inventoryNumber]
            );
            return rows[0] || null;
        } catch (error) {
            console.error('Ошибка получения книги:', error);
            throw error;
        }
    }

    async borrowBook(inventoryNumber, readerId) {
        const connection = await this.pool.getConnection();
        
        try {
            await connection.beginTransaction();

            const [books] = await connection.query(
                'SELECT id, title, status FROM physical_books WHERE inventory_number = ? FOR UPDATE',
                [inventoryNumber]
            );

            if (books.length === 0) {
                throw new Error(`Книга с инвентарным номером ${inventoryNumber} не найдена`);
            }

            const book = books[0];

            if (book.status !== 'available') {
                throw new Error(`Книга "${book.title}" уже выдана`);
            }

            const dateTaken = new Date().toISOString().split('T')[0];
            const [result] = await connection.query(
                'INSERT INTO physical_loans (book_id, reader_card, date_taken) VALUES (?, ?, ?)',
                [book.id, readerId, dateTaken]
            );

            await connection.query(
                'UPDATE physical_books SET status = "borrowed", updated_at = NOW() WHERE id = ?',
                [book.id]
            );

            await connection.commit();
            
            return {
                success: true,
                message: `Книга "${book.title}" выдана читателю ${readerId}`,
                loan_id: result.insertId,
                inventory_number: inventoryNumber,
                date_taken: dateTaken
            };

        } catch (error) {
            await connection.rollback();
            console.error('Ошибка выдачи книги:', error);
            return {
                success: false,
                message: error.message
            };
        } finally {
            connection.release();
        }
    }

    async returnBook(inventoryNumber, loanId = '') {
        const connection = await this.pool.getConnection();
        
        try {
            await connection.beginTransaction();

            const [books] = await connection.query(
                'SELECT id, title, status FROM physical_books WHERE inventory_number = ? FOR UPDATE',
                [inventoryNumber]
            );

            if (books.length === 0) {
                throw new Error(`Книга с инвентарным номером ${inventoryNumber} не найдена`);
            }

            const book = books[0];

            if (book.status !== 'borrowed') {
                throw new Error(`Книга "${book.title}" не была выдана`);
            }

            let loanCondition = 'book_id = ? AND date_returned IS NULL';
            const params = [book.id];
            
            if (loanId) {
                loanCondition = 'id = ?';
                params[0] = loanId;
            }

            const [loans] = await connection.query(
                `SELECT id FROM physical_loans WHERE ${loanCondition}`,
                params
            );

            if (loans.length === 0) {
                throw new Error('Активная выдача не найдена');
            }

            const loan = loans[0];

            const dateReturned = new Date().toISOString().split('T')[0];
            await connection.query(
                'UPDATE physical_loans SET date_returned = ? WHERE id = ?',
                [dateReturned, loan.id]
            );

            await connection.query(
                'UPDATE physical_books SET status = "available", updated_at = NOW() WHERE id = ?',
                [book.id]
            );

            await connection.commit();
            
            return {
                success: true,
                message: `Книга "${book.title}" возвращена`,
                inventory_number: inventoryNumber,
                loan_id: loan.id,
                date_returned: dateReturned
            };

        } catch (error) {
            await connection.rollback();
            console.error('Ошибка возврата книги:', error);
            return {
                success: false,
                message: error.message
            };
        } finally {
            connection.release();
        }
    }

    async getStats() {
        try {
            const [totalBooks] = await this.pool.query('SELECT COUNT(*) as count FROM physical_books');
            const [availableBooks] = await this.pool.query('SELECT COUNT(*) as count FROM physical_books WHERE status = "available"');
            const [borrowedBooks] = await this.pool.query('SELECT COUNT(*) as count FROM physical_books WHERE status = "borrowed"');
            const [totalLoans] = await this.pool.query('SELECT COUNT(*) as count FROM physical_loans');
            const [activeLoans] = await this.pool.query('SELECT COUNT(*) as count FROM physical_loans WHERE date_returned IS NULL');
            
            return {
                total_books: totalBooks[0].count,
                available: availableBooks[0].count,
                borrowed: borrowedBooks[0].count,
                lost: totalBooks[0].count - availableBooks[0].count - borrowedBooks[0].count,
                total_loans: totalLoans[0].count,
                active_loans: activeLoans[0].count
            };
        } catch (error) {
            console.error('Ошибка получения статистики:', error);
            throw error;
        }
    }

    async getPopularBooks(limit = 5) {
        try {
            const [rows] = await this.pool.query(`
                SELECT 
                    pb.inventory_number,
                    pb.title,
                    pb.author,
                    COUNT(pl.id) as borrow_count
                FROM physical_loans pl
                JOIN physical_books pb ON pl.book_id = pb.id
                GROUP BY pb.id
                ORDER BY borrow_count DESC
                LIMIT ?
            `, [limit]);
            return rows;
        } catch (error) {
            console.error('Ошибка получения популярных книг:', error);
            throw error;
        }
    }
}

let mysqlClient = null;

async function initialize() {
    console.log('🚀 Инициализация приложения...');
    
    const mysqlConnected = await connectToMySQL();
    
    if (mysqlConnected) {
        mysqlClient = new MySQLClient(mysqlPool);
        console.log('✅ MySQL клиент готов к работе');
        
        try {
            const stats = await mysqlClient.getStats();
            console.log('📊 Статистика базы данных:', stats);
        } catch (error) {
            console.error('❌ Ошибка тестового запроса:', error.message);
        }
    } else {
        console.log('⚠️ MySQL недоступен, работаем в ограниченном режиме');
    }
}

const fallbackBooks = [
    {
        inventory_number: "BLUM-001",
        title: "Мастер и Маргарита",
        author: "Михаил Булгаков",
        year: 1966,
        location: "Сектор А, полка 3",
        status: "available"
    },
    {
        inventory_number: "BLUM-002",
        title: "Преступление и наказание", 
        author: "Фёдор Достоевский",
        year: 1866,
        location: "Сектор Б, полка 1",
        status: "borrowed"
    },
    {
        inventory_number: "CHRIS-001",
        title: "Убийство в «Восточном экспрессе»",
        author: "Агата Кристи",
        year: 1934,
        location: "Сектор Г, полка 4",
        status: "available"
    }
];


app.get('/', (req, res) => {
    res.json({
        service: "Библиотечная система - REST API",
        version: "1.0",
        description: "API для управления библиотекой с прямым подключением к MySQL",
        mysql_status: mysqlClient ? "connected" : "disconnected",
        endpoints: {
            getBooks: "GET /api/physical/books",
            searchBooks: "GET /api/physical/books/search?q=...",
            getBook: "GET /api/physical/books/:inventory",
            borrowBook: "POST /api/physical/loan",
            returnBook: "POST /api/physical/return",
            stats: "GET /api/stats",
            popular: "GET /api/popular-books",
            health: "GET /api/health"
        }
    });
});

app.get('/api/physical/books', async (req, res) => {
    console.log('📚 Запрос всех книг из MySQL');
    
    if (!mysqlClient) {
        console.log('🔄 MySQL недоступен, используем fallback');
        return res.json({
            success: true,
            source: 'Локальная база (fallback)',
            count: fallbackBooks.length,
            data: fallbackBooks
        });
    }
    
    try {
        const books = await mysqlClient.getAllBooks();
        
        res.json({
            success: true,
            source: 'MySQL напрямую',
            count: books.length,
            data: books
        });
        
    } catch (error) {
        console.error('❌ Ошибка получения книг:', error.message);
        res.status(500).json({
            success: false,
            error: 'Ошибка получения данных из базы'
        });
    }
});

app.get('/api/physical/books/search', async (req, res) => {
    const query = req.query.q || '';
    console.log(`🔍 Поиск книг: "${query}"`);
    
    if (!query.trim()) {
        return res.json({
            success: true,
            count: 0,
            data: []
        });
    }
    
    if (!mysqlClient) {
        // Fallback поиск
        const filtered = fallbackBooks.filter(book => 
            book.title.toLowerCase().includes(query.toLowerCase()) ||
            book.author.toLowerCase().includes(query.toLowerCase())
        );
        
        return res.json({
            success: true,
            source: 'Локальная база (fallback)',
            count: filtered.length,
            data: filtered
        });
    }
    
    try {
        const books = await mysqlClient.searchBooks(query);
        
        res.json({
            success: true,
            source: 'MySQL напрямую',
            count: books.length,
            data: books
        });
        
    } catch (error) {
        console.error('Ошибка поиска книг:', error.message);
        res.status(500).json({
            success: false,
            error: 'Ошибка поиска в базе данных'
        });
    }
});

app.get('/api/physical/books/:inventory', async (req, res) => {
    const inventoryNumber = req.params.inventory;
    console.log(`🔎 Получение книги: ${inventoryNumber}`);
    
    if (!mysqlClient) {
        const book = fallbackBooks.find(b => b.inventory_number === inventoryNumber);
        
        if (book) {
            return res.json({
                success: true,
                source: 'Локальная база (fallback)',
                data: book
            });
        } else {
            return res.status(404).json({
                success: false,
                message: `Книга с инвентарным номером ${inventoryNumber} не найдена`
            });
        }
    }
    
    try {
        const book = await mysqlClient.getBookByInventory(inventoryNumber);
        
        if (book) {
            res.json({
                success: true,
                source: 'MySQL напрямую',
                data: book
            });
        } else {
            res.status(404).json({
                success: false,
                message: `Книга с инвентарным номером ${inventoryNumber} не найдена`
            });
        }
        
    } catch (error) {
        console.error('Ошибка получения книги:', error.message);
        res.status(500).json({
            success: false,
            error: 'Ошибка получения данных из базы'
        });
    }
});

app.get('/api/physical/loan', (req, res) => {
    res.status(405).json({
        success: false,
        error: "Method Not Allowed",
        message: "Используйте POST метод для выдачи книги",
        example: {
            inventory_number: "LIB-2023-001",
            reader_id: "R-12345"
        }
    });
});

app.post('/api/physical/loan', async (req, res) => {
    const { inventory_number, reader_id } = req.body;
    
    console.log(`📖 Выдача книги: ${inventory_number}, читатель: ${reader_id}`);
    
    if (!inventory_number || !reader_id) {
        return res.status(400).json({
            success: false,
            message: "Требуются inventory_number и reader_id"
        });
    }
    
    if (!mysqlClient) {
        return res.status(503).json({
            success: false,
            source: 'Node.js (offline)',
            message: "База данных недоступна. Проверьте подключение к MySQL."
        });
    }
    
    try {
        const result = await mysqlClient.borrowBook(inventory_number, reader_id);
        
        if (result.success) {
            res.json({
                success: true,
                source: 'MySQL напрямую',
                message: result.message,
                loan_id: result.loan_id,
                inventory_number,
                reader_id,
                date_taken: result.date_taken
            });
        } else {
            res.status(400).json({
                success: false,
                source: 'MySQL напрямую',
                message: result.message
            });
        }
        
    } catch (error) {
        console.error('Ошибка выдачи книги:', error.message);
        res.status(500).json({
            success: false,
            error: 'Внутренняя ошибка сервера'
        });
    }
});

app.get('/api/physical/return', (req, res) => {
    res.status(405).json({
        success: false,
        error: "Method Not Allowed",
        message: "Используйте POST метод для возврата книги",
        example: {
            inventory_number: "LIB-2023-001",
            loan_id: "LOAN-123"
        }
    });
});

app.post('/api/physical/return', async (req, res) => {
    const { inventory_number, loan_id } = req.body;
    
    console.log(`📚 Возврат книги: ${inventory_number}, заём: ${loan_id || 'не указан'}`);
    
    if (!inventory_number) {
        return res.status(400).json({
            success: false,
            message: "Требуется inventory_number"
        });
    }
    
    if (!mysqlClient) {
        return res.status(503).json({
            success: false,
            source: 'Node.js (offline)',
            message: "База данных недоступна. Проверьте подключение к MySQL."
        });
    }
    
    try {
        const result = await mysqlClient.returnBook(inventory_number, loan_id);
        
        if (result.success) {
            res.json({
                success: true,
                source: 'MySQL напрямую',
                message: result.message,
                inventory_number,
                loan_id: result.loan_id,
                date_returned: result.date_returned
            });
        } else {
            res.status(400).json({
                success: false,
                source: 'MySQL напрямую',
                message: result.message
            });
        }
        
    } catch (error) {
        console.error('Ошибка возврата книги:', error.message);
        res.status(500).json({
            success: false,
            error: 'Внутренняя ошибка сервера'
        });
    }
});

app.get('/api/stats', async (req, res) => {
    console.log('📊 Запрос статистики');
    
    if (!mysqlClient) {
        return res.json({
            success: true,
            source: 'Локальная база (fallback)',
            stats: {
                total_books: fallbackBooks.length,
                available: fallbackBooks.filter(b => b.status === 'available').length,
                borrowed: fallbackBooks.filter(b => b.status === 'borrowed').length
            }
        });
    }
    
    try {
        const stats = await mysqlClient.getStats();
        res.json({
            success: true,
            source: 'MySQL напрямую',
            stats
        });
    } catch (error) {
        console.error('Ошибка получения статистики:', error.message);
        res.status(500).json({
            success: false,
            error: 'Ошибка получения статистики'
        });
    }
});

app.get('/api/popular-books', async (req, res) => {
    const limit = parseInt(req.query.limit) || 5;
    console.log(`🏆 Популярные книги (лимит: ${limit})`);
    
    if (!mysqlClient) {
        return res.json({
            success: true,
            source: 'Локальная база (fallback)',
            count: 0,
            data: []
        });
    }
    
    try {
        const books = await mysqlClient.getPopularBooks(limit);
        res.json({
            success: true,
            source: 'MySQL напрямую',
            count: books.length,
            data: books
        });
    } catch (error) {
        console.error('Ошибка получения популярных книг:', error.message);
        res.status(500).json({
            success: false,
            error: 'Ошибка получения данных'
        });
    }
});

app.get('/api/health', async (req, res) => {
    let mysqlStatus = 'disconnected';
    let bookCount = 0;
    
    if (mysqlClient) {
        try {
            const [result] = await mysqlPool.query('SELECT 1 as status');
            mysqlStatus = result[0].status === 1 ? 'connected' : 'error';
            
            const [books] = await mysqlPool.query('SELECT COUNT(*) as count FROM physical_books');
            bookCount = books[0].count;
        } catch (error) {
            mysqlStatus = 'error';
        }
    }
    
    res.json({
        status: "OK",
        service: "Library REST API v1.0",
        timestamp: new Date().toISOString(),
        port: PORT,
        mysql_status: mysqlStatus,
        books_in_database: bookCount,
        endpoints: {
            books: "GET /api/physical/books",
            search: "GET /api/physical/books/search?q=...",
            loan: "POST /api/physical/loan", 
            return: "POST /api/physical/return",
            stats: "GET /api/stats",
            health: "GET /api/health"
        }
    });
});


app.use((req, res) => {
    res.status(404).json({ 
        success: false,
        error: 'Endpoint not found',
        requested: `${req.method} ${req.url}`,
        available_endpoints: [
            'GET  /',
            'GET  /api/physical/books',
            'GET  /api/physical/books/search?q=...',
            'GET  /api/physical/books/:inventory',
            'POST /api/physical/loan',
            'POST /api/physical/return',
            'GET  /api/stats',
            'GET  /api/popular-books',
            'GET  /api/health'
        ]
    });
});

app.use((err, req, res, next) => {
    console.error('🔥 Ошибка сервера:', err);
    res.status(500).json({ 
        success: false,
        error: 'Internal server error',
        message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
    });
});

async function startServer() {
    // Инициализация
    await initialize();
    
    // Запуск сервера
    app.listen(PORT, () => {
        console.log(`
✅ Node.js REST API запущен!
👉 Адрес: http://localhost:${PORT}
🗄️  База данных: ${mysqlConfig.database}@${mysqlConfig.host}
📊 Книг в базе: ${mysqlClient ? 'подключение...' : 'недоступно'}
🎯 Основные эндпоинты:
   GET  http://localhost:${PORT}/api/physical/books
   POST http://localhost:${PORT}/api/physical/loan
   POST http://localhost:${PORT}/api/physical/return
   GET  http://localhost:${PORT}/api/health
        `);
    });
}

startServer().catch(error => {
    console.error('❌ Не удалось запустить сервер:', error);
    process.exit(1);
});