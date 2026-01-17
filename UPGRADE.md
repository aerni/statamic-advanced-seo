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

#### Code Generation

If you're using GraphQL code generation tools that create TypeScript or other types from the schema, regenerate your types after upgrading to reflect the new type names.
