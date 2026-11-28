const PORT = 8001;
const { WebSocketServer } = require('ws');
const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');
const crypto = require('crypto');

function loadJwtSecret() {
    if (process.env.JWT_SECRET) {
        return process.env.JWT_SECRET.trim();
    }
    const envPath = path.resolve(__dirname, '../.env');
    if (fs.existsSync(envPath)) {
        const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/);
        for (const line of lines) {
            const match = line.match(/^\s*JWT_SECRET\s*=\s*(.+)\s*$/);
            if (match) {
                return match[1].replace(/^['"]|['"]$/g, '').trim();
            }
        }
    }
    return '';
}

const JWT_SECRET = loadJwtSecret();

function base64UrlDecode(str) {
    const padded = str.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - (str.length % 4)) % 4);
    return Buffer.from(padded, 'base64');
}

function verifyJwt(token) {
    if (!token || typeof token !== 'string' || !JWT_SECRET) {
        return null;
    }

    const parts = token.split('.');
    if (parts.length !== 3) {
        return null;
    }

    const [encodedHeader, encodedPayload, encodedSignature] = parts;

    try {
        const header = JSON.parse(base64UrlDecode(encodedHeader).toString('utf8'));
        if (header.alg !== 'HS256') {
            return null;
        }

        const expectedSig = crypto
            .createHmac('sha256', JWT_SECRET)
            .update(`${encodedHeader}.${encodedPayload}`)
            .digest();

        const actualSig = base64UrlDecode(encodedSignature);
        if (expectedSig.length !== actualSig.length || !crypto.timingSafeEqual(expectedSig, actualSig)) {
            return null;
        }

        const payload = JSON.parse(base64UrlDecode(encodedPayload).toString('utf8'));
        const now = Math.floor(Date.now() / 1000);
        if (typeof payload.exp === 'number' && payload.exp < now) {
            return null;
        }

        const userId = Number(payload.sub);
        if (!Number.isInteger(userId) || userId <= 0) {
            return null;
        }

        return { userId, payload };
    } catch (err) {
        return null;
    }
}

// Create HTTP server for local development or HTTPS server for production
let server;

const hasCerts = fs.existsSync('./fullchain.pem') && fs.existsSync('./privkey.pem');

if (process.env.NODE_ENV === 'production' && hasCerts) {
    server = https.createServer({
        cert: fs.readFileSync('./fullchain.pem'),
        key: fs.readFileSync('./privkey.pem'),
    });
} else {
    server = http.createServer();
}

// Create a WebSocket server
const ws = new WebSocketServer({ server });

server.listen(PORT, '0.0.0.0', () => {
    console.log(`WebSocket server running on port ${PORT}`);
});

let ID = 0;
const clients = new Map();

ws.on("connection", (client, request) => {
    client.id = ID++;
    client.authenticatedUserId = null;

    // Optional handshake token (?token=<jwt>)
    try {
        const reqUrl = new URL(request.url || '/', 'http://localhost');
        const queryToken = reqUrl.searchParams.get('token');
        if (queryToken) {
            const verified = verifyJwt(queryToken);
            if (!verified) {
                client.send(JSON.stringify({ type: 'error', message: 'Unauthorized: invalid token' }));
                client.close(4001, 'Unauthorized');
                return;
            }
            client.authenticatedUserId = verified.userId;
        }
    } catch (err) {
        client.close(4001, 'Unauthorized');
        return;
    }

    console.log(`new client: ${client.id} (authenticatedUserId=${client.authenticatedUserId ?? 'pending'})`);
    client.send(JSON.stringify({ message: "Hello from server" }));

    client.on("message", (message) => {
        let parsedMessage;
        try {
            parsedMessage = JSON.parse(message);
        } catch (error) {
            console.error('Invalid JSON received:', message);
            return;
        }

        if (parsedMessage.type === 'connect') {
            if (!client.authenticatedUserId) {
                const verified = verifyJwt(parsedMessage.token);
                if (!verified) {
                    client.send(JSON.stringify({ type: 'error', message: 'Unauthorized: invalid or missing JWT' }));
                    client.close(4001, 'Unauthorized');
                    return;
                }
                client.authenticatedUserId = verified.userId;
            }

            clients.set(client.authenticatedUserId, {
                client, 
                channelId: parsedMessage.channelId,
                JobPostId: parsedMessage.JobPostId
            });
        } else if (parsedMessage.type === 'message') {
            if (!client.authenticatedUserId) {
                client.send(JSON.stringify({ type: 'error', message: 'Unauthorized: authenticate before sending messages' }));
                client.close(4001, 'Unauthorized');
                return;
            }

            const targetChannelId = Number(parsedMessage.channelId);
            const responseMessage = {
                type: 'message',
                userId: client.authenticatedUserId,
                message: parsedMessage.message,
                channelId: targetChannelId,
                JobPostId: parsedMessage.JobPostId,
            };

            const targetClient = clients.get(targetChannelId);
            if (targetClient && targetClient.client.readyState === 1) {
                targetClient.client.send(JSON.stringify(responseMessage));
            }
        }
    });

    client.on("close", () => {
        console.log(`client disconnected: ${client.id}`);
        for (const [userId, clientInfo] of clients) {
            if (clientInfo.client === client) {
                clients.delete(userId);
                break;
            }
        }
    });
});
