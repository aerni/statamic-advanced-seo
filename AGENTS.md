# Advanced SEO – AI Context Instructions

This directory contains the **Advanced SEO** Statamic addon by Aerni.
It is a first-class Statamic package, not a generic Laravel SEO tool.

AI assistants working in this directory should follow the context below
before suggesting changes, explanations, or integrations.

---

## High-Level Purpose

Advanced SEO provides a **structured, multi-layer SEO system** for Statamic
with strong support for:

- Multi-site & localization
- Site-wide settings and defaults
- Collection and Taxonomy settings and defaults
- Per-entry & per-term overrides of collection and taxonomy defaults
- Computed metadata output (Antlers, Blade, GraphQL)
- Sitemaps and social image generation

SEO data is **never rendered directly from raw fields**.
All output is computed and merged through the package’s domain layer.

---

## Mental Model

Advanced SEO should be understood as a **deterministic SEO rules engine**.

SEO output is:
- Configured declaratively
- Computed through a domain model
- Independent of request lifecycle and rendering context

It is not a helper library or a runtime state container.

---

## Core Concepts

### SEO Sets & Groups
- A **SeoSetGroup** represents a domain (defaults for site, collections, and taxonomies).
- Each group contains multiple **SeoSets** (a set per Statamic collection and taxonomy).
- Each set has a configuration represented by the **SeoSetConfig**.
- Each set can be localized in the available sites with a **SeoSetLocalization**.

SEO Sets are accessed via the `Seo` facade and computed on the fly.

---

## Storage Architecture

Advanced SEO uses **driver-based persistence**, similar to Statamic core.

### File / Stache Driver (Default)
- Uses custom Stache stores:
  - `SeoSetConfigsStore`
  - `SeoSetLocalizationsStore`
- Data lives in `config('advanced-seo.directory')`
- Git-friendly and versionable

### Eloquent Driver (Optional)
- Enabled only when:
  - `statamic/eloquent-driver` is installed
  - `advanced-seo.driver=eloquent`
- Repositories are swapped via `Statamic::repository(...)`
- Some parts are marked legacy / transitional

AI should **not assume Eloquent is always active**.

---

## Fieldtypes & Blueprints

- Advanced SEO provides a **custom fieldtype** for entries and terms.
- SEO fields are **blueprint-configurable**.
- The fieldtype does not render output — it stores structured SEO data.

Merging order (simplified):
1. Site defaults
2. Collection / taxonomy defaults
3. Entry / term overrides

---

## Rendering & Output

### Antlers & Blade
- SEO output is injected via a **view cascade**:
  - `advanced-seo::head`
  - `advanced-seo::body`
- Blade directive:
  ```blade
  @seo
  ```
- All public SEO data is exposed as a computed `seo` array.

### GraphQL
When enabled:
- Adds `seo` field to `EntryInterface` and `TermInterface`
- Provides strongly typed SEO, sitemap, and defaults queries
- GraphQL output mirrors Antlers / Blade output

---

## Control Panel Integration

- CP navigation is built dynamically from visible SeoSetGroups
- Access is permission-based (`SeoSetPolicy`)
- Permissions:
  - `configure seo` – full access
  - `edit seo` – edit defaults only

---

## Sitemaps & Social Images

### Sitemaps
- Supports collections, taxonomies, and custom routes
- Available via HTTP and GraphQL

### Social Images
- Preset-based image generation
- Queueable jobs
- CP bulk action: `GenerateSocialImages`

---

## Code Style & Conventions (Important)

All code suggestions and modifications must follow **Laravel and Statamic best practices**.

### Laravel Conventions
- Use explicit return types and parameter type hints
- Use constructor property promotion where applicable
- Prefer dependency injection over facades (except where Statamic expects facades)
- Avoid static helpers unless already established in the codebase
- Follow PSR-12 formatting (Laravel Pint compatible)

### Statamic Conventions
- Prefer **Stache + repositories** over manual file access
- Use Statamic facades (`Statamic::repository`, `Stache`, `Nav`, `Permission`, etc.)
- Respect Statamic lifecycle hooks and events
- Do not bypass policies or permission checks
- Treat content as immutable value objects where applicable

### View & Template Conventions
- No inline PHP in Antlers or Blade templates
- Do not render SEO directly from raw fields
- Always rely on computed SEO output and view composers

AI should match the **existing style and architecture** in this package
and avoid introducing patterns that conflict with Statamic’s design.

---

## Important Assumptions for AI

- Do **not** bypass the Seo domain layer.
- Do **not** read or write SEO output directly from entries.
- Prefer existing repositories, facades, and contracts.
- Respect Statamic conventions (Stache, policies, view composers).
- Treat SEO metadata as **computed state**, not static fields.

---

## Creativity & Forward-Looking Ideas

AI assistants are encouraged to **challenge the current implementation**
when the user is exploring ideas, future directions, or larger refactors.

This includes:
- Proposing new abstractions, APIs, or architectural shifts
- Reimagining how the SEO domain could be modeled or composed
- Identifying opportunities to reduce complexity or unify concepts

When doing so:
- Clearly separate **conceptual proposals** from production-ready changes
- Articulate trade-offs, risks, and migration paths
- Do not implement large refactors unless explicitly requested

This package values **deliberate evolution**, not accidental change.

---

## Idea vs Implementation Mode

AI assistants should explicitly distinguish between two modes:

- **Idea Mode** — Explore long-term design, alternatives, and vNext concepts.
  Existing assumptions may be questioned, but not bypassed in production code.
- **Implementation Mode** — Preserve architectural consistency, minimize diffs,
  and strictly follow Statamic and package conventions.

If implementation is not explicitly requested, default to **Idea Mode**
for refactors, redesigns, or new capabilities.

---

## Examples of High-Quality Proposals

Strong proposals:
- Introduce a new abstraction and explain how it clarifies the SEO domain
- Propose a vNext API with migration and compatibility considerations
- Identify conceptual overlap and suggest a unifying model

Weak proposals:
- Recreate existing behavior without a clear benefit
- Add helpers or services that bypass the Seo domain layer
- Suggest breaking changes without addressing impact

---

## vNext Thinking

When brainstorming, AI assistants may reason in terms of a hypothetical
**Advanced SEO vNext**:

- Backward compatibility matters, but is not absolute
- Consider how the system would be designed if started today
- Balance innovation with Statamic’s mental model and ecosystem

These ideas should remain **conceptual** unless explicitly requested
for implementation.

---

## Core Values

Advanced SEO is guided by the following principles:

- **Computed over imperative** — SEO is derived state, never mutated at runtime
- **Domain-first design** — All behavior flows through the Seo domain layer
- **Determinism** — Identical input produces identical output across all renderers
- **Explicit modeling** — SEO rules are modeled, not inferred from side effects
- **Long-term correctness** — Stability and clarity outweigh convenience

---

## Performance & Caching

SEO computation should remain:
- Deterministic
- Side-effect free
- Safe to cache at appropriate boundaries

Avoid request-scoped mutation, static memoization, or hidden caches
that alter behavior based on execution order.

---

## Non-Goals

Advanced SEO intentionally does **not** aim to:

- Be a generic, framework-agnostic SEO toolkit
- Replace Statamic’s content modeling or editorial workflows
- Offer per-request, imperative SEO mutation APIs
- Expose raw SEO fields for direct rendering
- Optimize for short-term convenience over architectural integrity

Proposals that approach these areas must be clearly justified
and framed as exploratory rather than default behavior.

---

## Good Questions to Ask Before Changes

AI should clarify:
- Is this site multi-site?
- Is GraphQL enabled?
- Is the file or Eloquent driver in use?
- Is output intended for Antlers, Blade, or GraphQL?

---

## Documentation

Official docs:
https://advanced-seo.michaelaerni.ch

This file exists to prevent repeated context explanation
and to align AI assistance with Advanced SEO’s architecture.
