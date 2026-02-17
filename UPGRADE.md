# Upgrading from v2 to v3

v3 introduces a significant internal refactor that improves the architecture and maintainability of the addon and improves the overall user experience. Most breaking changes have automated upgrade scripts, so the upgrade should be seamless for most users.

## Requirements

- PHP 8.3+
- Laravel 12+
- Statamic 6+

## Permissions

The permission system has been simplified. Old granular permissions have been replaced with three new permissions:

- `configure seo` - Full access to all SEO settings, defaults, and content editing
- `edit seo defaults` - Edit collection and taxonomy defaults
- `edit seo content` - Access the SEO tab on entries and terms

**Important:** The `edit seo defaults` and `edit seo content` permissions work in conjunction with Statamic's native collection and taxonomy permissions. For example, to edit SEO defaults for the Pages collection, a user needs the `edit seo defaults` permission AND either Statamic's `configure collections` or `edit pages entries` permission.

> **Automated Migration**: User role permissions are automatically migrated. All existing roles receive the `edit seo content` permission to maintain backward compatibility.

## Single-Site Data Structure

Single-site installations now use the same data structure as multi-site, with config and localization data stored separately.

For file-based installations, SEO data that was previously stored inline in config files is now stored in separate localization files within a site-specific directory:

**Before:**
```
content/seo/collections/pages.yaml  # contained both config and data
```

**After:**
```
content/seo/collections/pages.yaml           # config only
content/seo/collections/{site}/pages.yaml    # localization data
```

If you use the Eloquent driver, this is handled by the database migration.

> **Automated Migration**: Your existing data is automatically migrated to the new structure.

## Disabled Collections & Taxonomies Config

The `disabled` configuration option has been removed from `config/advanced-seo.php`:

```diff
- 'disabled' => [
-     'collections' => [],
-     'taxonomies' => [],
- ],
```

> **Automated Migration**: Your existing disabled collections and taxonomies are automatically migrated. Collections and taxonomies can now be disabled individually through the Control Panel by clicking the "Configure" button on each set.

## Localization Origin Field

The `origin` field has been removed from localizations. Origin configuration is now stored centrally in the set config using an `origins` array.

> **Automated Migration**: Your existing origin configuration is automatically migrated from localizations to the set config.

## Eloquent Driver

If you're using the Eloquent driver, the database schema has changed:

The `advanced_seo_defaults` table has been replaced by `seo_set_localizations`. Additionally, a new `seo_set_configs` table has been added to store configuration data (enabled state, origins, etc.).

> **Automated Migration**: When updating to v3.0, new migrations will be published and run automatically. Data will be migrated from the old table to the new tables, and the old `advanced_seo_defaults` table will be dropped.

## SEO Field Values

### Simplified Field UI

The SEO source fields have been simplified from a three-state toggle (Auto/Default/Custom) to a two-state inheritance model. Fields are now either **inherited** (showing the default value) or **custom** (user-set value). The toggle has been removed — transitioning between states happens implicitly through editing and a Reset action.

### `@field:handle` Syntax Replaced with Antlers

The `@field:handle` syntax for referencing other fields has been replaced with standard Antlers `{{ handle }}` syntax. This means you can now use the full power of Antlers in your SEO fields, including modifiers like `{{ content | truncate(90, '...') }}`.

> **Automated Migration**: All `@field:handle` references, as well as `@auto` and `@null` sentinel values, are automatically migrated during the upgrade.

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

> **Automated Migration**: Existing Twitter field data is automatically removed from your content during the upgrade.

### Twitter Card Setting

The `seo_twitter_card` field has been moved from a per-localization setting to a per-collection/taxonomy configuration setting. It is now available in the "Social Appearance" section of the collection/taxonomy config, alongside other collection-level settings like the sitemap and social images generator toggles.

> **Automated Migration**: The existing `seo_twitter_card` value from the default site's localization is automatically migrated to the collection/taxonomy config.

### Custom Views

If you've published or customized the `_twitter.antlers.html` snippet, update the following references:

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

The social images generator now uses [spatie/laravel-screenshot](https://spatie.be/docs/laravel-screenshot/v1/introduction). Browsershot remains the default driver, so no changes are needed for existing setups. You now also have the option to use Cloudflare Browser Rendering as an alternative driver. To configure the driver or customize other screenshot settings, publish the config and refer to the [documentation](https://spatie.be/docs/laravel-screenshot/v1/introduction):

```
php artisan vendor:publish --tag=laravel-screenshot-config
```

### Per-Collection Settings

The `social_images_generator_collections` field has been removed from the `site::social_media` defaults. The social images generator is now enabled per-collection using the `social_images_generator` toggle in the collection config.

> **Automated Migration**: Your existing settings are automatically migrated to per-collection configuration.

### Removed `generate_on_save` Config Option

The `generate_on_save` config option has been removed from the generator config:

```diff
  'generator' => [
-     'generate_on_save' => true,
  ],
```

You can safely remove this option from your published config file.

Social image generation now works as follows:

- **After saving**: Images are generated using Laravel's `defer()`, which runs after the response is sent (sync driver) or dispatches a queued job. Content hashing ensures images are only regenerated when content actually changes.
- **On demand**: If a generated image is missing (e.g. accidentally deleted), it will be regenerated on-the-fly on the next frontend request.

### Removed Twitter Image Generation

The social images generator no longer generates separate Twitter images. Only the Open Graph image is generated. The `twitter_summary` and `twitter_summary_large_image` config presets are still used to resize the shared image for the Twitter meta tags via Glide, but no separate images are generated for them.

If you have custom social images generator themes, you can remove the `twitter_summary.antlers.html` and `twitter_summary_large_image.antlers.html` templates — only `open_graph.antlers.html` is used.

### Template Changes

The `$group` variable has been removed from the data passed to social images generator templates. If your templates reference this variable, you'll need to update them.

### Image Storage Directory

Generated social images are now stored in `social_images/collection-{handle}/` and `social_images/taxonomy-{handle}/` subdirectories instead of `social_images/{handle}/`. This change adds support for taxonomy terms and avoids collisions between collections and taxonomies with the same handle.

Existing generated images in the old directory structure will not be migrated automatically. They will be regenerated in the new location on the next save or frontend request. You may delete the orphaned images in the old directories manually.

### Unified Social Image Field

The `seo_generated_og_image` field has been removed. The `seo_og_image` field now handles both generated and user-defined images automatically based on the `seo_generate_social_images` toggle state.

When the toggle is enabled, `seo_og_image` returns the generated image. When disabled, it returns the user-defined image. This change simplifies the data model and eliminates the need to check multiple fields.

**Custom Views:** If you published or customized views that reference `seo:generated_og_image`, update them to use `seo:og_image`.

## Sitemaps

### Per-Collection/Taxonomy Settings

The `excluded_collections` and `excluded_taxonomies` fields have been removed from the `site::indexing` defaults. The sitemap is now enabled per-collection/taxonomy using the `sitemap` toggle in the collection/taxonomy config.

> **Automated Migration**: Your existing settings are automatically migrated to per-collection/taxonomy configuration.

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

**Before:**
```php
Sitemap::register($sitemap);
```

**After:**
```php
Sitemap::index('english')->add($sitemap);
```

## Events

The `SeoDefaultSetSaved` event has been renamed to `SeoSetLocalizationSaved`. Both events handle the same localization data. The public property has been renamed from `$defaults` to `$localization`.

Additionally, new events have been added for config and deletion operations:
- `SeoSetConfigSaved` - Fired when a set's configuration is saved
- `SeoSetConfigDeleted` - Fired when a set's configuration is deleted
- `SeoSetLocalizationDeleted` - Fired when a localization is deleted

## GraphQL

The GraphQL API has been simplified and restructured for consistency. If you're using the GraphQL API, you'll need to update your queries.

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
| `analyticsDefaults` | `analyticsSiteSet` |
| `faviconsDefaults` | `faviconsSiteSet` |
| `generalDefaults` | `generalSiteSet` |
| `indexingDefaults` | `indexingSiteSet` |
| `socialMediaDefaults` | `socialMediaSiteSet` |
| `contentDefaults` | `collectionSet` / `taxonomySet` |

#### Removed Fields

The following fields have been removed as they are now configured per collection/taxonomy:

```diff
  {
    seoSet {
      site {
        indexing {
-         excluded_collections
-         excluded_taxonomies
        }
        socialMedia {
-         social_images_generator_collections
        }
      }
    }
  }
```

#### Disabled Features

Fields and entire sets belonging to disabled features are now completely removed from the schema. Previously, disabled feature fields were still present but returned `null` values. Now, if a feature is disabled in `config/advanced-seo.php`, its fields will not appear in the schema at all.

For example, if you disable the sitemap, all sitemap related fields will be removed. Similarly, disabling favicons removes the `favicons` field from `siteSet`, and disabling all analytics trackers removes the `analytics` field entirely.

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
| `twitter_title` | `og_title` |
| `twitter_image` | Use raw data `og_image` field |
| `og_image` | Use raw data `og_image` field |
| `generated_og_image` | Use raw data `og_image` field |

A new `twitter_card` field has been added to the `computed` type, returning the card size (`summary` or `summary_large_image`).

The following raw data fields have been removed from the `collectionSet`, `taxonomySet`, and `socialMediaSiteSet` types:

| Removed Field | Use Instead |
|---------------|-------------|
| `twitter_card` | Use `seoMeta { computed { twitter_card } }` |
| `twitter_title` | `og_title` |
| `twitter_description` | `og_description` |
| `twitter_summary_image` | `og_image` |
| `twitter_summary_large_image` | `og_image` |

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
