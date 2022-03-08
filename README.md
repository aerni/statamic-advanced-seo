![Statamic](https://flat.badgen.net/badge/Statamic/3.0+/FF269E) ![Packagist version](https://flat.badgen.net/packagist/v/aerni/advanced-seo/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/advanced-seo)

# Advanced SEO
Advanced SEO is a comprehensive solution providing you with all the tools you need to step up your SEO game. It was designed with the focus on a top-notch user experience, multi-site support, and flexibility, letting you tailor its features to your project’s needs. Want to handle the Sitemap yourself? Simply disable the feature in the config.

## Features
- Multi-site support
- Site and content defaults
- Great user experience leveraging a custom source fieldtype
- Social images generator
- Fine-grained user permissions
- Sitemap
- Support for Statamic’s Git integration
- Highly flexible by design

## Meta Data
Advanced SEO supports the following meta tags and scripts:

### Basic
- `title`
- `description`
- `alternate (hreflang)`
- `canonical`
- `prev`
- `next`
- `robots (noindex, nofollow)`
- `icon (Favicon)`

### Open Graph
- `og:type`
- `og:site_name`
- `og:title`
- `og:description`
- `og:url`
- `og:locale`
- `og:locale:alternate`
- `og:image`
- `og:image:width`
- `og:image:height`

### Twitter
- `twitter:card`
- `twitter:title`
- `twitter:description`
- `twitter:site`
- `twitter:image`
- `twitter:image:alt`

### Site Verification
google-site-verification (Google)
msvalidate.01 (Bing)

### Knowledge Graph
- `JSON-LD Schema`
- `Breadcrumbs`

### Analytics
- `Fathom`
- `Cloudflare Web Analytics`
- `Google Tag Manager`

## Requirements
- PHP 8.0
- Statamic 3.3
- Laravel 8

## Installation
Install the addon using Composer:

```bash
composer require aerni/advanced-seo
```

The config will be published to `config/advanced-seo.php` as part of the installation process. Make sure to check it out as there are tons of configuration options to tailor the features to your needs.

## Getting started
Add `{{ seo:head }}` somewhere between your `<head>` tags.

```html
<head>{{ seo:head }}</head>
```

 Add `{{ seo:body }}` after the opening `<body>` tag. This tag is only needed when using Google Tag Manager.

```html
<body>{{ seo:body }}</body>
```

### Available Tags
| Tag               | Description                                              |
| ----------------- | -------------------------------------------------------- |
| `{{ seo:head }}`  | Render the head view with the meta data                  |
| `{{ seo:body }}`  | Render the body view with the body data                  |
| `{{ seo:dump }}`  | Dump all the meta data of the current page               |
| `{{ seo:field }}` | Get the data of a specific field, e.g. `{{ seo:title }}` |

## Concept & Usage
After installing the addon you will find a new `SEO` section in the sidebar navigation in the control panel. You will also find a new `SEO` tab when editing your entries and terms.

Advanced SEO is using its own stache store to save site defaults, as well as content defaults for your collection entries and taxonomy terms. The store is organized in three content types: `site`, `collections` and `taxonomies`.

The data will cascade down from the site defaults, to the content defaults and finally to the entry/term.

## Site Defaults
The site defaults consist of thoughtfully organized site-wide settings for the likes of site name, noindex, social images, and analytics trackers. Head to `SEO -> Site` to configure the settings to your liking. Values configured here will be saved in `content/seo/site/`.

If your site is a multi-site, you will be able to localize the settings for each of your sites.

## Collection & Taxonomy Defaults
The collection and taxonomy defaults let you define default values for your entries and terms like title, description, social images, sitemap settings, etc. Head to `SEO -> Collections` or `SEO -> Taxonomies` to configure the defaults to your liking. Values configured here will be saved into `content/seo/collections/` or `content/seo/taxonomies/`.

If your site is a multi-site, you will be able to localize the data to the sites that are configured on the respective collection or taxonomy.

## Entries & Terms
The addon will add a new `SEO` tab to the blueprint of your entries and terms that bundles all the SEO-related settings. Advanced SEO shipps its own source fieldtype that will let you choose the value’s source. It consists of at least two options: `Default` and `Custom`. Some fields, e.g. `Meta Title`, will also give you the `Auto` option.

| Source    | Description                                 | Value             |
| --------- | ------------------------------------------- | ----------------- |
| `Auto`    | Inherits the value from a predefined field  | `@auto`           |
| `Default` | Inherits the value from the defaults        | `@default`        |
| `Custom`  | Overwrites the default value with your own  | Your custom value |

## Sitemap
XML Sitemaps will automatically be generated for your entries and terms. The sitemap can be found at `yourwebsite.com/sitemap.xml`. You may exclude certain collections and taxonomies from the sitemap in `SEO -> Site -> Indexing`, or disable the sitemap feature altogether in the config.

The `priority` and `change frequency` of each item can be configured in the SEO settings on the respective entry/term.

## Permissions
The addon gives you fine-grained control of your user’s permissions to view and edit the site, collection, and taxonomy defaults.

## License
Advanced SEO is **commercial software** but has an open-source codebase. If you want to use it in production, you’ll need to [buy a license from the Statamic Marketplace](https://statamic.com/addons/aerni/advanced-seo).
>Advanced SEO is **NOT** free software.

## Credits
Developed by [Michael Aerni](https://www.michaelaerni.ch)
