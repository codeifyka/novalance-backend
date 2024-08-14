const PORT = 8001;
const { WebSocketServer } = require('ws');

let ws = new WebSocketServer({ port: PORT, path: '/ws' }, () => {
    console.log(`Server is up running on ${PORT}`);
});

let ID = 0;
const clients = new Map();

ws.on("connection", (client, request) => {
    client.id = ID++;
    console.log(`new client: ${ID}`);

    client.send(JSON.stringify({ message: "Hello from server" }));

    client.on("message", (message) => {
        let parsedMessage;
        try {
            parsedMessage = JSON.parse(message);
        } catch (error) {
            console.error('Invalid JSON received:', message);
            return;
        }
        console.log(parsedMessage);
  
        if (parsedMessage.type === 'connect') {
            // Store the client with their userId, JobPostId, and channelId
            clients.set(parsedMessage.userId, { 
                client, 
                channelId: parsedMessage.channelId,
                JobPostId: parsedMessage.JobPostId
            });
        } else if (parsedMessage.type === 'message') {
            const responseMessage = {
                type: 'message',
                userId: parsedMessage.userId,
                message: parsedMessage.message,
                channelId: parsedMessage.channelId,
                JobPostId: parsedMessage.JobPostId
            };

        // Send the message only to the client with the matching channelId && JobPostId
        for (let [userId, clientInfo] of clients) {
            if (userId === parsedMessage.channelId) {
                    clientInfo.client.send(JSON.stringify(responseMessage));
                }
            }
        }
    });
});

ws.on("close", (client, request) => {
    console.log(`client disconnected: ${client.id}`);
    // Remove the client from the clients Map
    for (let [userId, clientInfo] of clients) {
        if (clientInfo.client === client) {
            clients.delete(userId);
            break;
        }
    }
});