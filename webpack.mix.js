require('dotenv').config();   // <-- add this line first

const mix = require('laravel-mix');

// The build is orchestrated by scripts/build/build.mjs (npm run dev / npm run production), which
// resolves the version (git revision or npm --output_version) and spawns mix with it in the
// environment so both build halves agree on output filenames. Everything except the app.js bundle
// and the sass entries below has moved to scripts/build/ (#3491); mix goes away entirely in #3492.
const version = process.env.KSG_BUILD_VERSION;
if (typeof version === 'undefined' || version === '') {
    throw new Error('KSG_BUILD_VERSION is not set - run the build via npm run dev / npm run production');
}

/**
 * Bootstrap 5 emits its design tokens as CSS variables on :root. Our themes wrap all of Bootstrap in a
 * theme class (e.g. `.darkly { @import bootstrap }`), which compiles those variable blocks to
 * `.darkly :root` — a selector that can never match, since :root is the <html> element that carries the
 * theme class itself. Rewrite them to `:root.darkly` so the variables resolve again.
 */
const scopedThemeRootFix = {
    postcssPlugin: 'scoped-theme-root-fix',
    Rule(rule) {
        if (rule.selector.includes(':root')) {
            rule.selectors = rule.selectors.map(
                selector => selector.replace(/^(\.[\w-]+) :root$/, ':root$1')
            );
        }
    },
};

mix.options({
    // This dramatically speeds up the build process -  adding new .scss for the redesign greatly increased build times without this
    processCssUrls: false,
    postCss: [scopedThemeRootFix],
}).webpackConfig({
    output: {
        publicPath: '/',
    },
    watchOptions: {
        ignored: ['node_modules', 'vendor', 'storage'],
        poll: 2000 // Check for changes every two seconds
    },
    // Handlebars has a bug which requires this: https://github.com/wycats/handlebars.js/issues/1174
    resolve: {
        alias: {
            handlebars: 'handlebars/dist/handlebars.min.js'
        }
    },
    module: {
        rules: [{
            test: /\.s[ac]ss$/i,
            loader: 'sass-loader',
            options: {
                additionalData: `$asset-url: "${process.env.ASSETS_BASE_URL}";`,
            }
        }],
    },
});

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

mix.js('resources/assets/js/app.js', `public/js/app-${version}.js`)
    .sass('resources/assets/sass/app.scss', `public/css/app-${version}.css`)
    .sass('resources/assets/sass/theme/theme.scss', `public/css/theme-${version}.css`)
    .sass('resources/assets/sass/custom/custom.scss', `public/css/custom-compiled-${version}.css`)
    .sass('resources/assets/sass/custom/assets/assets.scss', `public/css/assets-compiled-${version}.css`);

mix.sourceMaps();
