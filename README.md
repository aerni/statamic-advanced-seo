![Statamic](https://flat.badgen.net/badge/Statamic/3.0+/FF269E) ![Packagist version](https://flat.badgen.net/packagist/v/aerni/advanced-seo/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/advanced-seo)

# Advanced SEO
An advanced SEO addon for Statamic

## Features
- Multi-site support

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
