const axios = require('axios');

class IntegrationController {
    constructor() {
        this.phpApiUrl = process.env.PHP_API_URL || null;
        console.log('🔧 PHP API URL:', this.phpApiUrl || 'не настроен');
    }

async getBooks(req, res) {
    try {
        console.log('📚 Запрос книг из PHP API');
        
        if (!this.phpApiUrl) {
            console.log('⚠️ PHP API не настроен, возвращаем тестовые данные');
            return this.getMockBooks(req, res);
        }
        
        const apiUrl = this.phpApiUrl.endsWith('/rest-api.php') 
            ? this.phpApiUrl 
            : `${this.phpApiUrl}/rest-api.php`;
        
        console.log(`🔗 Запрос к: ${apiUrl}/books`);
        
        const response = await axios.get(`${apiUrl}/books`, {
            timeout: 3000
        });
        
        console.log('✅ Получены данные из PHP API');
        res.json({
            success: true,
            source: 'PHP REST API',
            ...response.data
        });
        
    } catch (error) {
        console.error('❌ Ошибка при запросе к PHP:', error.message);
        this.getMockBooks(req, res);
    }
}
    
    getMockBooks(req, res) {
        const books = [
            {
                inventory_number: "BLUM-001",
                title: "Мастер и Маргарита",
                author: "Михаил Булгаков",
                year: 1966,
                status: "available",
                location: "Сектор A, Полка 3"
            },
            {
                inventory_number: "BLUM-002",
                title: "Преступление и наказание",
                author: "Фёдор Достоевский",
                year: 1866,
                status: "borrowed",
                location: "Сектор B, Полка 1"
            },
            {
                inventory_number: "BLUM-003",
                title: "Война и мир",
                author: "Лев Толстой",
                year: 1869,
                status: "available",
                location: "Сектор C, Полка 2"
            }
        ];
        
        res.json({
            success: true,
            source: 'Тестовые данные',
            count: books.length,
            data: books
        });
    }
}

module.exports = new IntegrationController();