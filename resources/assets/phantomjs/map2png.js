var page = require("webpage").create();
var url = "https://dev.keystone.guru/DDhw5Cc/preview";

console.log('fetch ' + url);

function onPageReady() {
    console.log('onPageReady');
    page.render('map.png');

    phantom.exit();
}

function checkReadyState() {
    console.log('checkReadyState');
    setTimeout(function () {
        var readyState = page.evaluate(function () {
            return document.readyState;
        });
        console.log(readyState);

        if ("complete" === readyState) {
            onPageReady();
        } else {
            checkReadyState();
        }
    }, 1000);
}

console.log('opening..');
page.open(url, function (status) {
    console.log('fn -> ' + status);

    checkReadyState();
});
console.log('opened!');

