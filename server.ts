import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';

const port = Number(process.env.PORT) || 3000;
let serverStarted = false;

// MIME types for static assets (pure JavaScript object)
const MIME_TYPES = {
    '.html': 'text/html; charset=utf-8',
    '.css': 'text/css; charset=utf-8',
    '.js': 'application/javascript; charset=utf-8',
    '.json': 'application/json; charset=utf-8',
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.svg': 'image/svg+xml',
    '.ico': 'image/x-icon',
    '.zip': 'application/zip',
    '.tar.gz': 'application/gzip',
    '.md': 'text/markdown; charset=utf-8',
    '.txt': 'text/plain; charset=utf-8',
};

function startNodeHttpServer() {
    if (serverStarted) return;
    serverStarted = true;

    console.log(`[Nomowly Server] Initializing production HTTP engine on 0.0.0.0:${port}...`);

    const server = http.createServer((req, res) => {
        try {
            const rawUrl = req.url || '/';
            const urlPath = rawUrl.split('?')[0];

            // Healthcheck endpoint for Cloud Run
            if (urlPath === '/health' || urlPath === '/up') {
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({ status: 'ok', service: 'Nomowly Platform', time: new Date().toISOString() }));
                return;
            }

            // Direct deployment package download
            if (urlPath === '/nomowly-hostinger-deployment.zip' || urlPath === '/download') {
                const zipPath = path.resolve(process.cwd(), 'nomowly-hostinger-deployment.zip');
                if (fs.existsSync(zipPath)) {
                    const stat = fs.statSync(zipPath);
                    res.writeHead(200, {
                        'Content-Type': 'application/zip',
                        'Content-Length': stat.size,
                        'Content-Disposition': 'attachment; filename="nomowly-hostinger-deployment.zip"',
                    });
                    fs.createReadStream(zipPath).pipe(res);
                    return;
                }
            }

            // Hostinger Deployment Guide
            if (urlPath === '/HOSTINGER_DEPLOYMENT.md' || urlPath === '/guide') {
                const guidePath = path.resolve(process.cwd(), 'HOSTINGER_DEPLOYMENT.md');
                if (fs.existsSync(guidePath)) {
                    res.writeHead(200, { 'Content-Type': 'text/markdown; charset=utf-8' });
                    fs.createReadStream(guidePath).pipe(res);
                    return;
                }
            }

            // Map routes to pre-rendered HTML files
            const routeMap = {
                '/': 'public/index.html',
                '/marketplace': 'public/marketplace.html',
                '/login': 'public/login.html',
                '/register': 'public/register.html',
                '/brands': 'public/brands.html',
                '/marketers': 'public/marketers.html',
            };

            let targetFile = '';
            if (routeMap[urlPath]) {
                targetFile = path.resolve(process.cwd(), routeMap[urlPath]);
            } else {
                // Check if file exists in public/
                const cleanPath = urlPath.replace(/^\/+/, '');
                const candidatePublic = path.resolve(process.cwd(), 'public', cleanPath);
                if (fs.existsSync(candidatePublic) && fs.statSync(candidatePublic).isFile()) {
                    targetFile = candidatePublic;
                } else {
                    targetFile = path.resolve(process.cwd(), 'public/index.html');
                }
            }

            if (fs.existsSync(targetFile) && fs.statSync(targetFile).isFile()) {
                const ext = path.extname(targetFile).toLowerCase();
                const contentType = MIME_TYPES[ext] || 'application/octet-stream';
                const stat = fs.statSync(targetFile);

                res.writeHead(200, {
                    'Content-Type': contentType,
                    'Content-Length': stat.size,
                    'Cache-Control': ext === '.html' ? 'no-cache' : 'public, max-age=31536000, immutable',
                });
                fs.createReadStream(targetFile).pipe(res);
            } else {
                res.writeHead(404, { 'Content-Type': 'text/plain; charset=utf-8' });
                res.end('Not Found');
            }
        } catch (e) {
            console.error('[Nomowly Server] Error handling request:', e);
            res.writeHead(500, { 'Content-Type': 'text/plain; charset=utf-8' });
            res.end('Internal Server Error');
        }
    });

    server.listen(port, '0.0.0.0', () => {
        console.log(`[Nomowly Server] Node server successfully listening on 0.0.0.0:${port}`);
    });
}

function bootstrap() {
    console.log(`[Nomowly Server] Starting Nomowly application on port ${port}...`);

    try {
        const php = spawn('php', ['artisan', 'serve', '--host=0.0.0.0', `--port=${port}`], {
            stdio: 'inherit',
            env: process.env,
        });

        php.on('error', (err) => {
            if (err && err.code === 'ENOENT') {
                console.warn('[Nomowly Server] PHP binary not found in container runtime (ENOENT). Activating native Node.js HTTP engine.');
            } else {
                console.warn('[Nomowly Server] PHP spawn failed:', err ? err.message : err);
            }
            startNodeHttpServer();
        });

        php.on('exit', (code, signal) => {
            if (!serverStarted) {
                console.warn(`[Nomowly Server] PHP process exited with code ${code} signal ${signal}. Starting Node.js HTTP fallback.`);
                startNodeHttpServer();
            }
        });

        process.on('SIGINT', () => {
            if (php) php.kill('SIGINT');
            process.exit(0);
        });

        process.on('SIGTERM', () => {
            if (php) php.kill('SIGTERM');
            process.exit(0);
        });
    } catch (err) {
        console.warn('[Nomowly Server] Exception starting PHP process:', err);
        startNodeHttpServer();
    }
}

bootstrap();
