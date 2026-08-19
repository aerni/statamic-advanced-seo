# Fix SEO cleanup when a collection or taxonomy is deleted

## Problem

`HandleSeoSet::deleteSeoSet()` calls `Seo::find($id)?->delete()`. The registry is built from live collections/taxonomies, so when `CollectionDeleted` / `TaxonomyDeleted` fires, the parent is often already gone and `find()` returns `null`. SEO config and localization YAML may be left behind.

## Approach

Delete SEO data by set id through the config/localization repositories (or Stache stores directly), without resolving the set via `Seo::find()`.

Mirror how config/localization paths are keyed: `{type}/{handle}.yaml` and `{type}/{locale}/{handle}.yaml`.

## Scope

- `src/Listeners/HandleSeoSet.php`
- Possibly a small helper on repositories if one doesn't exist
- Pest test: delete collection → SEO config and localizations for that handle are removed

Out of scope: `isValidSeoSet` guard, switch commands, redirect stores.

## Test plan

- Deleting a collection removes its SEO config and all locale localizations from Stache.
- Deleting a taxonomy does the same for taxonomy sets.
- Unrelated sets are untouched.
