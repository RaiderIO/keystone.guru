const { mix } = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/assets/js/app.js', 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css')
    // Lib processing
   .styles(['resources/assets/lib/**/*.css'], 'public/css/lib.css')
   .combine(['resources/assets/lib/**/*.js'], 'public/js/lib.js')
    // Custom css processing
   .styles(['resources/assets/css/**/*.css'], 'public/css/custom.css');

// Should be uncommented for production!
// mix.copy('resources/assets/images', 'public/images', false);
mix.copy('resources/assets/images/datatables', 'public/images/datatables', false);