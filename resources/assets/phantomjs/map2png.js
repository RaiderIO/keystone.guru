var page = require("webpage").create();
var url = "https://dev.keystone.guru/DDhw5Cc/preview";
var output = 'public/phantomjs/map.png';

console.log('fetch ' + url);

page.viewportSize = {
  width: 200,
  height: 120
};

page.open(url, function (status) {
    if (status !== 'success') {
        console.log('Unable to load the address!');
        phantom.exit();
    }
    // else {
    //     window.setTimeout(function () {
    //         page.render(output);
    //         phantom.exit();
    //     }, 1000); // Change timeout as required to allow sufficient time
    // }
});

page.onLoadFinished = function(status){
    console.log(status);
    page.render(output);
    phantom.exit();
};

// function onPageReady() {
//     console.log('onPageReady');
//     page.render(output);
//
//     phantom.exit();
// }
//
// function checkReadyState() {
//     console.log('checkReadyState');
//     setTimeout(function () {
//         var readyState = page.evaluate(function () {
//             return document.readyState;
//         });
//         console.log(readyState);
//
//         if ("complete" === readyState) {
//             onPageReady();
//         } else {
//             checkReadyState();
//         }
//     }, 1000);
// }
//
// console.log('opening..');
// page.open(url, function (status) {
//     console.log('fn -> ' + status);
//
//     checkReadyState();
// });
// console.log('opened!');

