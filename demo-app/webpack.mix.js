const mix = require('laravel-mix');
const path = require('path');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.babelConfig({
    presets: [
        "@babel/preset-env",
        "@babel/preset-react",
    ]
});

mix.js('resources/js/app.js', 'public/js')
    .copy('resources/images', 'public/images')
    .postCss('resources/css/app.css', 'public/css', [
        //
    ]);

if (!mix.inProduction()) {
    mix.sourceMaps()
        .webpackConfig({devtool: 'inline-source-map'});
}


if (mix.inProduction() || process.env.npm_lifecycle_event !== 'hot') {
    mix.version();
}
