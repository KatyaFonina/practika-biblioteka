const { db, initializeDatabase } = require('../database/db');
const { v4: uuidv4 } = require('uuid');

class DigitalController {
    constructor() {
        this.initialize();
    }

    async initialize() {
        await initializeDatabase();
    }

    async getAll(req, res) {
        await db.read();
        res.json({
            success: true,
            count: db.data.digitalResources.length,
            data: db.data.digitalResources
        });
    }

    async getById(req, res) {
        const { id } = req.params;
        await db.read();
        
        const resource = db.data.digitalResources.find(r => r.id === id);
        
        if (resource) {
            res.json({ success: true, data: resource });
        } else {
            res.status(404).json({ success: false, error: 'Resource not found' });
        }
    }

    async create(req, res) {
        const { title, type, format, size, duration } = req.body;
        
        if (!title || !type) {
            return res.status(400).json({ 
                success: false, 
                error: 'Title and type are required' 
            });
        }

        const newResource = {
            id: uuidv4(),
            title,
            type,
            format: format || 'unknown',
            size: size || 'unknown',
            duration: duration || 'unknown',
            createdAt: new Date().toISOString(),
            updatedAt: new Date().toISOString()
        };

        await db.read();
        db.data.digitalResources.push(newResource);
        await db.write();

        res.status(201).json({ success: true, data: newResource });
    }

    async update(req, res) {
        const { id } = req.params;
        const updates = req.body;
        
        await db.read();
        const index = db.data.digitalResources.findIndex(r => r.id === id);
        
        if (index === -1) {
            return res.status(404).json({ success: false, error: 'Resource not found' });
        }

        db.data.digitalResources[index] = {
            ...db.data.digitalResources[index],
            ...updates,
            updatedAt: new Date().toISOString()
        };

        await db.write();
        res.json({ success: true, data: db.data.digitalResources[index] });
    }

    async delete(req, res) {
        const { id } = req.params;
        
        await db.read();
        const initialLength = db.data.digitalResources.length;
        db.data.digitalResources = db.data.digitalResources.filter(r => r.id !== id);
        
        if (db.data.digitalResources.length === initialLength) {
            return res.status(404).json({ success: false, error: 'Resource not found' });
        }

        await db.write();
        res.json({ success: true, message: 'Resource deleted successfully' });
    }
}

module.exports = new DigitalController();