const { spawn } = require('child_process');
const fs = require('fs');

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const fileUrl = 'file:///C:/xampp/htdocs/apply like facebook/LoanManagement/scratch/test_active_styles.html';

async function main() {
  const proc = spawn(edgePath, [
    '--remote-debugging-port=9222',
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    '--window-size=600,600',
    fileUrl
  ]);

  try {
    await new Promise(r => setTimeout(r, 2000));
    const listRes = await fetch('http://127.0.0.1:9222/json/list');
    const tabs = await listRes.json();
    const tab = tabs.find(t => t.type === 'page');
    if (!tab) throw new Error('No page tab');

    const ws = new WebSocket(tab.webSocketDebuggerUrl);
    await new Promise((res, rej) => { ws.onopen = res; ws.onerror = rej; });

    const send = (method, params = {}) => new Promise((resolve, reject) => {
      const id = Math.floor(Math.random() * 100000);
      const handler = (event) => {
        const msg = JSON.parse(event.data);
        if (msg.id === id) {
          ws.removeEventListener('message', handler);
          if (msg.error) reject(msg.error);
          else resolve(msg.result);
        }
      };
      ws.addEventListener('message', handler);
      ws.send(JSON.stringify({ id, method, params }));
    });

    await send('Page.enable');
    const shot = await send('Page.captureScreenshot', { format: 'png' });
    fs.writeFileSync('scratch/sidebar_test_screenshot.png', Buffer.from(shot.data, 'base64'));
    console.log('Saved scratch/sidebar_test_screenshot.png');
    ws.close();
  } finally {
    proc.kill();
  }
}

main().catch(console.error);
