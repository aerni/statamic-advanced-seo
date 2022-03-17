![Statamic](https://flat.badgen.net/badge/Statamic/3.3+/FF269E) ![Packagist version](https://flat.badgen.net/packagist/v/aerni/advanced-seo/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/advanced-seo)

# Advanced SEO
Advanced SEO is a comprehensive solution providing you with all the tools you need to step up your SEO game. It was designed with the focus on a top-notch user experience, multi-site support, and flexibility, letting you tailor its features to your project’s needs. Want to handle the Sitemap yourself? Simply disable the feature in the config.

## Features
- Multi-site support
- Site defaults
- Content defaults
- Excellent user experience leveraging a custom source fieldtype
- Social images generator
- Fine-grained user permissions
- Migration command to easily migrate from other addons
- Sitemap
- Support for Statamic’s Git integration
- Highly flexible by design

## Requirements
- PHP 8.0
- Statamic 3.3
- Laravel 8
- [Puppeteer](https://github.com/spatie/browsershot#requirements) (Social Images Generator)

## Installation
Install the addon using Composer:

```bash
composer require aerni/advanced-seo
```

The config will be published to `config/advanced-seo.php` as part of the installation process. Make sure to check it out, as there are tons of configuration options to tailor the features to your needs.

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

## Migration
Advanced SEO provides an easy upgrade path from addons such as `Aardvark SEO` and `SEO Pro`. Simply run the following command to migrate your entries and terms to the new data format:

```bash
php please seo:migrate
```

>**Note:** The migrator only migrates data saved on entries and terms. It does not migrate any config files, settings, and default data. There are just too many things that could go wrong. So you will have to migrate those yourself. This should be pretty straightforward and not take too much of your time.

## Concept & Usage
After installing the addon, you will find a new `SEO` section in the sidebar navigation in the control panel. You will also find a new `SEO` tab when editing your entries and terms.

Advanced SEO uses its own stache store to save site defaults and content defaults for your collection entries and taxonomy terms. The store is organized into three content types: `site`, `collections` and `taxonomies`.

The data will cascade down from the site defaults to the content defaults and finally to the entry/term.

### Site Defaults
The site defaults consist of thoughtfully organized site-wide settings for the likes of site name, noindex, social images, and analytics trackers. Head to `SEO -> Site` to configure the settings to your liking. Values configured here will be saved in `content/seo/site/`.

If your site is a multi-site, you will be able to localize the settings for each of your sites.

### Collection & Taxonomy Defaults
The collection and taxonomy defaults let you define default values for your entries and terms like title, description, social images, sitemap settings, etc. Head to `SEO -> Collections` or `SEO -> Taxonomies` to configure the defaults to your liking. Values configured here will be saved into `content/seo/collections/` or `content/seo/taxonomies/`.

If your site is a multi-site, you will be able to localize the data to the sites configured on the respective collection or taxonomy.

### Entries & Terms
The addon will add a new `SEO` tab to the blueprint of your entries and terms that bundles all the SEO-related settings. Advanced SEO ships with its own source fieldtype that will let you choose the value’s source. It consists of at least two options: `Default` and `Custom`. Some fields, e.g. `Meta Title`, will also give you the `Auto` option.

| Source    | Description                                 | Value             |
| --------- | ------------------------------------------- | ----------------- |
| `Auto`    | Inherits the value from a predefined field  | `@auto`           |
| `Default` | Inherits the value from the defaults        | `@default`        |
| `Custom`  | Overwrites the default value with your own  | Your custom value |

## Social Images Generator
The social images generator provides an easy way to add captivating images to your entries. It leverages [Browsershot](https://github.com/spatie/browsershot) to convert your templates to an image. This means that you can design your images like a regular template, using variables, tags, partials, etc.

>**Note:** The generator requires a working installation of [Puppeteer](https://github.com/spatie/browsershot#requirements).

### Templating
To get started, simply run the following command to publish a basic layout and templates to `resources/views/social_images/`:

```bash
php artisan vendor:publish --tag=advanced-seo-views
```

You can access the templates according to the following schema:

```
https://site.test/!/advanced-seo/social-images/og/{id}
https://site.test/!/advanced-seo/social-images/twitter/{id}
```

To access the data of a localized entry, you also need to add the entry’s locale as the last segment of the route:

```
https://site.test/!/advanced-seo/social-images/og/{id}/german
https://site.test/!/advanced-seo/social-images/twitter/{id}/german
```

### Configuration
Make sure the generator is enabled in the addon’s config. Next, head over to `SEO -> Site -> Social Media` and enable the collections you want to enable the generator for. This will add a new `Social Images Generator` section to the selected collections’ defaults and entry blueprint. It will also add a new action to the contextual menu on the collections’ listing page.

### Usage
Activate the `Generate Social Images` toggle to generate the images every time you save the entry. If you don’t like this behavior, you can instead use the action in the contextual menu to generate the images on demand.

### Additional Fields
You might want to add fields to your blueprint specifically for your social images. Create a fieldset with the handle `social_images_generator` and it will add the fields directly below the social images generator. How sweet is that!

### Live Preview
You may use Statamic’s live preview feature to preview your social images when editing an entry. Simply click the `Live Preview` button and select the `Open Graph Image` or `Twitter Image` from the target dropdown.

## Sitemap
XML Sitemaps will automatically be generated for your entries and terms. The sitemap can be found at `yourwebsite.com/sitemap.xml`. You may exclude specific collections and taxonomies from the sitemap in `SEO -> Site -> Indexing`, or disable the sitemap feature altogether in the config.

The `priority` and `change frequency` of each item can be configured in the SEO settings on the respective entry/term.

## Permissions
The addon gives you fine-grained control of your users’ permissions to view and edit the site, collection, and taxonomy defaults.

## Meta Tags & Scripts
Advanced SEO supports all of the following meta tags and scripts:

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
- `google-site-verification`
- `msvalidate.01 (Bing)`

### Knowledge Graph
- `JSON-LD Schema`
- `Breadcrumbs`

### Analytics
- `Fathom`
- `Cloudflare Web Analytics`
- `Google Tag Manager`

## License
Advanced SEO is **commercial software** but has an open-source codebase. If you want to use it in production, you’ll need to [buy a license from the Statamic Marketplace](https://statamic.com/addons/aerni/advanced-seo).
>Advanced SEO is **NOT** free software.

## Credits
Developed by [Michael Aerni](https://www.michaelaerni.ch)
