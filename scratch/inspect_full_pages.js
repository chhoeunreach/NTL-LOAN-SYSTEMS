const { spawn } = require('child_process');

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

async function inspectUrl(fileUrl) {
  const proc = spawn(edgePath, [
    '--remote-debugging-port=9222',
    '--headless=new',
    '--disable-gpu',
    '--no-first-run',
    '--no-default-browser-check',
    fileUrl
  ]);

  try {
    await new Promise(r => setTimeout(r, 2000));
    const listRes = await fetch('http://127.0.0.1:9222/json/list');
    const tabs = await listRes.json();
    const tab = tabs.find(t => t.type === 'page');
    if (!tab) throw new Error('No page tab found');

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

    await send('Runtime.enable');
    const res = await send('Runtime.evaluate', {
      expression: `(() => {
        const link = document.querySelector('#loanManagementSidebar .lm-menu-link.active');
        if (!link) return { error: 'link not found' };
        const cs = window.getComputedStyle(link);
        const rect = link.getBoundingClientRect();
        const icon = link.querySelector('.lm-menu-icon');
        const ics = icon ? window.getComputedStyle(icon) : {};
        const irect = icon ? icon.getBoundingClientRect() : {};
        const label = link.querySelector('.lm-menu-label');
        const lcs = label ? window.getComputedStyle(label) : {};
        const lrect = label ? label.getBoundingClientRect() : {};

        return {
          rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height },
          styles: {
            boxSizing: cs.boxSizing,
            padding: \`\${cs.paddingTop} \${cs.paddingRight} \${cs.paddingBottom} \${cs.paddingLeft}\`,
            margin: \`\${cs.marginTop} \${cs.marginRight} \${cs.marginBottom} \${cs.marginLeft}\`,
            border: \`\${cs.borderTopWidth} \${cs.borderTopStyle} \${cs.borderTopColor}\`,
            borderLeft: \`\${cs.borderLeftWidth} \${cs.borderLeftStyle} \${cs.borderLeftColor}\`,
            borderRadius: cs.borderRadius,
            boxShadow: cs.boxShadow,
            lineHeight: cs.lineHeight,
            fontSize: cs.fontSize,
            fontFamily: cs.fontFamily,
            fontWeight: cs.fontWeight,
            color: cs.color,
            background: cs.background
          },
          icon: {
            className: icon ? icon.className : '',
            rect: { x: irect.x, y: irect.y, width: irect.width, height: irect.height },
            paddingLeft: ics.paddingLeft,
            marginLeft: ics.marginLeft,
            width: ics.width,
            fontSize: ics.fontSize,
            lineHeight: ics.lineHeight
          },
          label: {
            text: label ? label.textContent.trim() : '',
            rect: { x: lrect.x, y: lrect.y, width: lrect.width, height: lrect.height },
            fontSize: lcs.fontSize,
            lineHeight: lcs.lineHeight
          }
        };
      })()`,
      returnByValue: true
    });

    ws.close();
    return res.result.value;
  } finally {
    proc.kill();
  }
}

async function main() {
  console.log("=== INSPECTING ADMIN LOAN PAGE ===");
  const r1 = await inspectUrl('file:///C:/xampp/htdocs/apply like facebook/LoanManagement/scratch/full_admin_loan.html');
  console.log(JSON.stringify(r1, null, 2));

  await new Promise(r => setTimeout(r, 1000));

  console.log("\n=== INSPECTING DASHBOARD REPORTS PAGE ===");
  const r2 = await inspectUrl('file:///C:/xampp/htdocs/apply like facebook/LoanManagement/scratch/full_dashboard_reports.html');
  console.log(JSON.stringify(r2, null, 2));
}

main().catch(console.error);
