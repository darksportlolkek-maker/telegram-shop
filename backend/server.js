const express = require('express');
const cors = require('cors');
const db = require('./db');

const app = express();
app.use(cors());
app.use(express.json());

/* Получить все товары */
app.get('/api/products', (req, res) => {
    db.query('SELECT * FROM products', (err, results) => {
        if (err) {
            console.error(err);
            return res.status(500).json({ error: 'DB error' });
        }
        res.json(results);
    });
});

/* Запуск сервера */
app.listen(3000, () => {
    console.log('✅ Backend запущен: http://localhost:3000');
});
