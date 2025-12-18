const axios = require('axios');
const { exec } = require('child_process');
const util = require('util');
const execPromise = util.promisify(exec);

class IntegrationTester {
    constructor() {
        this.API_BASE = 'http://localhost:3000';
        this.PHP_URL = 'http://localhost:8000';
        this.testResults = [];
    }

    async runAllTests() {
        console.log('🚀 НАЧАЛО СКВОЗНОГО ТЕСТИРОВАНИЯ БИБЛИОТЕЧНОЙ СИСТЕМЫ');
        console.log('='.repeat(70));
        console.log('Дата тестирования:', new Date().toLocaleString());
        console.log('='.repeat(70));

        try {
            await this.testServiceAvailability();

            await this.testRestApi();

            await this.testBusinessLogic();

            this.generateReport();

        } catch (error) {
            console.error('❌ Критическая ошибка тестирования:', error.message);
            process.exit(1);
        }
    }

    async testServiceAvailability() {
        console.log('\n📡 ЧАСТЬ 1: ПРОВЕРКА ДОСТУПНОСТИ СЕРВИСОВ');
        console.log('-'.repeat(50));

        await this.runTest('Node.js API доступен', async () => {
            const response = await axios.get(`${this.API_BASE}/api/health`, { timeout: 5000 });
            return response.data.status === 'OK';
        });

        await this.runTest('PHP SOAP Server (опционально)', async () => {
            try {
                const response = await axios.get(`${this.PHP_URL}/soap-server.php?wsdl`, { timeout: 3000 });
                return response.status === 200;
            } catch {
                return '⚠️ Не запущен (но это нормально для теста)';
            }
        });

        await this.runTest('Frontend файлы существуют', () => {
            const fs = require('fs');
            const path = require('path');
            const indexHtml = path.join(__dirname, 'frontend', 'index.html');
            return fs.existsSync(indexHtml);
        });
    }

    async testRestApi() {
        console.log('\n🔌 ЧАСТЬ 2: ТЕСТИРОВАНИЕ REST API');
        console.log('-'.repeat(50));

        await this.runTest('GET /api/physical/books', async () => {
            const response = await axios.get(`${this.API_BASE}/api/physical/books`);
            const data = response.data;
            
            if (!data.success) throw new Error('API не вернул success=true');
            if (!Array.isArray(data.data)) throw new Error('data не является массивом');
            if (data.count !== data.data.length) throw new Error('count не совпадает с длиной массива');
            
            return `Книг: ${data.count}, Источник: ${data.source}`;
        });

        await this.runTest('POST /api/physical/loan', async () => {
            // Сначала получаем доступную книгу
            const booksRes = await axios.get(`${this.API_BASE}/api/physical/books`);
            const availableBook = booksRes.data.data.find(b => b.status === 'available');
            
            if (!availableBook) {
                return '⚠️ Нет доступных книг для теста';
            }

            const loanRes = await axios.post(`${this.API_BASE}/api/physical/loan`, {
                inventory_number: availableBook.inventory_number,
                reader_id: `TEST-${Date.now()}`
            });

            if (!loanRes.data.success) throw new Error('Выдача не удалась');
            
            this.lastLoanId = loanRes.data.loan_id;
            this.lastBookInv = availableBook.inventory_number;
            
            return `Выдана: ${availableBook.title}, ID займа: ${this.lastLoanId}`;
        });

        await this.runTest('POST /api/physical/return', async () => {
            if (!this.lastBookInv) {
                return '⚠️ Нет данных о выданной книге';
            }

            const returnRes = await axios.post(`${this.API_BASE}/api/physical/return`, {
                inventory_number: this.lastBookInv,
                loan_id: this.lastLoanId
            });

            if (!returnRes.data.success) throw new Error('Возврат не удался');
            return `Возвращена: ${this.lastBookInv}`;
        });

        await this.runTest('GET /api/books/search', async () => {
            const response = await axios.get(`${this.API_BASE}/api/books/search?q=агата`);
            return `Найдено: ${response.data.length} книг`;
        });

        await this.runTest('GET /api/reports/:type', async () => {
            const response = await axios.get(`${this.API_BASE}/api/reports/popular`);
            return `Записей в отчете: ${response.data.length}`;
        });
    }

    async testBusinessLogic() {
        console.log('\n💼 ЧАСТЬ 3: ТЕСТИРОВАНИЕ БИЗНЕС-ЛОГИКИ');
        console.log('-'.repeat(50));

        await this.runTest('Нельзя выдать уже выданную книгу', async () => {
            const booksRes = await axios.get(`${this.API_BASE}/api/physical/books`);
            const borrowedBook = booksRes.data.data.find(b => b.status === 'borrowed');
            
            if (!borrowedBook) {
                return '⚠️ Нет выданных книг для теста';
            }

            try {
                await axios.post(`${this.API_BASE}/api/physical/loan`, {
                    inventory_number: borrowedBook.inventory_number,
                    reader_id: `TEST-DUPLICATE-${Date.now()}`
                });
                throw new Error('Книга была выдана повторно (это ошибка!)');
            } catch (error) {
                if (error.response && error.response.status === 400) {
                    return '✅ Проверка пройдена: книга не выдана повторно';
                }
                throw error;
            }
        });

        await this.runTest('Корректность статистики', async () => {
            const booksRes = await axios.get(`${this.API_BASE}/api/physical/books`);
            const books = booksRes.data.data;
            
            const availableCount = books.filter(b => b.status === 'available').length;
            const borrowedCount = books.filter(b => b.status === 'borrowed').length;
            const totalCount = books.length;
            
            if (availableCount + borrowedCount !== totalCount) {
                throw new Error('Сумма статусов не равна общему количеству');
            }
            
            return `Доступно: ${availableCount}, Выдано: ${borrowedCount}, Всего: ${totalCount}`;
        });

        await this.runTest('Корректность формата данных', async () => {
            const booksRes = await axios.get(`${this.API_BASE}/api/physical/books`);
            const book = booksRes.data.data[0];
            
            const requiredFields = ['inventory_number', 'title', 'author', 'status'];
            const missingFields = requiredFields.filter(field => !(field in book));
            
            if (missingFields.length > 0) {
                throw new Error(`Отсутствуют поля: ${missingFields.join(', ')}`);
            }
            
            return '✅ Все обязательные поля присутствуют';
        });
    }

    async runTest(name, testFunction) {
        const startTime = Date.now();
        
        try {
            const result = await testFunction();
            const duration = Date.now() - startTime;
            
            this.testResults.push({
                name,
                status: '✅ ПРОЙДЕН',
                result: typeof result === 'string' ? result : 'OK',
                duration: `${duration}ms`
            });
            
            console.log(`  ${this.testResults.length}. ${name}`);
            console.log(`     ${this.testResults[this.testResults.length - 1].status} (${duration}ms)`);
            if (typeof result === 'string') {
                console.log(`     ${result}`);
            }
            
        } catch (error) {
            const duration = Date.now() - startTime;
            
            this.testResults.push({
                name,
                status: '❌ ОШИБКА',
                result: error.message,
                duration: `${duration}ms`
            });
            
            console.log(`  ${this.testResults.length}. ${name}`);
            console.log(`     ❌ ОШИБКА: ${error.message} (${duration}ms)`);
        }
    }

    generateReport() {
        console.log('\n' + '='.repeat(70));
        console.log('📊 ОТЧЕТ О ТЕСТИРОВАНИИ');
        console.log('='.repeat(70));

        const passed = this.testResults.filter(t => t.status.includes('✅')).length;
        const failed = this.testResults.filter(t => t.status.includes('❌')).length;
        const warnings = this.testResults.filter(t => t.result && t.result.includes('⚠️')).length;

        console.log(`\n📈 ИТОГИ:`);
        console.log(`  ✅ Пройдено: ${passed}`);
        console.log(`  ❌ Ошибок: ${failed}`);
        console.log(`  ⚠️  Предупреждений: ${warnings}`);
        console.log(`  📋 Всего тестов: ${this.testResults.length}`);

        if (failed > 0) {
            console.log('\n🔍 ОШИБКИ:');
            this.testResults
                .filter(t => t.status.includes('❌'))
                .forEach(test => {
                    console.log(`  • ${test.name}: ${test.result}`);
                });
        }

        if (warnings > 0) {
            console.log('\n⚠️  ПРЕДУПРЕЖДЕНИЯ:');
            this.testResults
                .filter(t => t.result && t.result.includes('⚠️'))
                .forEach(test => {
                    console.log(`  • ${test.name}: ${test.result}`);
                });
        }

        console.log('\n' + '='.repeat(70));
        console.log(failed === 0 ? '🎉 ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!' : '💥 НЕОБХОДИМО ИСПРАВИТЬ ОШИБКИ');
        console.log('='.repeat(70));

        const report = {
            timestamp: new Date().toISOString(),
            summary: { passed, failed, warnings, total: this.testResults.length },
            tests: this.testResults,
            systemInfo: {
                nodeVersion: process.version,
                platform: process.platform,
                apiBase: this.API_BASE
            }
        };

        const fs = require('fs');
        fs.writeFileSync(
            'test-report.json',
            JSON.stringify(report, null, 2)
        );
        
        console.log('\n📄 Отчет сохранен в: test-report.json');
        
        if (failed > 0) {
            process.exit(1);
        }
    }
}

async function main() {
    try {
        await axios.get('http://localhost:3000/api/health', { timeout: 2000 });
    } catch (error) {
        console.error('❌ Node.js сервер не запущен на localhost:3000');
        console.error('Запустите сначала: cd node-system && npm start');
        process.exit(1);
    }

    const tester = new IntegrationTester();
    await tester.runAllTests();
}

process.on('unhandledRejection', (error) => {
    console.error('⛔ Необработанная ошибка:', error.message);
    process.exit(1);
});

main();