// Конфигурация
const API_BASE_URL = 'http://localhost:3000'; 

const API_ENDPOINTS = {
    getBooks: '/api/physical/books',      
    borrowBook: '/api/physical/loan',    
    returnBook: '/api/physical/return',   
    health: '/api/health'
};
const ADMIN_PANEL_URL = 'http://localhost/php-system/admin.php';

let currentTab = 'search';
let books = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('Библиотечная система загружена');
    
    initTabs();
    
    initSearch();
    
    initOperations();
    
    initAdminPanel();
    
    checkApiStatus();
    
    updateTime();
    setInterval(updateTime, 1000);
    
    loadSampleData();
});

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.dataset.tab;
            switchTab(tabId);
        });
    });
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    document.getElementById(tabId).classList.add('active');
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
    
    currentTab = tabId;
    
    if (tabId === 'admin') {
        generateReport();
    }
}

function initSearch() {
    const searchBtn = document.getElementById('search-btn');
    const searchInput = document.getElementById('search-input');
    const showAllBtn = document.getElementById('show-all-btn');
    
    searchBtn.addEventListener('click', performSearch);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') performSearch();
    });
    
    if (showAllBtn) {
        showAllBtn.addEventListener('click', () => {
            document.getElementById('search-input').value = '';
            performSearch();
        });
    }
}

async function performSearch() {
    const query = document.getElementById('search-input').value.trim();
    const resultsDiv = document.getElementById('search-results');
    const statsDiv = document.getElementById('results-stats');
    
    console.log(`🔍 Поиск: "${query}"`);
    
    resultsDiv.innerHTML = '<p class="placeholder"><i class="fas fa-spinner fa-spin"></i> Загрузка книг...</p>';
    statsDiv.innerHTML = '';
    
    try {
        const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.getBooks}`);
        
        if (!response.ok) {
            throw new Error(`Ошибка API: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('✅ API ответ:', result);
        
        let books = [];
        if (result.success && result.data && Array.isArray(result.data)) {
            books = result.data;
        } else if (Array.isArray(result)) {
            books = result;
        }
        
        console.log(`📚 Получено книг: ${books.length}`);
        
        if (books.length === 0) {
            resultsDiv.innerHTML = '<p class="placeholder">В базе данных нет книг</p>';
            statsDiv.innerHTML = `Найдено: <strong>0</strong> из 0 книг`;
            return;
        }
        
        let filteredBooks = books;
        
        if (query) {
            const queryLower = query.toLowerCase();
            filteredBooks = books.filter(book => {
                const title = (book.title || '').toLowerCase();
                const author = (book.author || '').toLowerCase();
                return title.includes(queryLower) || author.includes(queryLower);
            });
        }else {
    filteredBooks = books;
    console.log('📚 Показываем все книги (пустой запрос)');
}
        
        const onlyAvailable = document.getElementById('filter-available').checked;
        if (onlyAvailable) {
            filteredBooks = filteredBooks.filter(book => book.status === 'available');
        }
        
        statsDiv.innerHTML = `Найдено: <strong>${filteredBooks.length}</strong> из ${books.length} книг`;
        
        if (filteredBooks.length === 0) {
            resultsDiv.innerHTML = `<p class="placeholder">По запросу "${query}" книги не найдены</p>`;
            return;
        }
        
        resultsDiv.innerHTML = filteredBooks.map(book => `
            <div class="book-card">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author"><i class="fas fa-user-pen"></i> ${book.author}</p>
                <p><i class="fas fa-hashtag"></i> Инв. номер: <strong>${book.inventory_number}</strong></p>
                <p><i class="fas fa-calendar"></i> Год: ${book.year}</p>
                <p><i class="fas fa-map-marker-alt"></i> Место: ${book.location}</p>
                <div class="book-status ${book.status === 'available' ? 'available' : 'borrowed'}">
                    ${book.status === 'available' ? '✅ Доступна' : '📖 Выдана'}
                </div>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('❌ Ошибка:', error);
        resultsDiv.innerHTML = `
            <p class="placeholder error">
                <i class="fas fa-exclamation-triangle"></i> Ошибка загрузки
                <br><small>${error.message}</small>
            </p>
        `;
    }
}
function initOperations() {
    document.getElementById('borrow-btn').addEventListener('click', borrowBook);
    
    document.getElementById('return-btn').addEventListener('click', returnBook);
}

async function borrowBook() {
    const invNumber = document.getElementById('borrow-inv').value.trim();
    const readerId = document.getElementById('reader-id').value.trim();
    const resultDiv = document.getElementById('borrow-result');
    console.log('🔍 Отладка borrowBook:', { invNumber, readerId });

    if (!invNumber || !readerId) {
        showResult(resultDiv, 'error', 'Заполните все поля');
        return;
    }
    
    showResult(resultDiv, 'loading', 'Выдача книги...');
    
    try {
        const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.borrowBook}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                inventory_number: invNumber, 
                reader_id: readerId 
            })
        });
        
        if (!response.ok) {
            throw new Error(`API: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showResult(resultDiv, 'success', `
                <strong>✅ Успешно!</strong><br>
                ${result.message}<br>
                ID займа: ${result.loan_id}
            `);
            
            document.getElementById('borrow-inv').value = '';
            document.getElementById('reader-id').value = '';
            
            addToHistory('Выдача', invNumber, readerId);
            
            window.addEventListener('load', () => {
    console.log('📚 Загрузка всех книг при старте...');
    
    document.getElementById('search-input').value = '';
    
    setTimeout(() => {
        performSearch();
    }, 500);
});
            
        } else {
            showResult(resultDiv, 'error', result.message || 'Ошибка');
        }
        
    } catch (error) {
        showResult(resultDiv, 'error', `
            <strong>❌ Ошибка</strong><br>
            ${error.message}<br>
            <small>Эндпоинт: ${API_ENDPOINTS.borrowBook}</small>
        `);
    }
}


function updateBookStatus(invNumber, newStatus) {
    console.log(`Локальное обновление: книга ${invNumber} -> ${newStatus}`);
}


async function returnBook() {
    const invNumber = document.getElementById('return-inv').value.trim();
    const loanId = document.getElementById('loan-id').value.trim();
    const resultDiv = document.getElementById('return-result');
    
    if (!invNumber) {
        showResult(resultDiv, 'error', 'Введите инвентарный номер');
        return;
    }
    
    showResult(resultDiv, 'loading', 'Возврат книги...');
    
    try {
        const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.returnBook}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                inventory_number: invNumber,
                loan_id: loanId || undefined
            })
        });
        
        if (!response.ok) {
            throw new Error(`API: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            showResult(resultDiv, 'success', `
                <strong>✅ Успешно!</strong><br>
                ${result.message}
            `);
            
            document.getElementById('return-inv').value = '';
            document.getElementById('loan-id').value = '';
            
            addToHistory('Возврат', invNumber);
            
            setTimeout(() => {
                const currentQuery = document.getElementById('search-input').value;
                if (currentQuery) performSearch();
            }, 1000);
            
        } else {
            showResult(resultDiv, 'error', result.message || 'Ошибка');
        }
        
    } catch (error) {
        showResult(resultDiv, 'error', `
            <strong>❌ Ошибка</strong><br>
            ${error.message}<br>
            <small>Эндпоинт: ${API_ENDPOINTS.returnBook}</small>
        `);
    }
}


function addToHistory(operation, invNumber, readerId = null) {
    const historyDiv = document.getElementById('operations-history');
    const now = new Date();
    const timeString = now.toLocaleTimeString('ru-RU');
    
    let historyItem = document.createElement('div');
    historyItem.className = 'history-item';
    historyItem.innerHTML = `
        <strong>${operation}</strong> | ${invNumber} 
        ${readerId ? `→ ${readerId}` : ''} 
        <span class="history-time">${timeString}</span>
    `;
    
    if (historyDiv.firstChild?.classList?.contains('placeholder')) {
        historyDiv.innerHTML = '';
    }
    
    historyDiv.prepend(historyItem);
    
    const items = historyDiv.querySelectorAll('.history-item');
    if (items.length > 10) {
        items[items.length - 1].remove();
    }
}

function initAdminPanel() {
    document.getElementById('generate-report').addEventListener('click', generateReport);
    document.getElementById('export-report').addEventListener('click', exportReport);
}

async function generateReport() {
    const reportType = document.getElementById('report-type').value;
    const period = document.getElementById('period').value;
    const reportDiv = document.getElementById('report-content');
    
    reportDiv.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Формирование отчета...</p>';
    
    try {
        await simulateApiDelay(1200);
        
        const reportData = generateMockReport(reportType, period);
        
        let html = `<h3>Отчет: ${getReportTitle(reportType)} (${getPeriodTitle(period)})</h3>`;
        
        if (reportData.summary) {
            html += `<div class="summary-stats">`;
            Object.entries(reportData.summary).forEach(([key, value]) => {
                html += `<div class="stat-item">
                    <span class="stat-label">${key}:</span>
                    <span class="stat-value">${value}</span>
                </div>`;
            });
            html += `</div>`;
        }
        
        if (reportData.table && reportData.table.length > 0) {
            html += `<table class="report-table">
                <thead><tr>`;
            
            Object.keys(reportData.table[0]).forEach(header => {
                html += `<th>${header}</th>`;
            });
            
            html += `</tr></thead><tbody>`;
            
            reportData.table.forEach(row => {
                html += `<tr>`;
                Object.values(row).forEach(cell => {
                    html += `<td>${cell}</td>`;
                });
                html += `</tr>`;
            });
            
            html += `</tbody></table>`;
        }
        
        if (reportData.chartData) {
            html += `<div class="chart-container">
                <p><i class="fas fa-chart-bar"></i> Визуализация данных</p>
                <div class="chart-placeholder">
                    (Здесь могла бы быть диаграмма на основе ${reportData.chartData})
                </div>
            </div>`;
        }
        
        reportDiv.innerHTML = html;
        
    } catch (error) {
        reportDiv.innerHTML = `<p class="error">Ошибка формирования отчета: ${error.message}</p>`;
    }
}

function generateMockReport(type, period) {
    const reports = {
        summary: {
            summary: {
                'Всего книг': books.length,
                'Доступно': books.filter(b => b.status === 'available').length,
                'Выдано': books.filter(b => b.status === 'borrowed').length,
                'Читателей': 42,
                'Активных займов': 15
            },
            table: [
                { 'Категория': 'Художественная', 'Количество': '120', 'Процент': '40%' },
                { 'Категория': 'Научная', 'Количество': '85', 'Процент': '28%' },
                { 'Категория': 'Техническая', 'Количество': '65', 'Процент': '22%' }
            ],
            chartData: 'summary'
        },
        popular: {
            table: [
                { 'Книга': 'Мастер и Маргарита', 'Автор': 'Булгаков', 'Выдач': '25' },
                { 'Книга': 'Преступление и наказание', 'Автор': 'Достоевский', 'Выдач': '18' },
                { 'Книга': 'Война и мир', 'Автор': 'Толстой', 'Выдач': '15' }
            ],
            chartData: 'popular'
        }
    };
    
    return reports[type] || { summary: { 'Сообщение': 'Нет данных для отчета' } };
}

function getReportTitle(type) {
    const titles = {
        summary: 'Общая статистика',
        popular: 'Популярные книги',
        overdue: 'Просроченные книги',
        readers: 'Активные читатели'
    };
    return titles[type] || 'Отчет';
}

function getPeriodTitle(period) {
    const periods = {
        week: 'неделя',
        month: 'месяц',
        quarter: 'квартал',
        year: 'год'
    };
    return periods[period] || 'период';
}

function exportReport() {
    alert('В реальном приложении здесь был бы экспорт в CSV/Excel');
}

// ==================== ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ====================
function showResult(element, type, message) {
    element.innerHTML = message;
    element.className = 'result-message ' + type;
    
    if (type !== 'loading') {
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    } else {
        element.style.display = 'block';
    }
}

async function simulateApiDelay(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

function loadSampleData() {
    books = [
        {
            inventory_number: "LIB-2023-001",
            title: "Мастер и Маргарита",
            author: "Михаил Булгаков",
            year: 1967,
            location: "Сектор А, полка 3",
            status: "available"
        },
        {
            inventory_number: "LIB-2023-002",
            title: "Преступление и наказание",
            author: "Фёдор Достоевский",
            year: 1866,
            location: "Сектор Б, полка 1",
            status: "borrowed"
        },
        {
            inventory_number: "LIB-2023-003",
            title: "Война и мир",
            author: "Лев Толстой",
            year: 1869,
            location: "Сектор В, полка 2",
            status: "available"
        },
        {
            inventory_number: "LIB-2023-004",
            title: "Мёртвые души",
            author: "Николай Гоголь",
            year: 1842,
            location: "Сектор А, полка 4",
            status: "available"
        }
    ];
}

async function checkApiStatus() {
    const apiStatus = document.getElementById('api-status');
    const dbStatus = document.getElementById('db-status');
    
    try {
        const healthResponse = await fetch(`${API_BASE_URL}${API_ENDPOINTS.health}`);
        const healthData = await healthResponse.json();
        
        apiStatus.innerHTML = `<i class="fas fa-circle"></i> Онлайн`;
        apiStatus.className = 'status-online';
        apiStatus.title = `${healthData.service} (${new Date(healthData.timestamp).toLocaleTimeString()})`;
        
        const booksResponse = await fetch(`${API_BASE_URL}${API_ENDPOINTS.getBooks}`);
        const booksData = await booksResponse.json();
        
        const bookCount = booksData.count || (booksData.data ? booksData.data.length : 0);
        dbStatus.innerHTML = `<i class="fas fa-circle"></i> ${bookCount} книг`;
        dbStatus.className = 'status-online';
        dbStatus.title = `Источник: ${booksData.source || 'API'}`;
        
    } catch (error) {
        apiStatus.innerHTML = '<i class="fas fa-circle"></i> Офлайн';
        apiStatus.className = 'status-offline';
        apiStatus.title = error.message;
        
        dbStatus.innerHTML = '<i class="fas fa-circle"></i> Нет данных';
        dbStatus.className = 'status-offline';
    }
    
    setTimeout(checkApiStatus, 30000);
}

function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('ru-RU');
    document.getElementById('last-update').textContent = timeString;
}
async function loadInitialData() {
    try {
        const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.getBooks}`);
        if (response.ok) {
            const data = await response.json();
            console.log('Загружено книг из API:', data.data ? data.data.length : 0);
        }
    } catch (error) {
        console.log('Не удалось загрузить данные из API:', error.message);
    }
}

const additionalStyles = `
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-item {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
}

.stat-label {
    display: block;
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #2c3e50;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.report-table th, .report-table td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: left;
}

.report-table th {
    background: #4a6491;
    color: white;
}

.report-table tr:nth-child(even) {
    background: #f2f2f2;
}

.chart-placeholder {
    background: #f8f9fa;
    padding: 40px;
    text-align: center;
    border-radius: 8px;
    color: #666;
    margin: 20px 0;
}

.history-item {
    padding: 10px 15px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}

.history-time {
    color: #888;
    font-size: 0.9rem;
}
`;
