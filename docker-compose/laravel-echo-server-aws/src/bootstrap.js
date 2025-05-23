const express = require('express');
const { spawn } = require('child_process');

const app = express();
const port = 80;

// Add /health endpoint
app.get('/health', (req, res) => {
    res.status(200).send('ok');
});

app.listen(port, () => {
    console.log(`Health check server listening on port ${port}`);

    // Start Echo server after health server is ready
    const echo = spawn('laravel-echo-server', [
        'start',
        '--force',
        '--config',
        '/app/docker-compose/laravel-echo-server/laravel-echo-server.json'
    ], {
        stdio: 'inherit',
    });

    echo.on('close', (code) => {
        console.log(`Laravel Echo Server exited with code ${code}`);
        process.exit(code);
    });
});
