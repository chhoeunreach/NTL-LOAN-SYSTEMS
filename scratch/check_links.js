const fs = require('fs');
const html = fs.readFileSync('scratch/full_admin_loan.html', 'utf8');
console.log('Sidebar match:', html.includes('loanManagementSidebar'));
const m = html.match(/class="[^"]*lm-menu-link[^"]*"/g);
console.log('Links count:', m ? m.length : 0);
console.log('Sample links:', m ? m.slice(0, 5) : []);
