# Upgrading from v2 to v3

v3 introduces a significant internal refactor that improves the architecture and maintainability of the addon and improves the overall user experience. Most breaking changes have automated upgrade scripts, so the upgrade should be seamless for most users.

## Upgrade Summary

| Change | Action |
|--------|--------|
| [Requirements](#requirements) | ⚠️ Verify compatibility |
| [Permissions](#permissions) | ✅ Automated |
| [Single-site data structure](#single-site-data-structure) | ✅ Automated |
| [Site SEO sets consolidated](#site-seo-sets) | ✅ Automated |
| [Disabled config removed](#disabled-collections--taxonomies-config) | ✅ Automated |
| [Origin field centralized](#localization-origin-field) | ✅ Automated |
| [Eloquent schema](#eloquent-driver) | ✅ Automated |
| [Simplified field UI](#simplified-field-ui) | ✅ Automated |
| [`@field:handle` syntax](#fieldhandle-syntax-replaced-with-antlers) | ✅ Automated |
| [Site name position composed into title](#site-name-position-composed-into-title) | ✅ Automated |
| [Title separator renamed](#title-separator-renamed) | ✅ Automated |
| [Twitter fields simplified](#removed-fields) | ✅ Automated |
| [Twitter card moved to config](#twitter-card-setting) | ✅ Automated |
| [Twitter custom views](#custom-views) | ⚠️ Update published views |
| [Screenshot package](#screenshot-package) | ⚠️ Require package manually |
| [Social images per-collection](#per-collection-settings) | ✅ Automated |
| [`enabled` config removed](#removed-enabled-config-option) | ⚠️ Remove from published config |
| [`generate_on_save` removed](#removed-generate_on_save-config-option) | ⚠️ Remove from published config |
| [Twitter image generation removed](#removed-twitter-image-generation) | ⚠️ Remove custom templates |
| [Social images template `$group`](#template-changes) | ⚠️ Update custom templates |
| [Social images directory](#image-storage-directory) | ⚠️ Delete orphaned images |
| [Social image preview targets](#removed-social-image-preview-targets) | ✅ No changes needed |
| [Unified social image field](#unified-social-image-field) | ⚠️ Update published views |
| [Sitemap per-collection/taxonomy](#per-collectiontaxonomy-settings) | ✅ Automated |
| [Sitemap domain scoping](#domain-scoping) | ✅ No changes needed |
| [Custom sitemap registration](#custom-sitemap-registration) | ⚠️ Update `Sitemap::register()` calls |
| [Events](#events) | ⚠️ Update event listeners |
| [GraphQL](#graphql) | ⚠️ Update queries |

## Requirements

- PHP 8.4+
- Laravel 12+
- Statamic 6+

## Permissions

The permission system has been simplified. Old granular permissions have been replaced with three new permissions:

- `configure seo` - Full access to all SEO settings, defaults, and content editing
- `edit seo defaults` - Edit collection and taxonomy defaults
- `edit seo content` - Access the SEO tab on entries and terms

**Important:** The `edit seo defaults` and `edit seo content` permissions work in conjunction with Statamic's native collection and taxonomy permissions. For example, to edit SEO defaults for the Pages collection, a user needs the `edit seo defaults` permission AND either Statamic's `configure collections` or `edit pages entries` permission.

> ✅ **Automated** — User role permissions are automatically migrated. All existing roles receive the `edit seo content` permission to maintain backward compatibility.

## Single-Site Data Structure

Single-site installations now use the same data structure as multi-site. Config and localization data are stored separately:

```diff
  content/seo/collections/pages.yaml
+ content/seo/collections/{site}/pages.yaml
```

> ✅ **Automated** — Your existing data is automatically migrated to the new structure, whether you use file-based storage or the Eloquent driver.

## Site SEO Sets

The five separate site SEO sets (`site::general`, `site::indexing`, `site::social_media`, `site::analytics`, `site::favicons`) have been consolidated into a single `site::defaults` set. The separation previously existed only for UI grouping — tabs now provide that organization within a single publish form.

For file-based installations, the file structure has changed:

```diff
- content/seo/site/general.yaml
- content/seo/site/indexing.yaml
- content/seo/site/social_media.yaml
- content/seo/site/analytics.yaml
- content/seo/site/favicons.yaml
- content/seo/site/{site}/general.yaml
- content/seo/site/{site}/indexing.yaml
- content/seo/site/{site}/social_media.yaml
- content/seo/site/{site}/analytics.yaml
- content/seo/site/{site}/favicons.yaml
+ content/seo/site/defaults.yaml
+ content/seo/site/{site}/defaults.yaml
```

> ✅ **Automated** — Your existing config and localization data is automatically merged into the single `site::defaults` set.

## Disabled Collections & Taxonomies Config

The `disabled` configuration option has been removed from `config/advanced-seo.php`:

```diff
- 'disabled' => [
-     'collections' => [],
-     'taxonomies' => [],
- ],
```

> ✅ **Automated** — Your existing disabled collections and taxonomies are automatically migrated. Collections and taxonomies can now be disabled individually through the Control Panel by clicking the "Configure" button on each set. You can safely remove the `disabled` option from your published config file.

## Localization Origin Field

The `origin` field has been removed from localizations. Origin configuration is now stored centrally in the set config using an `origins` array.

> ✅ **Automated** — Your existing origin configuration is automatically migrated from localizations to the set config.

## Eloquent Driver

If you're using the Eloquent driver, the database schema has changed:

The `advanced_seo_defaults` table has been replaced by `seo_set_localizations`. Additionally, a new `seo_set_configs` table has been added to store configuration data (enabled state, origins, etc.).

> ✅ **Automated** — When updating to v3.0, new migrations will be published and run automatically. Data will be migrated from the old table to the new tables, and the old `advanced_seo_defaults` table will be dropped.

## SEO Field Values

### Simplified Field UI

The SEO source fields have been simplified from a three-state toggle (Auto/Default/Custom) to a two-state inheritance model. Fields are now either **inherited** (showing the default value) or **custom** (user-set value). The toggle has been removed — transitioning between states happens implicitly through editing and a Reset action.

### `@field:handle` Syntax Replaced with Antlers

The `@field:handle` syntax for referencing other fields has been replaced with standard Antlers `{{ handle }}` syntax. This means you can now use the full power of Antlers in your SEO fields, including modifiers like `{{ content | truncate(90, '...') }}`.

> ✅ **Automated** — All `@field:handle` references, as well as `@auto` and `@null` sentinel values, are automatically migrated during the upgrade.

### Site Name Position Composed into Title

The `seo_site_name_position` field has been removed. Its value is now composed directly into the `seo_title` field using Antlers syntax, giving you full control over the title format.

| Position | Resulting `seo_title` |
|----------|-----------------------|
| `start` | `{{ site_name }} {{ separator }} {{ title }}` |
| `end` | `{{ title }} {{ separator }} {{ site_name }}` |
| `disabled` | `{{ title }}` (no site name appended) |

> ✅ **Automated** — The position is automatically composed into the title during the upgrade.

### Title Separator Renamed

The `title_separator` field in site defaults has been renamed to `separator`.

> ✅ **Automated** — Existing `title_separator` values are automatically renamed to `separator` across all site localizations.

## X (Twitter) Social Sharing

The X (Twitter) social sharing has been significantly simplified. Instead of maintaining separate Twitter-specific fields, the addon now uses a unified social image shared between Open Graph and Twitter, with only the card size remaining as a Twitter-specific setting.

### Removed Fields

The following fields have been removed from entries, terms, and SEO set localizations:

| Removed Field | Replacement |
|---------------|-------------|
| `seo_twitter_title` | Uses `seo_og_title` |
| `seo_twitter_description` | Uses `seo_og_description` |
| `seo_twitter_summary_image` | Uses `seo_og_image` |
| `seo_twitter_summary_large_image` | Uses `seo_og_image` |

The following fields have been removed from the site-wide social media defaults:

| Removed Field | Replacement |
|---------------|-------------|
| `twitter_summary_image` | Uses `og_image` |
| `twitter_summary_large_image` | Uses `og_image` |

> ✅ **Automated** — Existing Twitter field data is automatically removed from your content during the upgrade.

### Twitter Card Setting

The `seo_twitter_card` field has been moved from a per-localization setting to a per-collection/taxonomy configuration setting. It is now available in the "Social Appearance" section of the collection/taxonomy config, alongside other collection-level settings like the sitemap and social images generator toggles.

> ✅ **Automated** — The existing `seo_twitter_card` value from the default site's localization is automatically migrated to the collection/taxonomy config.

### Custom Views

> ⚠️ **Manual** — If you've published or customized the `_twitter.antlers.html` snippet, update the following references:

| Old Variable | New Variable |
|--------------|--------------|
| `seo:twitter_title` | `seo:og_title` |
| `seo:twitter_description` | `seo:og_description` |
| `seo:twitter_image` | `seo:og_image` |
| `seo:twitter_image:alt` | `seo:og_image:alt` |

### Social Images Generator

If you're using the social images generator, see the [Removed Twitter Image Generation](#removed-twitter-image-generation) section for related changes.

### GraphQL

If you're using the GraphQL API, see the [Computed Field Changes](#computed-field-changes) section under GraphQL for related schema updates.

## Social Images Generator

### Screenshot Package

The social images generator now uses [spatie/laravel-screenshot](https://spatie.be/docs/laravel-screenshot/v1/introduction) and is no longer bundled as a dependency. If you use the social images generator, you must require the package manually:

```
composer require spatie/laravel-screenshot
```

The `social_images.generator.enabled` config option has been removed — installing the package is sufficient to enable the feature. You will also need to install and configure a screenshot driver such as Browsershot or Cloudflare Browser Rendering. Refer to the [installation guide](https://spatie.be/docs/laravel-screenshot/v1/installation-setup) for details. To customize screenshot settings, publish the config:

```
php artisan vendor:publish --tag=laravel-screenshot-config
```

### Per-Collection Settings

The `social_images_generator_collections` field has been removed from the site defaults. The social images generator is now enabled per-collection using the `social_images_generator` toggle in the collection config.

> ✅ **Automated** — Your existing settings are automatically migrated to per-collection configuration.

### Removed `enabled` Config Option

The `enabled` config option has been removed from the generator config:

```diff
  'generator' => [
-     'enabled' => false,
  ],
```

The social images generator is now automatically enabled when `spatie/laravel-screenshot` is installed. See [Screenshot Package](#screenshot-package) for details.

> ⚠️ **Manual** — Remove this option from your published config file if present.

### Removed `generate_on_save` Config Option

The `generate_on_save` config option has been removed from the generator config:

```diff
  'generator' => [
-     'generate_on_save' => true,
  ],
```

> ⚠️ **Manual** — Remove this option from your published config file if present.

Social image generation now works as follows:

- **After saving**: Images are generated using Laravel's `defer()`, which runs after the response is sent (sync driver) or dispatches a queued job. Content hashing ensures images are only regenerated when content actually changes.
- **On demand**: If a generated image is missing (e.g. accidentally deleted), it will be regenerated on-the-fly on the next frontend request.

### Removed Twitter Image Generation

The social images generator no longer generates separate Twitter images. Only the Open Graph image is generated. The `twitter_summary` and `twitter_summary_large_image` config presets are still used to resize the shared image for the Twitter meta tags via Glide, but no separate images are generated for them.

> ⚠️ **Manual** — If you have custom social images generator themes, remove the `twitter_summary.antlers.html` and `twitter_summary_large_image.antlers.html` templates — only `open_graph.antlers.html` is used.

### Template Changes

> ⚠️ **Manual** — The `$group` variable has been removed from the data passed to social images generator templates. If your templates reference this variable, update them.

### Image Storage Directory

Generated social images are now stored in `social_images/collection-{handle}/` and `social_images/taxonomy-{handle}/` subdirectories instead of `social_images/{handle}/`. This change adds support for taxonomy terms and avoids collisions between collections and taxonomies with the same handle.

> ⚠️ **Manual** — Existing generated images in the old directory structure will not be migrated automatically. They will be regenerated in the new location on the next save or frontend request. You may delete the orphaned images in the old directories.

### Removed Social Image Preview Targets

The social images generator no longer registers live preview targets for entries. Social image previews are now shown directly in the publish form via the new inline preview fieldtype, which provides a better editing experience with real-time theme switching.

### Unified Social Image Field

The `seo_generated_og_image` field has been removed. The `seo_og_image` field now handles both generated and user-defined images automatically based on the `seo_generate_social_images` toggle state.

When the toggle is enabled, `seo_og_image` returns the generated image. When disabled, it returns the user-defined image. This change simplifies the data model and eliminates the need to check multiple fields.

> ⚠️ **Manual** — If you published or customized views that reference `seo:generated_og_image`, update them to use `seo:og_image`.

## Sitemaps

### Per-Collection/Taxonomy Settings

The `excluded_collections` and `excluded_taxonomies` fields have been removed from the site defaults. The sitemap is now enabled per-collection/taxonomy using the `sitemap` toggle in the collection/taxonomy config.

> ✅ **Automated** — Your existing settings are automatically migrated to per-collection/taxonomy configuration.

### Domain Scoping

Each domain now gets its own sitemap index, and its sitemaps only contain URLs from sites on that domain. Previously, a single sitemap index included URLs from all sites regardless of domain.

| Setup | Sitemap indexes |
|-------|-----------------|
| `example.com`, `example.com/de`, `example.com/fr` | One index on `example.com` (unchanged) |
| `example.com`, `example.com/de`, `example.fr` | One index on `example.com`, one on `example.fr` |
| `example.com`, `example.de`, `example.fr` | One index per domain |

> **Note:** Hreflang tags are unaffected and continue to reference all localized versions across domains.

### Custom Sitemap Registration

Due to domain scoping, the API for registering custom sitemaps has changed. Instead of using `Sitemap::register()` which implicitly routed sitemaps based on their site, you now explicitly add sitemaps to a specific site's index:

```diff
- Sitemap::register($sitemap);
+ Sitemap::index('english')->add($sitemap);
```

> ⚠️ **Manual** — Update any custom sitemap registration calls in your application code.

## Events

The `SeoDefaultSetSaved` event has been renamed to `SeoSetLocalizationSaved`. Both events handle the same localization data. The public property has been renamed from `$defaults` to `$localization`.

Additionally, new events have been added for config and deletion operations:
- `SeoSetConfigSaved` - Fired when a set's configuration is saved
- `SeoSetConfigDeleted` - Fired when a set's configuration is deleted
- `SeoSetLocalizationDeleted` - Fired when a localization is deleted

> ⚠️ **Manual** — If you listen for `SeoDefaultSetSaved`, update your listener to use `SeoSetLocalizationSaved` and rename the `$defaults` property reference to `$localization`.

## GraphQL

The GraphQL API has been simplified and restructured for consistency. If you're using the GraphQL API, you'll need to update your queries.

> ⚠️ **Manual** — All GraphQL changes below require updating your queries.

### `seoSet` Query

#### Renamed Query and Types

The query has been renamed from `seoDefaults` to `seoSet`:

```diff
- seoDefaults {
+ seoSet {
    ...
  }
```

All type names have been updated to use "Set" instead of "Defaults":

| Old Type Name | New Type Name |
|---------------|---------------|
| `siteDefaults` | `siteSet` |
| `contentDefaults` | `collectionSet` / `taxonomySet` |

#### Flattened Site Set Structure

The `siteDefaults` type previously contained nested sub-types (`general`, `indexing`, `socialMedia`, `analytics`, `favicons`). These have been consolidated into a single flat `siteSet` type with all fields at the top level:

```diff
  {
-   seoDefaults {
-     site {
-       general { site_name, ... }
-       indexing { noindex, ... }
-       socialMedia { og_image, ... }
-       analytics { fathom_id, ... }
-       favicons { favicon_svg, ... }
-     }
-   }
+   seoSet {
+     site { site_name, noindex, og_image, fathom_id, favicon_svg, ... }
+   }
  }
```

The following sub-types have been removed:

| Removed Type | Replacement |
|--------------|-------------|
| `generalDefaults` | Fields moved directly onto `siteSet` |
| `indexingDefaults` | Fields moved directly onto `siteSet` |
| `socialMediaDefaults` | Fields moved directly onto `siteSet` |
| `analyticsDefaults` | Fields moved directly onto `siteSet` |
| `faviconsDefaults` | Fields moved directly onto `siteSet` |

#### Removed Fields

The following fields have been removed from the `siteSet` type as they are now configured per collection/taxonomy:

- `excluded_collections`
- `excluded_taxonomies`
- `social_images_generator_collections`

#### Disabled Features

Fields belonging to disabled features are now completely removed from the schema. Previously, disabled feature fields were still present but returned `null` values. Now, if a feature is disabled in `config/advanced-seo.php`, its fields will not appear in the schema at all.

For example, if you disable the sitemap, all sitemap related fields will be removed. Similarly, disabling favicons or all analytics trackers removes those fields entirely.

### `seoMeta` Query

#### Removed `baseUrl` Argument

The `baseUrl` argument has been removed from the `seoMeta` query. Previously, it allowed rewriting absolute URLs for headless setups where the frontend was hosted on a different domain. To achieve the same behavior, configure your frontend domain in Statamic's sites configuration (`resources/sites.yaml`).

The argument was defined on the query itself but affected the output of `computed.canonical`, `computed.hreflang`, `computed.site_schema`, and `computed.breadcrumbs` by rewriting their absolute URLs.

```diff
  {
-   seoMeta(id: "e5f6a7b8-1234-5678-9abc-def012345678", baseUrl: "https://frontend.example.com") {
+   seoMeta(id: "e5f6a7b8-1234-5678-9abc-def012345678") {
      computed {
        canonical
        hreflang
        site_schema
        breadcrumbs
      }
    }
  }
```

#### Computed Field Changes

The following fields have been removed from the `computed` type:

| Removed Field | Use Instead |
|---------------|-------------|
| `title` | Use raw data `title` field |
| `og_title` | Use raw data `og_title` field |
| `twitter_title` | Use raw data `og_title` field |
| `twitter_image` | Use raw data `og_image` field |
| `og_image` | Use raw data `og_image` field |
| `generated_og_image` | Use raw data `og_image` field |

The `title` and `og_title` fields are now fully resolved through augmentation (see [Site Name Position](#site-name-position-composed-into-title)), making the computed methods redundant.

A new `twitter_card` field has been added to the `computed` type, returning the card size (`summary` or `summary_large_image`).

The following fields have been removed from the `seoMeta` raw type, `collectionSet`, and `taxonomySet` types:

| Removed Field | Use Instead |
|---------------|-------------|
| `site_name_position` | Composed into `title` (see [Site Name Position](#site-name-position-composed-into-title)) |
| `twitter_card` | Use `seoMeta { computed { twitter_card } }` |
| `twitter_title` | `og_title` |
| `twitter_description` | `og_description` |
| `twitter_summary_image` | `og_image` |
| `twitter_summary_large_image` | `og_image` |

The following fields have been removed from the `siteSet` type:

| Removed Field | Use Instead |
|---------------|-------------|
| `twitter_summary_image` | `og_image` |
| `twitter_summary_large_image` | `og_image` |

The following field has been renamed on the `siteSet` type:

| Old Field | New Field |
|-----------|-----------|
| `title_separator` | `separator` |

### `seoSitemaps` Query

The query has been completely restructured for a simpler, flatter API.

**Before:**
```graphql
{
  seoSitemaps {
    collection(baseUrl: "https://frontend.example.com", site: "default", handle: "pages") {
      loc
      lastmod
      alternates { hreflang, href }
    }
  }
}
```

**After:**
```graphql
{
  seoSitemaps(site: "default", type: "collection", handle: "pages") {
    id
    type
    handle
    lastmod
    urls {
      loc
      lastmod
      changefreq
      priority
      alternates { hreflang, href }
    }
  }
}
```

#### Key Changes

- `site` is now a required query argument (was optional on nested fields)
- `type` argument (`collection`, `taxonomy`, `custom`) replaces the nested fields
- `baseUrl` has been removed. Configure your frontend domain in Statamic's sites configuration (`resources/sites.yaml`) instead.
- Returns a list of sitemaps with `id`, `type`, `handle`, `lastmod`, and nested `urls` array (previously returned a flat list of URLs)
