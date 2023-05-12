module.exports = {
    prefix: 'seo-',
    presets: [
      require('./vendor/statamic/cms/tailwind.config.js'),
    ],
    corePlugins: {
        preflight: false,
    },
}
