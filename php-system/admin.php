<?php
require_once __DIR__ . '/config/database.php';

require_once __DIR__ . '/soap/LibraryDatabase.php';  


try {
    $db = new LibraryDatabase();
    $db_status = "✓ Подключение к БД успешно";
} catch (Exception $e) {
    $db_status = "✗ Ошибка БД: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель Библиотеки</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #4a6fa5 0%, #3a4f8c 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .db-status {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            backdrop-filter: blur(10px);
        }
        
        .status-online {
            color: #4ade80;
        }
        
        .status-offline {
            color: #f87171;
        }
        
        .tabs {
            display: flex;
            background: #f8fafc;
            border-bottom: 3px solid #e2e8f0;
            padding: 0 30px;
        }
        
        .tab {
            padding: 20px 30px;
            background: none;
            border: none;
            font-size: 1.1em;
            cursor: pointer;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .tab:hover {
            color: #4a6fa5;
            background: #f1f5f9;
        }
        
        .tab.active {
            color: #4a6fa5;
            border-bottom-color: #4a6fa5;
            background: white;
        }
        
        .tab-content {
            padding: 40px;
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        h2 {
            color: #4a6fa5;
            margin-bottom: 25px;
            font-size: 1.8em;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        h2::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(90deg, #4a6fa5, transparent);
            margin-left: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 600;
            font-size: 1.1em;
        }
        
        input[type="text"],
        input[type="number"] {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1.1em;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        input[type="text"]:focus,
        input[type="number"]:focus {
            outline: none;
            border-color: #4a6fa5;
            background: white;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
        }
        
        button[type="submit"] {
            background: linear-gradient(135deg, #4a6fa5 0%, #3a4f8c 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 12px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(74, 111, 165, 0.3);
        }
        
        button[type="submit"]:active {
            transform: translateY(0);
        }
        
        .result {
            margin-top: 30px;
            /* padding: 25px; */
            border-radius: 12px;
            animation: fadeIn 0.5s ease;
            align-item: center;
        }
        
        .loading {
            background: #f0f9ff;
            border: 2px solid #bae6fd;
            color: #0369a1;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1em;
        }
        
        .success {
            background: #f0fdf4;
            border: 2px solid #bbf7d0;
            color: #166534;
        }
        
        .error {
            background: #fef2f2;
            border: 2px solid #fecaca;
            color: #dc2626;
        }
        
        .success h3,
        .error h3 {
            color: inherit;
            margin-bottom: 15px;
            font-size: 1.4em;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        th {
            background: #4a6fa5;
            color: white;
            padding: 18px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        .status-available {
            color: #16a34a;
            font-weight: bold;
        }
        
        .status-borrowed {
            color: #ea580c;
            font-weight: bold;
        }
        
        .status-lost {
            color: #dc2626;
            font-weight: bold;
        }
        
        pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 10px;
            overflow-x: auto;
            font-family: 'Consolas', monospace;
            font-size: 0.9em;
            margin-top: 10px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .tabs {
                flex-wrap: wrap;
                padding: 0;
            }
            
            .tab {
                flex: 1;
                min-width: 120px;
                justify-content: center;
                padding: 15px;
            }
            
            header h1 {
                font-size: 2em;
                flex-direction: column;
                gap: 10px;
            }
            
            .db-status {
                position: relative;
                top: 0;
                right: 0;
                margin-top: 15px;
                display: inline-block;
            }
        }
        
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-error {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border: 2px solid #bae6fd;
        }
        
        .stat-card h3 {
            color: #0369a1;
            margin-bottom: 10px;
            font-size: 1.3em;
        }
        
        .stat-card .number {
            font-size: 2.5em;
            font-weight: bold;
            color: #0c4a6e;
        }
        
        footer {
            text-align: center;
            padding: 30px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .help-text {
            font-size: 0.9em;
            color: #94a3b8;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Административная панель Библиотеки</h1>
            <div class="db-status <?php echo strpos($db_status, '✓') !== false ? 'status-online' : 'status-offline'; ?>">
                <?php echo $db_status; ?>
            </div>
        </header>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('search', this)">🔍 Поиск книг</button>
            <button class="tab" onclick="switchTab('loan', this)">📖 Выдача книг</button>
            <button class="tab" onclick="switchTab('return', this)">↪️ Возврат книг</button>
            <button class="tab" onclick="switchTab('overdue', this)">⏰ Просроченные</button>
        </div>
        
        <div class="tab-content active" id="search-tab">
            <div class="form-grid">
                <div>
                    <h2>🔍 Поиск по инвентарному номеру</h2>
                    <form id="searchByInventoryForm">
                        <div class="form-group">
                            <label for="inventory_number_search">Инвентарный номер:</label>
                            <input type="text" id="inventory_number_search" name="inventory_number" 
                                   placeholder="Например: LIB-2023-001" required>
                            <span class="help-text">Введите полный инвентарный номер книги</span>
                        </div>
                        <button type="submit">🔎 Найти книгу</button>
                    </form>
                    <div id="searchResult" class="result"></div>
                </div>
                
                <div>
                    <h2>👨‍🏫 Поиск по автору</h2>
                    <form id="searchByAuthorForm">
                        <div class="form-group">
                            <label for="author_name">Имя автора:</label>
                            <input type="text" id="author_name" name="author_name" 
                                   placeholder="Например: Николай Гоголь" required>
                            <span class="help-text">Введите полное имя автора</span>
                        </div>
                        <button type="submit">🔎 Найти книги автора</button>
                    </form>
                    <div id="authorSearchResult" class="result"></div>
                </div>
            </div>
        </div>
        
        <div class="tab-content" id="loan-tab">
            <h2>📖 Выдача книги читателю</h2>
            <form id="loanForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="inventory_number_loan">Инвентарный номер книги:</label>
                        <input type="text" id="inventory_number_loan" name="inventory_number" 
                               placeholder="LIB-2023-001" required>
                        <span class="help-text">Книга должна быть в статусе "Доступна"</span>
                    </div>
                    <div class="form-group">
                        <label for="reader_card">Номер читательского билета:</label>
                        <input type="text" id="reader_card" name="reader_card" 
                               placeholder="R-99999" required>
                        <span class="help-text">Формат: R-XXXXX</span>
                    </div>
                </div>
                <button type="submit">📝 Оформить выдачу</button>
            </form>
            <div id="loanResult" class="result"></div>
        </div>
        
        <div class="tab-content" id="return-tab">
            <h2>↪️ Возврат книги</h2>
            <form id="returnForm">
                <div class="form-group">
                    <label for="inventory_number_return">Инвентарный номер книги:</label>
                    <input type="text" id="inventory_number_return" name="inventory_number" 
                           placeholder="LIB-2023-001" required>
                    <span class="help-text">Книга должна быть в статусе "Выдана"</span>
                </div>
                <button type="submit">↪️ Зарегистрировать возврат</button>
            </form>
            <div id="returnResult" class="result"></div>
        </div>
        
        <div class="tab-content" id="overdue-tab">
            <h2>⏰ Просроченные книги</h2>
            <p style="margin-bottom: 20px; color: #64748b;">
                Список книг, которые не были возвращены в течение 30 дней.
            </p>
            <button onclick="loadOverdueBooks()" style="margin-bottom: 20px;">🔄 Обновить список</button>
            <div id="overdueResult" class="result"></div>
        </div>
        
        <footer>
            <p>© 2024 Система управления библиотекой. Все права защищены.</p>
            <p style="font-size: 0.9em; margin-top: 10px;">Версия 2.0 | SOAP Web Service</p>
        </footer>
    </div>
    
    <script>
        
        function switchTab(tabName, clickedElement = null) {
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab').forEach(button => {
        button.classList.remove('active');
    });
    
    const tabElement = document.getElementById(tabName + '-tab');
    if (tabElement) {
        tabElement.classList.add('active');
    }
    
    if (clickedElement) {
        clickedElement.classList.add('active');
    } else {
        document.querySelectorAll('.tab').forEach(button => {
            if (button.onclick && button.onclick.toString().includes("'" + tabName + "'")) {
                button.classList.add('active');
            }
        });
    }
}
        
        function formatXml(xml) {
            if (!xml) return "Нет данных";
            
            try {
                const formatted = xml
                    .replace(/>\s*</g, '>\n<')
                    .split('\n')
                    .map(line => {
                        const indent = (line.match(/^<\/(\w+)/) ? -1 : 0) + 
                                      (line.match(/^<\w[^>]*[^\/]>$/) ? 1 : 0);
                        return '  '.repeat(Math.max(0, indent)) + line.trim();
                    })
                    .filter(line => line)
                    .join('\n');
                
                return formatted;
            } catch (error) {
                return xml.replace(/>\s*</g, '>\n<');
            }
        }
        
        function escapeXml(unsafe) {
            if (!unsafe) return '';
            return unsafe.toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&apos;');
        }
        
        
        function parseSoapResponse(xmlText) {
            try {
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlText, "text/xml");
                
                const parserError = xmlDoc.querySelector('parsererror');
                if (parserError) {
                    throw new Error("Неверный формат XML");
                }
                
                const fault = xmlDoc.querySelector('faultstring');
                if (fault) {
                    throw new Error(fault.textContent);
                }
                
                const returnElements = xmlDoc.getElementsByTagName('return');
                if (returnElements.length > 0) {
                    const returnXml = returnElements[0].textContent;
                    
                    return parseInternalXml(returnXml);
                }
                
                const books = parseBooksFromXml(xmlDoc);
                if (books.length > 0) {
                    return books;
                }
                
                const allBooks = xmlDoc.getElementsByTagName('book');
                if (allBooks.length > 0) {
                    return parseBookElements(allBooks);
                }
                
                return [];
                
            } catch (error) {
                return [];
            }
        }
        
        function parseInternalXml(xmlText) {
            try {
                const parser = new DOMParser();
                const innerDoc = parser.parseFromString(xmlText, "text/xml");
                
                return parseBooksFromXml(innerDoc);
                
            } catch (error) {
                return [];
            }
        }
        
        function parseBooksFromXml(xmlDoc) {
            const books = [];
            
            const bookElements = xmlDoc.getElementsByTagName('book');
            
            for (let bookElement of bookElements) {
                const book = {
                    inventory_number: getElementValue(bookElement, 'inventory_number'),
                    title: getElementValue(bookElement, 'title'),
                    author: getElementValue(bookElement, 'author'),
                    year: getElementValue(bookElement, 'year'),
                    location: getElementValue(bookElement, 'location'),
                    status: getElementValue(bookElement, 'status')
                };
                
                if (book.title && book.title !== 'N/A') {
                    books.push(book);
                }
            }
            
            if (books.length === 0) {
                const booksElement = xmlDoc.querySelector('books');
                if (booksElement) {
                    const bookNodes = booksElement.querySelectorAll('book');
                    for (let bookNode of bookNodes) {
                        const book = {
                            inventory_number: getElementValue(bookNode, 'inventory_number'),
                            title: getElementValue(bookNode, 'title'),
                            author: getElementValue(bookNode, 'author'),
                            year: getElementValue(bookNode, 'year'),
                            location: getElementValue(bookNode, 'location'),
                            status: getElementValue(bookNode, 'status')
                        };
                        
                        if (book.title && book.title !== 'N/A') {
                            books.push(book);
                        }
                    }
                }
            }
            
            return books;
        }
        
        function parseBookElements(bookElements) {
            const books = [];
            
            for (let bookElement of bookElements) {
                const book = {
                    inventory_number: getElementValue(bookElement, 'inventory_number'),
                    title: getElementValue(bookElement, 'title'),
                    author: getElementValue(bookElement, 'author'),
                    year: getElementValue(bookElement, 'year'),
                    location: getElementValue(bookElement, 'location'),
                    status: getElementValue(bookElement, 'status')
                };
                
                if (book.title && book.title !== 'N/A') {
                    books.push(book);
                }
            }
            
            return books;
        }
        
        function getElementValue(parent, tagName) {
            const element = parent.getElementsByTagName(tagName)[0];
            return element ? element.textContent.trim() : 'N/A';
        }
        
        
        function showBooksTable(books, author_name) {
            if (books.length === 0) {
                return '<div class="error">Книги не найдены</div>';
            }
            
            let html = `<div class="success">
                <h3>📚 Найдено книг: ${books.length}</h3>
                <table>
                    <tr>
                        <th>Инв. номер</th>
                        <th>Название</th>
                        <th>Автор</th>
                        <th>Год</th>
                        <th>Статус</th>
                        <th>Местоположение</th>
                    </tr>`;
            
            books.forEach(book => {
                let statusClass = 'status-available';
                let statusText = book.status;
                
                if (book.status === 'borrowed' || book.status === 'выдана') {
                    statusClass = 'status-borrowed';
                    statusText = 'Выдана';
                } else if (book.status === 'lost' || book.status === 'утеряна') {
                    statusClass = 'status-lost';
                    statusText = 'Утеряна';
                } else {
                    statusText = 'Доступна';
                }
                
                html += `
                    <tr>
                        <td><strong>${book.inventory_number}</strong></td>
                        <td>${book.title}</td>
                        <td>${book.author}</td>
                        <td>${book.year}</td>
                        <td class="${statusClass}">${statusText}</td>
                        <td><small>${book.location}</small></td>
                    </tr>
                `;
            });
            
            html += `</table></div>`;
            return html;
        }
        
        
        document.getElementById('searchByInventoryForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const inventory_number = formData.get('inventory_number');
            
            document.getElementById('searchResult').innerHTML = 
                `<div class="loading">🔍 Ищем книгу с номером ${inventory_number}...</div>`;
            
            try {
                const soapRequest = `<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:ns1="urn:LibraryService">
    <SOAP-ENV:Body>
        <ns1:getBookByInventory>
            <inventory_number>${escapeXml(inventory_number)}</inventory_number>
        </ns1:getBookByInventory>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>`;
                
                const response = await fetch('soap-server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'text/xml; charset=utf-8',
                    },
                    body: soapRequest
                });
                
                const xmlText = await response.text();
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const books = parseSoapResponse(xmlText);
                
                if (books.length > 0) {
                    document.getElementById('searchResult').innerHTML = showBooksTable(books, 'Результат поиска');
                } else {
                    const parser = new DOMParser();
                    const xmlDoc = parser.parseFromString(xmlText, "text/xml");
                    
                    const error = xmlDoc.querySelector('error');
                    if (error) {
                        const message = error.querySelector('message')?.textContent || 'Книга не найдена';
                        document.getElementById('searchResult').innerHTML = 
                            `<div class="error">${message}</div>`;
                    } else {
                        document.getElementById('searchResult').innerHTML = 
                            `<div class="error">Книга не найдена. Ответ сервера:<br>
                            <pre style="font-size: 12px; max-height: 200px; overflow: auto;">${escapeXml(xmlText.substring(0, 1000))}</pre></div>`;
                    }
                }
                
            } catch (error) {
                document.getElementById('searchResult').innerHTML = 
                    `<div class="error">Ошибка: ${error.message}</div>`;
            }
        });
        
        document.getElementById('searchByAuthorForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const author_name = document.getElementById('author_name').value.trim();
            
            if (!author_name) {
                document.getElementById('authorSearchResult').innerHTML = 
                    '<div class="error">Введите имя автора</div>';
                return;
            }
            
            document.getElementById('authorSearchResult').innerHTML = 
                `<div class="loading">🔍 Ищем книги автора "${author_name}"...</div>`;
            
            try {
                const soapRequest = `<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:ns1="urn:LibraryService">
    <SOAP-ENV:Body>
        <ns1:searchBooksByAuthor>
            <author_name>${escapeXml(author_name)}</author_name>
        </ns1:searchBooksByAuthor>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>`;
                
                const response = await fetch('soap-server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'text/xml; charset=utf-8'
                    },
                    body: soapRequest
                });
                
                const xmlText = await response.text();
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const books = parseSoapResponse(xmlText);
                
                if (books.length > 0) {
                    document.getElementById('authorSearchResult').innerHTML = showBooksTable(books, author_name);
                } else {
                    document.getElementById('authorSearchResult').innerHTML = 
                        `<div class="error">Книги автора "${author_name}" не найдены</div>`;
                }
                
            } catch (error) {
                document.getElementById('authorSearchResult').innerHTML = 
                    `<div class="error">Ошибка: ${error.message}</div>`;
            }
        });
        
        document.getElementById('loanForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const inventory_number = formData.get('inventory_number');
            const reader_card = formData.get('reader_card');
            
            document.getElementById('loanResult').innerHTML = 
                `<div class="loading">📖 Выдаем книгу ${inventory_number} читателю ${reader_card}...</div>`;
            
            try {
                const soapRequest = `<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:ns1="urn:LibraryService">
    <SOAP-ENV:Body>
        <ns1:registerLoan>
            <inventory_number>${escapeXml(inventory_number)}</inventory_number>
            <reader_card>${escapeXml(reader_card)}</reader_card>
        </ns1:registerLoan>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>`;
                
                const response = await fetch('soap-server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'text/xml; charset=utf-8',
                    },
                    body: soapRequest
                });
                
                const xmlText = await response.text();
                console.log("Выдача - сырой ответ:", xmlText);
                
                let success = null;
                let message = null;
                let loan_id = null;
                let date_taken = null;
                
                const successMatch = xmlText.match(/<success[^>]*>([^<]+)<\/success>/i);
                const messageMatch = xmlText.match(/<message[^>]*>([^<]+)<\/message>/i);
                
                if (successMatch) success = successMatch[1];
                if (messageMatch) message = messageMatch[1];
                
                const loanIdMatch = xmlText.match(/<loan_id[^>]*>([^<]+)<\/loan_id>/i);
                const dateMatch = xmlText.match(/<date_taken[^>]*>([^<]+)<\/date_taken>/i);
                
                if (loanIdMatch) loan_id = loanIdMatch[1];
                if (dateMatch) date_taken = dateMatch[1];
                
                if (!success) {
                    try {
                        const parser = new DOMParser();
                        const xmlDoc = parser.parseFromString(xmlText, "text/xml");
                        
                        const returnElement = xmlDoc.getElementsByTagName('return')[0];
                        if (returnElement && returnElement.textContent) {
                            const innerDoc = parser.parseFromString(returnElement.textContent, "text/xml");
                            success = innerDoc.querySelector('success')?.textContent;
                            message = innerDoc.querySelector('message')?.textContent;
                            loan_id = innerDoc.querySelector('loan_id')?.textContent;
                            date_taken = innerDoc.querySelector('date_taken')?.textContent;
                        }
                        
                        if (!success) {
                            success = xmlDoc.querySelector('success')?.textContent;
                            message = xmlDoc.querySelector('message')?.textContent;
                        }
                    } catch (e) {
                    }
                }
                
                if (success === 'true') {
                    const html = `
                        <div class="success">
                            <h3>✅ Успешно!</h3>
                            <p>${message || 'Книга выдана'}</p>
                            <table>
                                ${loan_id ? `<tr><td>ID выдачи:</td><td><strong>${loan_id}</strong></td></tr>` : ''}
                                <tr><td>Инвентарный номер:</td><td><strong>${inventory_number}</strong></td></tr>
                                <tr><td>Читательский билет:</td><td><strong>${reader_card}</strong></td></tr>
                                ${date_taken ? `<tr><td>Дата выдачи:</td><td><strong>${date_taken}</strong></td></tr>` : ''}
                            </table>
                        </div>
                    `;
                    document.getElementById('loanResult').innerHTML = html;
                } else {
                    document.getElementById('loanResult').innerHTML = 
                        `<div class="error">❌ ${message || 'Ошибка при выдаче книги'}</div>`;
                }
                
            } catch (error) {
                document.getElementById('loanResult').innerHTML = 
                    `<div class="error">Ошибка соединения: ${error.message}</div>`;
            }
        });
        
        document.getElementById('returnForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const inventory_number = formData.get('inventory_number');
            
            document.getElementById('returnResult').innerHTML = 
                `<div class="loading">↪️ Возвращаем книгу ${inventory_number}...</div>`;
            
            try {
                const soapRequest = `<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:ns1="urn:LibraryService">
    <SOAP-ENV:Body>
        <ns1:returnBook>
            <inventory_number>${escapeXml(inventory_number)}</inventory_number>
        </ns1:returnBook>
    </SOAP-ENV:Body>
</SOAP-ENV:Envelope>`;
                
                const response = await fetch('soap-server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'text/xml; charset=utf-8',
                    },
                    body: soapRequest
                });
                
                const xmlText = await response.text();
                console.log("Возврат - сырой ответ:", xmlText);
                
                let success = null;
                let message = null;
                
                const successMatch = xmlText.match(/<success[^>]*>([^<]+)<\/success>/i);
                const messageMatch = xmlText.match(/<message[^>]*>([^<]+)<\/message>/i);
                
                if (successMatch) success = successMatch[1];
                if (messageMatch) message = messageMatch[1];
                
                if (!success) {
                    try {
                        const parser = new DOMParser();
                        const xmlDoc = parser.parseFromString(xmlText, "text/xml");
                        
                        const returnElement = xmlDoc.getElementsByTagName('return')[0];
                        if (returnElement && returnElement.textContent) {
                            const innerDoc = parser.parseFromString(returnElement.textContent, "text/xml");
                            success = innerDoc.querySelector('success')?.textContent;
                            message = innerDoc.querySelector('message')?.textContent;
                        }
                        
                        if (!success) {
                            success = xmlDoc.querySelector('success')?.textContent;
                            message = xmlDoc.querySelector('message')?.textContent;
                        }
                    } catch (e) {
                    }
                }
                
                if (success === 'true') {
                    document.getElementById('returnResult').innerHTML = 
                        `<div class="success">✅ ${message || 'Книга возвращена'}</div>`;
                } else {
                    document.getElementById('returnResult').innerHTML = 
                        `<div class="error">❌ ${message || 'Ошибка при возврате книги'}</div>`;
                }
                
            } catch (error) {
                document.getElementById('returnResult').innerHTML = 
                    `<div class="error">Ошибка соединения: ${error.message}</div>`;
            }
        });
        
        async function loadOverdueBooks() {
            document.getElementById('overdueResult').innerHTML = 
                `<div class="loading">⏰ Загружаем список просроченных книг...</div>`;
            
            try {
                const response = await fetch('report.php?type=overdue&format=xml');
                const xmlText = await response.text();
                
                const parser = new DOMParser();
                const xmlDoc = parser.parseFromString(xmlText, "text/xml");
                
                const books = xmlDoc.getElementsByTagName('overdue_book');
                
                if (books.length > 0) {
                    let html = `<div class="success">
                        <h3>📊 Просроченные книги: ${books.length}</h3>
                        <table>
                            <tr>
                                <th>Инв. номер</th>
                                <th>Название</th>
                                <th>Автор</th>
                                <th>Читатель</th>
                                <th>Дата выдачи</th>
                                <th>Дней просрочки</th>
                            </tr>`;
                    
                    for (let book of books) {
                        const invNum = book.querySelector('inventory_number')?.textContent || 'N/A';
                        const title = book.querySelector('title')?.textContent || 'N/A';
                        const author = book.querySelector('author')?.textContent || 'N/A';
                        const reader = book.querySelector('reader_card')?.textContent || 'N/A';
                        const date = book.querySelector('date_taken')?.textContent || 'N/A';
                        const days = book.querySelector('days_overdue')?.textContent || '0';
                        
                        html += `
                            <tr>
                                <td>${invNum}</td>
                                <td>${title}</td>
                                <td>${author}</td>
                                <td>${reader}</td>
                                <td>${date}</td>
                                <td><span style="color: #dc3545; font-weight: bold;">${days} дней</span></td>
                            </tr>
                        `;
                    }
                    
                    html += `</table></div>`;
                    document.getElementById('overdueResult').innerHTML = html;
                } else {
                    document.getElementById('overdueResult').innerHTML = 
                        `<div class="success">✅ Просроченных книг нет!</div>`;
                }
                
            } catch (error) {
                document.getElementById('overdueResult').innerHTML = 
                    `<div class="error">Ошибка: ${error.message}</div>`;
            }
        }
        
        
        window.addEventListener('DOMContentLoaded', () => {
            document.getElementById('inventory_number_search').value = 'LIB-2023-001';
            document.getElementById('author_name').value = 'Николай Гоголь';
            document.getElementById('inventory_number_loan').value = 'LIB-2024-004';
            document.getElementById('reader_card').value = 'R-99999';
            document.getElementById('inventory_number_return').value = 'LIB-2023-001';
            
            switchTab('search');
        });
    </script>
</body>
</html>