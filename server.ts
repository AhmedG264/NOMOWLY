import { spawn } from 'node:child_process';

const port = process.env.PORT || '3000';
console.log(`[Nomowly Server] Starting Laravel application on port ${port}...`);

const php = spawn('php', ['artisan', 'serve', '--host=0.0.0.0', `--port=${port}`], {
    stdio: 'inherit',
    env: process.env,
});

php.on('error', (err) => {
    console.error('[Nomowly Server] Failed to start PHP server:', err);
    process.exit(1);
});

php.on('exit', (code, signal) => {
    console.log(`[Nomowly Server] PHP process exited with code ${code} signal ${signal}`);
    process.exit(code || 0);
});

process.on('SIGINT', () => {
    php.kill('SIGINT');
});

process.on('SIGTERM', () => {
    php.kill('SIGTERM');
});
