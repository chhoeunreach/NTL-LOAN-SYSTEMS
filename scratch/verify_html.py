import urllib.request

url = 'http://127.0.0.1:8000/customer/login'
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
try:
    with urllib.request.urlopen(req) as res:
        content = res.read().decode('utf-8')
        print("Status code:", res.status)
        checks = ['id="demoBox"', 'id="btnQuickFillDemo"', 'id="demoPhoneDisplay"', '010111001', 'password', 'Click to Copy & Auto-Fill Demo']
        for c in checks:
            print(f"Check '{c}':", c in content)
        
        # Also print snippet of demoBox
        if 'id="demoBox"' in content:
            idx = content.find('id="demoBox"')
            print("\nSnippet around demoBox:")
            print(content[idx-50:idx+600])
except Exception as e:
    print("Error:", e)
