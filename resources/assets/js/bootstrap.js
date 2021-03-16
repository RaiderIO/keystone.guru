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
/**
 * Translations coupling from server to client.
 */
import messages from './messages';

window.io = require('socket.io-client');

window.startEcho = function () {
    window.Echo = new Echo({
        broadcaster: 'socket.io',
        host: window.location.hostname
    });
};

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
window.GestureHandling = require('leaflet-gesture-handling');
// window.interpolate = require('color-interpolate');
window.gju = require('geojson-utils');
window.bootstrapselect = require('bootstrap-select');
window.Handlebars = require('handlebars');
window.barrating = require('jquery-bar-rating');
window.polylinedecorator = require('leaflet-polylinedecorator');
window.owlCarousel = require('owl.carousel');
window.introjs = require('intro.js');
window.pwstrengthmeter = require('password-strength-meter');
window.jqueryMousewheel = require('jquery-mousewheel');
window.Cookies = require('js-cookie');
window.hull = require('hull.js'); // Find the 'hull' of a random set of points
window.Offset = require('polygon-offset'); // Offsetting polygons to get a smooth padding around them
window.Lang = require('lang.js'); // Javascript translations
window.d3 = require('d3'); // v3.5.14 since Pather uses an out-of-date version
window.Pather = require('leaflet-pather');
window.circleMenu = require('zikes-circlemenu');
window.Noty = require('noty');
window.Pickr = require('@simonwep/pickr');
window.AntPath = require('leaflet-ant-path');
window.Grapick = require('grapick');
window.jqueryVisible = require('jquery-visible');
window.simplebar = require('simplebar');
window.Draggable = require('@shopify/draggable');
window.autocomplete = require('bootstrap-4-autocomplete');
window.toggle = require('bootstrap4-toggle');
window.jarallax = require('jarallax/dist/jarallax.min');
window.swipe = require('jquery-touchswipe');
window.lazysizes = require('lazysizes');
window.ionRangeSlider = require('ion-rangeslider');

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
window.lang = new Lang({messages});

// https://stackoverflow.com/questions/13046401/how-to-set-selected-select-option-in-handlebars-template
window.Handlebars.registerHelper('select', function (value, options) {
    var $el = $('<select />').html(options.fn(this));
    $el.find('[value="' + value + '"]').attr({'selected': 'selected'});
    return $el.html();
});

// https://stackoverflow.com/questions/8853396/logical-operator-in-a-handlebars-js-if-conditional
window.Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
    switch (operator) {
        case '==':
            return (v1 == v2) ? options.fn(this) : options.inverse(this);
        case '===':
            return (v1 === v2) ? options.fn(this) : options.inverse(this);
        case '!=':
            return (v1 != v2) ? options.fn(this) : options.inverse(this);
        case '!==':
            return (v1 !== v2) ? options.fn(this) : options.inverse(this);
        case '<':
            return (v1 < v2) ? options.fn(this) : options.inverse(this);
        case '<=':
            return (v1 <= v2) ? options.fn(this) : options.inverse(this);
        case '>':
            return (v1 > v2) ? options.fn(this) : options.inverse(this);
        case '>=':
            return (v1 >= v2) ? options.fn(this) : options.inverse(this);
        case '&&':
            return (v1 && v2) ? options.fn(this) : options.inverse(this);
        case '||':
            return (v1 || v2) ? options.fn(this) : options.inverse(this);
        default:
            return options.inverse(this);
    }
});