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
    let startTime = new Date().getTime();
    console.log('Creating browser');
    const browser = await puppeteer.launch({
        args: ['--no-sandbox']
    });
    const page = await browser.newPage();
    await page.setViewport({width: 768, height: 512});

    console.log(`Navigating to ${process.argv[2]}`);
    await page.goto(process.argv[2]);

    console.log('Waiting for page to load fully');
    await page.waitForSelector('#finished_loading', {timeout: 5000});

    console.log('Waiting for animations to complete');
    await delay(500);

    console.log('Taking screenshot');
    await page.screenshot({path: process.argv[3]});

    await browser.close();
    let time = new Date().getTime() - startTime;
    console.log(`Finished in ${time}ms!`);
})();
