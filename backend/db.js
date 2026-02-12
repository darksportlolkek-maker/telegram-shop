const mysql = require('mysql2');

const db = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: 'Daniel2006',
  database: 'danoon_shop'
});

db.connect(err => {
  if (err) {
    console.error('❌ Ошибка MySQL', err);
  } else {
    console.log('✅ MySQL подключен');
  }
});

module.exports = db;
