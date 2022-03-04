![Statamic](https://flat.badgen.net/badge/Statamic/3.0+/FF269E) ![Packagist version](https://flat.badgen.net/packagist/v/aerni/advanced-seo/latest) ![Packagist Total Downloads](https://flat.badgen.net/packagist/dt/aerni/advanced-seo)

# Advanced SEO
Advanced SEO is a comprehensive solution providing you with all the tools you need to step up your SEO game. It was designed with focus on the user experience and offers top-notch multi-site support. The addon is built with flexibility in mind, letting you tailor its features to your project's needs. You don't use Google Tag Manager? Simply disable the feature in the config.

## Features
- Multi-site support
- Site and content defaults
- Great user experience leveraging a custom source fieldtype
- Social images generator
- Fine-grained user permissions
- Sitemap
- Support for Statamic's Git integration
- Highly flexible by design

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

 Add `{{ seo:body }}` after the opening `<body>` tag. This is only needed if you are using Google Tag Manager.

```html
<body>{{ seo:body }}</body>
```

## Usage
SEO settings will cascade down from the site defaults, to the content defaults and finally to the entry/term level.

## Site Defaults
The site defaults consist of thoughtfully organized site-wide settings for the likes of site name, noindex, social images, and analytics trackers. If your site is a multi-site, you will be able to localize some of the settings for each of your sites. Head to `SEO -> Site` to configure the settings to your liking. Values configured here will be saved in `content/seo/site/`.

## Collection & Taxonomy Defaults
The collection and taxonomy defaults let you define default values for your entries and terms like title, description, social images, sitemap settings, etc. Head to `SEO -> Collections` or `SEO -> Taxonomies` to configure the defaults to your liking. Values configured here will be saved into `content/seo/collections/` or `content/seo/taxonomies/`.

## Entries & Terms
A new `SEO` tab will be added to the blueprint of your entries and terms. This is where you will manage all the SEO-related data. A custom fieldtype will let you set the source of each field, giving you at least two options: `Default` and `Custom`. Some fields, e.g. `Meta Title`, will also have the option `Auto`.

| Source    | Description                                | Value
| --------- | ------------------------------------------ | ----------------- |
| `Default` | Inherits the value from the defaults       | `@default`        |
| `Custom`  | Overwrite the default value with your own  | Your custom value |
| `Auto`    | Inherits the value from a predefined field | `@auto`           |

## Tags
| Tag               | Description                                              |
| ----------------- | -------------------------------------------------------- |
| `{{ seo:head }}`  | Render the head view with the meta data                  |
| `{{ seo:body }}`  | Render the body view with the body data                  |
| `{{ seo:dump }}`  | Dump all the meta data of the current page               |
| `{{ seo:field }}` | Get the data of a specific field, e.g. `{{ seo:title }}` |

## License
Advanced SEO is **commercial software** but has an open-source codebase. If you want to use it in production, you'll need to [buy a license from the Statamic Marketplace](https://statamic.com/addons/aerni/advanced-seo).
>Advanced SEO is **NOT** free software.

## Credits
Developed by [Michael Aerni](https://www.michaelaerni.ch)
