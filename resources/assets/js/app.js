/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

const {getCsrfToken} = require('./csrf');

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */
// For now, we do not use Vue
// Vue.component('example', require('./components/Example.vue'));
//
// const app = new Vue({
//     el: '#app'
// });

// Attach a fresh CSRF token to every same-origin ajax request. We use the global `ajaxSend`
// event rather than `$.ajaxSetup` because the token is re-read from the cookie on every request
// (staying valid after the session is rotated in another tab) and because `ajaxSetup`'s
// `headers`/`beforeSend` are clobbered by the per-request `beforeSend` handlers several callers
// already define. See resources/assets/js/csrf.js and issue #3452.
$(document).ajaxSend(function (event, jqXHR, settings) {
    // Never leak the CSRF token to a third-party host.
    if (settings.crossDomain) {
        return;
    }

    const token = getCsrfToken();
    if (token !== null) {
        jqXHR.setRequestHeader(token.header, token.value);
    }
});