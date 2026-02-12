const express = require('express');
const cors = require('cors');
const db = require('./db');

const app = express();
app.use(cors());
app.use(express.json());

/* Получить товары */
app.get('/products', (req, res) => {
  db.query('SELECT * FROM products', (err, results) => {
    if (err) return res.status(500).json(err);
    res.json(results);
  });
});

/* Создать заказ */
app.post('/order', (req, res) => {
  const { telegram_id, cart, total } = req.body;

  db.query(
    'INSERT INTO orders (telegram_id, total) VALUES (?, ?)',
    [telegram_id, total],
    (err, result) => {
      if (err) return res.status(500).json(err);

      const orderId = result.insertId;

      cart.forEach(item => {
        db.query(
          'INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)',
          [orderId, item.id, item.quantity, item.price]
        );
      });

      res.json({ success: true, orderId });
    }
  );
});

app.listen(3000, () => {
  console.log('🚀 Server started on http://localhost:3000');
});
