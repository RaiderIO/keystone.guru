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
    host: window.location.hostname
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
window.Handlebars = require('handlebars');
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
window.Lang = require('lang.js'); // Javascript translations
window.d3 = require('d3'); // v3.5.14 since Pather uses an out-of-date version
window.Pather = require('leaflet-pather');
window.circleMenu = require('zikes-circlemenu');
window.Noty = require('noty');

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


/**
 * Translations coupling from server to client.
 */
import messages from './messages';
window.lang = new Lang({ messages });

// https://stackoverflow.com/questions/13046401/how-to-set-selected-select-option-in-handlebars-template
window.Handlebars.registerHelper('select', function( value, options ){
    var $el = $('<select />').html( options.fn(this) );
    $el.find('[value="' + value + '"]').attr({'selected':'selected'});
    return $el.html();
});