# Upgrading from v2 to v3

v3 introduces a significant internal refactor that improves the architecture and maintainability of the addon and improves the overall user experience. Most breaking changes have automated upgrade scripts, so the upgrade should be seamless for most users.

## Requirements

- PHP 8.3+
- Laravel 12+
- Statamic 6+

## Permission Breaking Changes

The permission system has been simplified. Old granular permissions have been replaced with three new permissions:

- `configure seo` - Full access to all SEO settings, defaults, and content editing
- `edit seo defaults` - Edit collection and taxonomy defaults
- `edit seo content` - Access the SEO tab on entries and terms

**Important:** The `edit seo defaults` and `edit seo content` permissions work in conjunction with Statamic's native collection and taxonomy permissions. For example, to edit SEO defaults for the Pages collection, a user needs the `edit seo defaults` permission AND either Statamic's `configure collections` or `edit pages entries` permission.

> **Automated Migration**: User role permissions are automatically migrated. All existing roles receive the `edit seo content` permission to maintain backward compatibility.

## Disabled Collections & Taxonomies Config

The `disabled` configuration option has been removed from `config/advanced-seo.php`:

```diff
- 'disabled' => [
-     'collections' => [],
-     'taxonomies' => [],
- ],
```

> **Automated Migration**: Your existing disabled collections and taxonomies are automatically migrated. Collections and taxonomies can now be disabled individually through the Control Panel by clicking the "Configure" button on each set.

## Sitemap Exclusion Settings

The `excluded_collections` and `excluded_taxonomies` fields have been removed from the `site::indexing` defaults. The sitemap is now enabled per-collection/taxonomy using the `sitemap` toggle in the collection/taxonomy config.

> **Automated Migration**: Your existing settings are automatically migrated to per-collection/taxonomy configuration.

## Social Images Generator Settings

The `social_images_generator_collections` field has been removed from the `site::social_media` defaults. The social images generator is now enabled per-collection using the `social_images_generator` toggle in the collection config.

> **Automated Migration**: Your existing settings are automatically migrated to per-collection configuration.

## Social Images Generator Templates

The `$group` variable has been removed from the data passed to social images generator templates. If your templates reference this variable, you'll need to update them.

## Localization Origin Field

The `origin` field has been removed from localizations. Origin configuration is now stored centrally in the set config using an `origins` array.

> **Automated Migration**: Your existing origin configuration is automatically migrated from localizations to the set config.

## Event Breaking Changes

The `SeoDefaultSetSaved` event has been renamed to `SeoSetLocalizationSaved`. Both events handle the same localization data. The public property has been renamed from `$defaults` to `$localization`.

Additionally, new events have been added for config and deletion operations:
- `SeoSetConfigSaved` - Fired when a set's configuration is saved
- `SeoSetConfigDeleted` - Fired when a set's configuration is deleted
- `SeoSetLocalizationDeleted` - Fired when a localization is deleted

## Eloquent Driver Breaking Changes

If you're using the Eloquent driver, the database schema has changed:

The `advanced_seo_defaults` table has been replaced by `seo_set_localizations`. Additionally, a new `seo_set_configs` table has been added to store configuration data (enabled state, origins, etc.).

> **Automated Migration**: When updating to v3.0, new migrations will be published and run automatically. Data will be migrated from the old table to the new tables, and the old `advanced_seo_defaults` table will be dropped.

If you have any custom code that directly queries the `advanced_seo_defaults` table, you'll need to update it to use the new table structure.

## GraphQL Breaking Changes

The GraphQL API has been simplified and renamed for consistency. If you're using the GraphQL API, you'll need to update your queries.

### Query Renamed

The root query has been renamed from `seoDefaults` to `seoSet`:

```diff
- seoDefaults {
+ seoSet {
    ...
  }
```

### Type Names Changed

All GraphQL type names have been updated to use "Set" instead of "Defaults":

| Old Type Name | New Type Name |
|---------------|---------------|
| `siteDefaults` | `siteSet` |
| `analyticsDefaults` | `analyticsSiteSet` |
| `faviconsDefaults` | `faviconsSiteSet` |
| `generalDefaults` | `generalSiteSet` |
| `indexingDefaults` | `indexingSiteSet` |
| `socialMediaDefaults` | `socialMediaSiteSet` |
| `contentDefaults` | `collectionSet` / `taxonomySet` |

### Removed Fields

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
