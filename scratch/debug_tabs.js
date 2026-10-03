const { spawn } = require('child_process');
const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const fileUrl = 'file:///C:/xampp/htdocs/apply like facebook/LoanManagement/scratch/full_admin_loan.html';

const proc = spawn(edgePath, [
  '--remote-debugging-port=9222',
  '--headless=new',
  '--disable-gpu',
  fileUrl
]);

setTimeout(async () => {
  const listRes = await fetch('http://127.0.0.1:9222/json/list');
  const tabs = await listRes.json();
  console.log('Tabs:', JSON.stringify(tabs, null, 2));
  proc.kill();
}, 2000);
