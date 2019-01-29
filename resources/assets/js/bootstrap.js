window._ = require('lodash');

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

window.$ = window.jQuery = require('jquery');
window.Popper = require('popper.js').default;

require('bootstrap');

/**
 * Vue is a modern JavaScript library for building interactive web interfaces
 * using reactive data binding and reusable components. Vue's API is clean
 * and simple, leaving you to focus on building your next great project.
 */

// window.Vue = require('vue');

/**
 * Echo server
 */
import Echo from 'laravel-echo'

window.io = require('socket.io-client');
window.Echo = new Echo({
    broadcaster: 'socket.io',
    host: window.location.hostname + ':6001'
});

window.Echo.channel('test-event')
    .listen('ExampleEvent', (e) => {
        console.log(e);
    });

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

window.axios = require('axios');

window.datatables = require('datatables');
window.leaflet = require('leaflet');
window.leafletdraw = require('leaflet-draw');
// window.leafleteditable = require('leaflet-editable');
window.leafletcontextmenu = require('leaflet-contextmenu');
// window.interpolate = require('color-interpolate');
window.gju = require('geojson-utils');
window.bootstrapselect = require('bootstrap-select');
window.handlebars = require('handlebars');
window.barrating = require('jquery-bar-rating');
window.polylinedecorator = require('leaflet-polylinedecorator');
window.owlCarousel = require('owl.carousel');
window.introjs = require('intro.js');
window.pwstrengthmeter = require('password-strength-meter');
window.jqueryMousewheel = require('jquery-mousewheel');
window.mCustomScrollbar = require('malihu-custom-scrollbar-plugin');
window.Cookies = require('js-cookie');
window.hull = require('hull.js'); // Find the 'hull' of a random set of points
window.Offset = require('polygon-offset'); // Offsetting polygons to get a smooth padding around them

require('@fortawesome/fontawesome-free');

window.axios.defaults.headers.common = {
    'X-Requested-With': 'XMLHttpRequest'
};

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from "laravel-echo"

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: 'your-pusher-key'
// });
