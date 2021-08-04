const mix = require('laravel-mix');

mix.setPublicPath('resources/dist')
    .js('resources/js/advanced-seo.js', 'js').vue()
    .postCss('resources/css/advanced-seo.css', 'css', [
        require('tailwindcss')
    ])

if (mix.inProduction()) {
    mix.version();
}
