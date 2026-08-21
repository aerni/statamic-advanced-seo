# Skip orphaned SEO set YAML in `isValidSeoSet`

## Problem

When a collection or taxonomy is deleted, its SEO YAML can remain on disk. Stache still loads those files because `isValidSeoSet()` only checks type and legacy site handles (`site/defaults`). Later, domain code calls `seoSet()` → `Seo::find()` returns `null` → TypeError.

Legacy site sets (`site/general.yaml`, etc.) are already filtered. Orphaned collection/taxonomy files are not.

## Approach

Extend `isValidSeoSet()` in `ParsesSeoSetPath` to require `Seo::find("{$type}::{$handle}")` for all types.

Files that fail the check stay on disk but are ignored by Stache — same behavior as leftover DB rows on export.

## Scope

- `src/Concerns/ParsesSeoSetPath.php`
- Tests in `tests/Stache/Stores/SeoSetConfigsStoreTest.php` and/or `SeoSetLocalizationsStoreTest.php`

Out of scope: deleting orphaned files, Eloquent export filtering, redirect stores.

## Test plan

- YAML for a registered collection is loaded.
- YAML for a non-existent collection handle is filtered out.
- `site/defaults.yaml` still loads; `site/general.yaml` still rejected.
