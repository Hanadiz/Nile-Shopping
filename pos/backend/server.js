const express = require('express');
const cors = require('cors');
const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());

app.get('/api/health', (req, res) => {
    res.json({ status: 'ok', message: 'Nile POS API Running' });
});

app.get('/api/products', (req, res) => {
    res.json({
        success: true,
        data: [
            { id: 1, name: 'Premium Notebook', price: 25000, quantity: 50 },
            { id: 2, name: 'Gel Pen Pack', price: 15000, quantity: 100 }
        ]
    });
});

app.post('/api/auth/login', (req, res) => {
    const { email, password } = req.body;
    if (email === 'admin@nileshopping.com' && password === 'Admin123!') {
        res.json({
            success: true,
            data: { token: 'test-token', user: { id: 1, name: 'Admin', email: email } }
        });
    } else {
        res.status(401).json({ success: false, error: 'Invalid credentials' });
    }
});

app.listen(PORT, () => {
    console.log(`✅ Nile POS API running on port ${PORT}`);
});
