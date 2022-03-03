![Statamic](https://flat.badgen.net/badge/Statamic/3.0+/FF269E) ![Packagist version](https://flat.badgen.net/packagist/v/aerni/advanced-seo/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/advanced-seo)

# Advanced SEO
Advanced SEO is comprehensive and robust solution that provides all the tools you need to step up your SEO game. Its flexible approach lets you disable core functionality, letting you tailor the addon to your projects needs. You don't need Google Tag Manager or don't want to use the built in Sitemaps? Simply disable it in the config.

## Features
- Comprehensive multi-site support
- Support for Collection and Taxonomy defaults
- Great UI experience when defining SEO data on an entry or term
- Generator for your Open Graph and Twitter images
- Sitemap
- Fine-grained user permissions
- The data is stored in its own Stache store
- Support for Statamic's Git integration

## Installation
Install the addon using Composer:

```bash
composer require aerni/advanced-seo
```

The config will be published to `config/advanced-seo.php` as part of the installation process.

## Getting started

Add `{{ seo:head }}` somewhere between your `<head>` tags and `{{ seo:body }}` after the opening `<body>` tag.

```html
<head>
    {{ seo:head }}
</head>

<body>
    {{ seo:body }}
</body>
```

## License
Advanced SEO is **commercial software** but has an open-source codebase. If you want to use it in production, you'll need to [buy a license from the Statamic Marketplace](https://statamic.com/addons/aerni/advanced-seo).
>Advanced SEO is **NOT** free software.

## Credits
Developed by [Michael Aerni](https://www.michaelaerni.ch)
