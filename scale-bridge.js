import { WebSocketServer } from 'ws';
import net from 'net';
import url from 'url';

const PORT = 8090;
const wss = new WebSocketServer({ port: PORT });

console.log(`[Bridge] WebSocket Bridge Server is running on port ${PORT}...`);

wss.on('connection', (ws, req) => {
    const parsedUrl = url.parse(req.url, true);
    const targetIp = parsedUrl.query.targetIp;
    const targetPort = parseInt(parsedUrl.query.targetPort) || 4001;

    if (!targetIp) {
        console.log(`[Bridge] Connection rejected: targetIp is missing.`);
        ws.close(4000, "targetIp is required");
        return;
    }

    console.log(`[Bridge] Client connecting -> Proxying to scale at ${targetIp}:${targetPort}`);

    // Create raw TCP connection to Moxa NPort
    const tcpSocket = new net.Socket();

    // Set connection timeout to 10 seconds
    tcpSocket.setTimeout(10000);

    tcpSocket.connect(targetPort, targetIp, () => {
        console.log(`[Bridge] Successfully connected to scale at ${targetIp}:${targetPort}`);
    });

    // Handle data from scale (TCP) -> send to browser (WS)
    tcpSocket.on('data', (data) => {
        if (ws.readyState === ws.OPEN) {
            const rawStr = data.toString('ascii');
            ws.send(rawStr);
        }
    });

    // Handle data from browser (WS) -> send to scale (TCP)
    ws.on('message', (message) => {
        if (tcpSocket.writable) {
            // ws.onmessage can receive Buffer or String
            const msgStr = typeof message === 'string' ? message : message.toString();
            tcpSocket.write(msgStr);
        }
    });

    // Timeout handling
    tcpSocket.on('timeout', () => {
        console.log(`[Bridge] TCP connection timeout to ${targetIp}:${targetPort}`);
        tcpSocket.destroy();
    });

    // Error handling
    tcpSocket.on('error', (err) => {
        console.error(`[Bridge] TCP socket error with scale ${targetIp}:${targetPort}:`, err.message);
        ws.close(4001, `TCP connection error: ${err.message}`);
    });

    ws.on('error', (err) => {
        console.error(`[Bridge] WebSocket client error:`, err.message);
        tcpSocket.destroy();
    });

    // Connection closed
    tcpSocket.on('close', () => {
        console.log(`[Bridge] TCP connection to scale ${targetIp}:${targetPort} closed.`);
        ws.close();
    });

    ws.on('close', () => {
        console.log(`[Bridge] WebSocket client disconnected.`);
        tcpSocket.destroy();
    });
});
