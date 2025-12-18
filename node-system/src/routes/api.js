const express = require('express');
const router = express.Router();
const integrationController = require('../controllers/integration');
const digitalController = require('../controllers/digital');

router.get('/health', (req, res) => {
    res.json({ 
        status: 'OK', 
        timestamp: new Date().toISOString(),
        service: 'Library REST API v1.0'
    });
});

router.get('/physical/test', integrationController.testConnection);

router.get('/physical/books', integrationController.getBooks);
router.get('/physical/books/:inventory', integrationController.getBookByInventory);
router.get('/physical/books/author/:author', integrationController.getBooksByAuthor);
router.post('/physical/loan', integrationController.registerLoan);
router.post('/physical/return', integrationController.returnBook);

router.get('/digital', digitalController.getAll);
router.get('/digital/:id', digitalController.getById);
router.post('/digital', digitalController.create);
router.put('/digital/:id', digitalController.update);
router.delete('/digital/:id', digitalController.delete);

module.exports = router;