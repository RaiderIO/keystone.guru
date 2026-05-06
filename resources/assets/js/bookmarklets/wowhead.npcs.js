(async function () {
    try {
        const response = await fetch('http://localhost:8008/webhook/wowhead/npc', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                url: window.location.href,
                title: document.title,
                html: document.documentElement.outerHTML
            })
        });

        const data = await response.json();

        if (!response.ok) {
            showMessage('Import failed: ' + (data.message || response.status));
            return;
        }

        showMessage('Imported npc data ' + data.id);

        /*if (response.ok) {
            window.close();
        }*/
    } catch (error) {
        showMessage('Import failed: ' + error.message);
    }
})();

function showMessage(text) {
    const el = document.createElement('div');
    el.textContent = text;
    el.style.position = 'fixed';
    el.style.top = '20px';
    el.style.right = '20px';
    el.style.zIndex = '999999';
    el.style.padding = '12px 16px';
    el.style.background = '#111';
    el.style.color = '#fff';
    el.style.border = '1px solid #555';
    el.style.borderRadius = '6px';
    document.body.appendChild(el);

    setTimeout(() => el.remove(), 3000);
}
