const express = require('express');
const bcrypt = require('bcrypt');
const bodyParser = require('body-parser');
const db = require('./database'); // Your database connection and query functions

const app = express();
const port = 3000;

app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());
app.use(express.static(__dirname)); // Serve static files

app.post('/signup', async (req, res) => {
    const { firstName, lastName, email, password, role } = req.body;

    try {
        // 1. Check if the user with the given email already exists (using a secure query)
        const existingUsers = await db.query('SELECT * FROM users WHERE email = ?', [email]);
        if (existingUsers.length > 0) {
            return res.status(409).send('User with this email already exists');
        }

        // 2. Hash the password using bcrypt
        const saltRounds = 10;
        const hashedPassword = await bcrypt.hash(password, saltRounds);

        // 3. Insert the new user data into the database (using a secure query)
        const result = await db.query(
            'INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)',
            [firstName, lastName, email, hashedPassword, role]
        );

        console.log('New user created:', result);
        res.status(201).send('User registered successfully!');
    } catch (error) {
        console.error('Error during signup:', error);
        res.status(500).send('Error during registration');
    }
});

app.post('/signin', async (req, res) => {
    const { email, password, role } = req.body;

    try {
        // 1. Find the user in the database based on email and role (using a secure query)
        const users = await db.query('SELECT * FROM users WHERE email = ? AND role = ?', [email, role]);
        if (users.length === 0) {
            return res.status(401).send('Invalid credentials');
        }

        const user = users[0];

        // 2. Compare the provided password with the hashed password from the database
        const passwordMatch = await bcrypt.compare(password, user.password);

        if (passwordMatch) {
            // Authentication successful
            console.log('User logged in:', user.email, user.role);
            // In a real application, implement session management here
            res.status(200).send('Login successful!');
        } else {
            res.status(401).send('Invalid credentials');
        }
    } catch (error) {
        console.error('Error during signin:', error);
        res.status(500).send('Error during login');
    }
});

app.listen(port, () => {
    console.log(`Server is running on http://localhost:${port}`);
});