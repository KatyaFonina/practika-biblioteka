const { join } = require('path');
const { Low } = require('lowdb');
const { JSONFile } = require('lowdb/node');
const { v4: uuidv4 } = require('uuid');

const file = join(__dirname, 'db.json');

const adapter = new JSONFile(file);
const db = new Low(adapter, { digitalResources: [], users: [] });

async function initializeDatabase() {
    await db.read();
    
    if (!db.data.digitalResources || db.data.digitalResources.length === 0) {        db.data.digitalResources = [
            {
                id: uuidv4(),
                title: "Война и мир (электронная версия)",
                type: "ebook",
                format: "PDF",
                size: "5.2 MB",
                createdAt: new Date().toISOString()
            },
            {
                id: uuidv4(),
                title: "Мастер и Маргарита (аудиокнига)",
                type: "audiobook",
                format: "MP3",
                duration: "12:45:30",
                createdAt: new Date().toISOString()
            }
        ];
        
        await db.write();
        console.log('✅ База данных инициализирована с тестовыми данными');
    }
    
    return db;
}

module.exports = { db, initializeDatabase };