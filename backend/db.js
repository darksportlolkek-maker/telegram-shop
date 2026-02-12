const mysql = require('mysql2');

const db = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: 'Daniel2006',
    database: 'danoon_shop'
});

module.exports = db;
