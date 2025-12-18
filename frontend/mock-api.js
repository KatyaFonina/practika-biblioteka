const express = require('express');
const app = express();
const cors = require('cors');

app.use(cors());
app.use(express.json());

const books = [
    {
        inventory_number: "LIB-2023-001",
        title: "Мастер и Маргарита",
        author: "Михаил Булгаков",
        year: 1967,
        location: "Стеллаж А, полка 3",
        status: "available"
    },
    {
        inventory_number: "LIB-2023-002", 
        title: "Преступление и наказание",
        author: "Фёдор Достоевский",
        year: 1866,
        location: "Стеллаж Б, полка 1",
        status: "borrowed"
    },
    {
        inventory_number: "LIB-2024-001",
        title: "Убийство в «Восточном экспрессе»",
        author: "Агата Кристи",
        year: 1934,
        location: "Стеллаж В, полка 2",
        status: "available"
    }
];

app.get('/api/books/search', (req, res) => {
    const query = (req.query.q || '').toLowerCase();
    
    if (query) {
        const filtered = books.filter(book => 
            book.title.toLowerCase().includes(query) ||
            book.author.toLowerCase().includes(query)
        );
        res.json(filtered);
    } else {
        res.json(books);
    }
});

app.post('/api/soap/borrow', (req, res) => {
    const { inventory_number, reader_id } = req.body;
    
    res.json({
        success: true,
        message: `Книга ${inventory_number} выдана читателю ${reader_id}`,
        loan_id: Math.floor(Math.random() * 9000) + 1000
    });
});

app.post('/api/soap/return', (req, res) => {
    res.json({
        success: true,
        message: "Книга успешно возвращена"
    });
});

app.get('/api/reports/:type', (req, res) => {
    const reports = {
        loans: [
            { book: "Преступление и наказание", reader: "Иванов И.И.", date: "2024-01-15" }
        ],
        popular: [
            { title: "Мастер и Маргарита", count: 25 },
            { title: "Убийство в «Восточном экспрессе»", count: 18 }
        ]
    };
    
    res.json(reports[req.params.type] || []);
});

app.get('/api/health', (req, res) => {
    res.json({ status: "OK", service: "mock-api" });
});


app.get('/', (req, res) => {
    res.json({
        service: "Mock Library API",
        version: "1.0",
        endpoints: {
            searchBooks: "GET /api/books/search?q=...",
            borrowBook: "POST /api/soap/borrow",
            returnBook: "POST /api/soap/return",
            reports: "GET /api/reports/:type",
            health: "GET /api/health"
        },
        note: "Это тестовый API для фронтенда на порту 8081"
    });
});

app.listen(3002, () => {
    console.log('Mock API запущен: http://localhost:3002');
    console.log('Доступные эндпоинты:');
    console.log('  GET  /               - Документация');
    console.log('  GET  /api/books/search?q=...');
    console.log('  POST /api/soap/borrow');
    console.log('  POST /api/soap/return');
    console.log('  GET  /api/reports/:type');
    console.log('  GET  /api/health');
});