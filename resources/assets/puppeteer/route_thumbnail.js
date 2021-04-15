const puppeteer = require('puppeteer');

// process.argv
// 0: node path
// 1: script path
// 2: target web page
// 3: resulting screenshot location
function delay(timeout) {
    return new Promise((resolve) => {
        setTimeout(resolve, timeout);
    });
}


(async () => {
    const browser = await puppeteer.launch({
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();

    await page.setViewport({width: 768, height: 512});
    await page.goto(process.argv[2]);
    await delay(5000);
    await page.screenshot({path: process.argv[3]});

    await browser.close();
})();