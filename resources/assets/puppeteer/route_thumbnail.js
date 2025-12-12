const puppeteer = require('puppeteer');

// process.argv
// 0: node path
// 1: script path
// 2: target web page
// 3: resulting screenshot location
// 4: viewport width
// 5: viewport height
function delay(timeout) {
    return new Promise((resolve) => {
        setTimeout(resolve, timeout);
    });
}


(async () => {
    let startTime = new Date().getTime();
    console.log('Creating browser');
    const browser = await puppeteer.launch({
        headless: 'new',
        args: ['--no-sandbox'],
        userDataDir: '/dev/null'
    });

    try {
        const page = await browser.newPage();
        // Output console to stdout
        // page
        //     .on('console', message =>
        //         console.log(`${message.type().substr(0, 3).toUpperCase()} ${message.text()}`))
        //     .on('pageerror', ({message}) => console.log(message))
        //     .on('response', response =>
        //         console.log(`${response.status()} ${response.url()}`))
        //     .on('requestfailed', request =>
        //         console.log(`${request.failure().errorText} ${request.url()}`));

        // Force facade for thumbnails
        await page.setCookie({
            name: 'map_facade_style',
            value: 'facade',
            domain: new URL(process.argv[2]).hostname
        });

        await page.setViewport({width: Math.max(process.argv[4] ?? 0, 768), height: Math.max(process.argv[5] ?? 0, 512)});

        console.log(`Navigating to ${process.argv[2]}`);
        await page.goto(process.argv[2]);

        console.log('Waiting for page to load fully');
        await page.waitForSelector('#finished_loading', {timeout: 10000});

        console.log('Waiting for animations to complete');
        await delay(500);

        console.log('Taking screenshot');
        await page.screenshot({path: process.argv[3]});
    } finally {
        await browser.close();
        let time = new Date().getTime() - startTime;
        console.log(`Finished in ${time}ms!`);
    }
})();
