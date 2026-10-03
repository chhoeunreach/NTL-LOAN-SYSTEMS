const { spawn } = require('child_process');
const http = require('http');

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const htmlFile = 'file:///C:/xampp/htdocs/apply like facebook/LoanManagement/scratch/inspect_rendered.html';

const proc = spawn(edgePath, [
  '--remote-debugging-port=9222',
  '--headless=new',
  '--disable-gpu',
  htmlFile
]);

setTimeout(async () => {
  try {
    const listRes = await fetch('http://127.0.0.1:9222/json/list');
    const tabs = await listRes.json();
    console.log('Tabs:', tabs.length);
    if (tabs.length > 0) {
      const wsUrl = tabs[0].webSocketDebuggerUrl;
      const WebSocket = require('stream'); // wait, standard node doesn't have ws built-in
    }
  } catch (err) {
    console.error(err);
  } finally {
    proc.kill();
  }
}, 2000);
