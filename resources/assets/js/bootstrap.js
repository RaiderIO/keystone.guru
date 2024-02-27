import _ from 'lodash';
window._ = _;

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

window.$ = window.jQuery = require('jquery');
window.Popper = require('popper.js').default;

import 'bootstrap';

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

import io from 'socket.io-client';
window.io = io;

window.startEcho = function () {
    window.Echo = new Echo({
        broadcaster: 'socket.io',
        host: window.location.host
    });
};

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

import datatables from 'datatables';
window.datatables = datatables;
import leaflet from 'leaflet';
window.leaflet = leaflet;
import leafletdraw from 'leaflet-draw';
window.leafletdraw = leafletdraw;
// window.leafleteditable = require('leaflet-editable');
import leafletcontextmenu from 'leaflet-contextmenu';
window.leafletcontextmenu = leafletcontextmenu;
import GestureHandling from 'leaflet-gesture-handling';
window.GestureHandling = GestureHandling;
// window.interpolate = require('color-interpolate');
import gju from 'geojson-utils';
window.gju = gju;
import bootstrapselect from 'bootstrap-select';
window.bootstrapselect = bootstrapselect;
import Handlebars from 'handlebars';
window.Handlebars = Handlebars;
import barrating from 'jquery-bar-rating';
window.barrating = barrating;
import polylinedecorator from 'leaflet-polylinedecorator';
window.polylinedecorator = polylinedecorator;
import lightCarousel from 'lightslider';
window.lightCarousel = lightCarousel;
import introjs from 'intro.js';
window.introjs = introjs;
import pwstrengthmeter from 'password-strength-meter';
window.pwstrengthmeter = pwstrengthmeter;
import jqueryMousewheel from 'jquery-mousewheel';
window.jqueryMousewheel = jqueryMousewheel;
import Cookies from 'js-cookie';
window.Cookies = Cookies;
window.hull = require('hull.js'); // Find the 'hull' of a random set of points
window.Offset = require('polygon-offset'); // Offsetting polygons to get a smooth padding around them
window.Lang = require('lang.js'); // Javascript translations
window.d3 = require('d3'); // v3.5.14 since Pather uses an out-of-date version
import Pather from 'leaflet-pather';
window.Pather = Pather;
// window.circleMenu = require('zikes-circlemenu');
import Noty from 'noty';
window.Noty = Noty;
import Pickr from '@simonwep/pickr';
window.Pickr = Pickr;
import AntPath from 'leaflet-ant-path';
window.AntPath = AntPath;
import Grapick from 'grapick';
window.Grapick = Grapick;
import jqueryVisible from 'jquery-visible';
window.jqueryVisible = jqueryVisible;
import simplebar from 'simplebar';
window.simplebar = simplebar;
import Draggable from '@shopify/draggable';
window.Draggable = Draggable;
import autocomplete from 'bootstrap-4-autocomplete';
window.autocomplete = autocomplete;
import toggle from 'bootstrap4-toggle';
window.toggle = toggle;
import jarallax from 'jarallax/dist/jarallax.min';
window.jarallax = jarallax;
import swipe from 'jquery-touchswipe';
window.swipe = swipe;
import lazysizes from 'lazysizes';
window.lazysizes = lazysizes;
import ionRangeSlider from 'ion-rangeslider';
window.ionRangeSlider = ionRangeSlider;

import '@fortawesome/fontawesome-free';

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
