const mysql = require('mysql');

const connection = mysql.createConnection({
    host: 'localhost',
    user: 'root', // As seen in your SELECT USER(); output
    password: 'mysecretpassword', // Replace with the password you used to log in to MySQL
    database: 'dental_app' // The database you just created
});

connection.connect((err) => {
    if (err) {
        console.error('Error connecting to database:', err);
        return;
    }
    console.log('Connected to the database!');
});

const query = (sql, values) => {
    return new Promise((resolve, reject) => {
        connection.query(sql, values, (error, results) => {
            if (error) {
                reject(error);
                return;
            }
            resolve(results);
        });
    });
};

module.exports = { query };