# Advanced SEO

A comprehensive SEO addon for Statamic. First-class Statamic package, not a generic Laravel SEO tool.

## Foundational Context

- php - ^8.4
- laravel/framework - ^12.0
- statamic/cms - ^6.0
- pestphp/pest - ^4.0
- laravel/pint - ^1.26 (laravel preset)
- spatie/browsershot - ^5.2
- spatie/schema-org - ^3.23
- statamic/eloquent-driver - ^5.0 (dev)
- orchestra/testbench - ^10.0 (dev)
- vue - ^3.5
- tailwindcss - ^4.1

## Core Domain

- **SeoSet** is the central data object. One per collection, taxonomy, and site. Each has a `SeoSetConfig` and a `SeoSetLocalization` per site.
- **SeoSetGroup** groups SeoSets by type (site, collections, taxonomies).
- Access data through the `Seo` facade — check the facade file for available methods. Use its methods to reach config and localizations (e.g. `Seo::find('collections::pages')->config()`). Only use `SeoConfig` and `SeoLocalization` facades directly when you need low-level repository access.
- Data merging order: site defaults → collection/taxonomy defaults → entry/term overrides.
- All SEO output is **computed state**. Never read or write SEO data directly from entries — always go through the domain layer.

### Context System
`Context` encapsulates resolution context (parent, type, handle, scope, site). Create via `Context::from($model)` or construct directly.

### Dual Driver Storage
- **File/Stache (default)** and **Eloquent (optional)**. Never assume Eloquent is active.
- Both drivers implement the same contracts in `Contracts/`. When adding a new data type, implement for both `Stache/Repositories/` and `Eloquent/`.

## Patterns

When adding new functionality, follow the existing pattern — check sibling files.

- **View Cascade:** `CascadeComposer` builds `ViewCascade` from view data. Chains site defaults → content config → page data → computed data. Output is the `seo` template variable.
- **Blink caching:** Namespaced keys: `advanced-seo::{type}::{handle}::{suffix}`. `SeoSet::flushBlink()` flushes by prefix.

## Conventions

- Laravel Pint with `laravel` preset. Run `vendor/bin/pint` before finalizing.
- Use `saveQuietly()` in setup code to avoid triggering events.
- **Pest** (not PHPUnit). Do not convert to PHPUnit. Run with `vendor/bin/pest` from package root.
- Use `PreventsSavingStacheItemsToDisk` in tests that create Statamic content.

## Documentation

Official docs: https://advanced-seo.michaelaerni.ch
