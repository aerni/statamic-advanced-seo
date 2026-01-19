# Upgrade Guide

## Upgrading to v3.0

### GraphQL Breaking Changes

The GraphQL API has been simplified and renamed for consistency. If you're using the GraphQL API, you'll need to update your queries.

#### Query Renamed

The root query has been renamed from `seoDefaults` to `seoSet`:

```diff
- seoDefaults {
+ seoSet {
    ...
  }
```

#### Type Names Changed

All GraphQL type names have been updated to use "Set" instead of "Defaults":

| Old Type Name | New Type Name |
|---------------|---------------|
| `seoDefaults` | `seoSet` |
| `siteDefaults` | `siteSet` |
| `analyticsDefaults` | `analyticsSiteSet` |
| `faviconsDefaults` | `faviconsSiteSet` |
| `generalDefaults` | `generalSiteSet` |
| `indexingDefaults` | `indexingSiteSet` |
| `socialMediaDefaults` | `socialMediaSiteSet` |
| `contentDefaults` | `collectionSet` / `taxonomySet` |

#### Content Defaults Split

The `contentDefaults` type has been split into separate `collectionSet` and `taxonomySet` types for clarity. The fields remain the same.

#### Migration Examples

**Before:**
```graphql
{
  seoDefaults {
    site {
      general {
        site_name
      }
      analytics {
        tracker_type
      }
    }
    collection(handle: "pages") {
      title
      description
    }
    taxonomy(handle: "tags") {
      title
    }
  }
}
```

**After:**
```graphql
{
  seoSet {
    site {
      general {
        site_name
      }
      analytics {
        tracker_type
      }
    }
    collection(handle: "pages") {
      title
      description
    }
    taxonomy(handle: "tags") {
      title
    }
  }
}
```

#### Inline Fragments

If you're using inline fragments with type conditions, update the type names:

**Before:**
```graphql
{
  seoDefaults {
    site {
      general {
        ... on generalDefaults {
          site_name
        }
      }
    }
  }
}
```

**After:**
```graphql
{
  seoSet {
    site {
      general {
        ... on generalSiteSet {
          site_name
        }
      }
    }
  }
}
```

#### Removed Fields

The following fields have been removed from the GraphQL API as they are now configured per collection/taxonomy:

**From `indexingSiteSet` (previously `indexingDefaults`):**
- `excluded_collections` - Removed. Sitemap inclusion is now configured via the `sitemap` field on each collection's config.
- `excluded_taxonomies` - Removed. Sitemap inclusion is now configured via the `sitemap` field on each taxonomy's config.

**From `socialMediaSiteSet` (previously `socialMediaDefaults`):**
- `social_images_generator_collections` - Removed. The social images generator is now enabled on each collection's config. Collections without it enabled will return `null` for social image generator fields.

These configuration options have been moved to individual collection/taxonomy settings. You no longer need to query these fields to determine availability - simply check if the returned data is `null`.

**Before:**
```graphql
{
  seoDefaults {
    site {
      indexing {
        excluded_collections
        excluded_taxonomies
      }
      socialMedia {
        social_images_generator_collections
      }
    }
  }
}
```

**After:**

These fields no longer exist. Sitemap inclusion and social image generation are now configured per collection/taxonomy. Query the collection/taxonomy directly and handle `null` responses for disabled features.

#### Code Generation

If you're using GraphQL code generation tools that create TypeScript or other types from the schema, regenerate your types after upgrading to reflect the new type names.
