const { spawn } = require('child_process');

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const htmlFile = 'file:///C:/xampp/htdocs/apply like facebook/LoanManagement/scratch/inspect_rendered.html';

const proc = spawn(edgePath, [
  '--remote-debugging-port=9222',
  '--headless=new',
  '--disable-gpu',
  '--no-first-run',
  '--no-default-browser-check',
  htmlFile
]);

async function run() {
  await new Promise(r => setTimeout(r, 1500));
  const listRes = await fetch('http://127.0.0.1:9222/json/list');
  const tabs = await listRes.json();
  const tab = tabs.find(t => t.url.includes('inspect_rendered.html')) || tabs[0];
  if (!tab) throw new Error('No tab found');

  const ws = new WebSocket(tab.webSocketDebuggerUrl);
  await new Promise((resolve, reject) => {
    ws.onopen = resolve;
    ws.onerror = reject;
  });

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

  await send('Runtime.enable');
  const evalResult = await send('Runtime.evaluate', {
    expression: 'document.getElementById("computed-json").textContent',
    returnByValue: true
  });

  console.log("COMPUTED STYLES RESULT:\n" + evalResult.result.value);
  ws.close();
  proc.kill();
}

run().catch(err => {
  console.error(err);
  proc.kill();
  process.exit(1);
});
