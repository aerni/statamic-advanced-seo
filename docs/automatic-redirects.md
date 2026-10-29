# Automatic redirects

Automatic redirects preserve old public URLs when content is saved or moved through Statamic. Both Stache and Eloquent storage are supported.

## Covered changes

| Content | Changes that create redirects |
| --- | --- |
| Entries | Slug, date, and stored route-field changes, including inherited localization values |
| Structured entries | Tree moves and parent URL changes, including children and grandchildren |
| Mounted content | URL changes to an existing mount entry, including translated and nested mounts |
| Terms | Base and localized slug changes, for canonical and collection-specific term URLs |
| Collection taxonomy indexes | Existing mount URL changes, including tree moves, translations, nested mounts, and collections with no entries or terms |

For example, renaming an existing mount from `/blog` to `/news` can redirect all of these:

- `/blog/post` → `/news/post`
- `/blog/tags` → `/news/tags`
- `/blog/tags/design` → `/news/tags/design`

The current public URL is compared with the saved old URL before a redirect is created. Date changes are independent of the editor's CP timezone, including repeated local times at the end of daylight saving time.

## Eligibility

- The redirect feature must be enabled.
- Entry redirects follow the affected entry collection's redirect setting.
- Term and taxonomy-index redirects follow the taxonomy's redirect setting. Disabling entry redirects on a mounted collection does not disable its public taxonomy indexes.
- An entry's own old and new URLs must be public. A draft parent or mount can still move public children, mounted entries, terms, or taxonomy indexes.
- Terms and taxonomy indexes need a resolvable template on the affected site. Collection-specific URLs must belong to both the taxonomy and the collection on that site.
- New entries and terms have no previous public URL to redirect.

## Existing and reclaimed URLs

Manual redirects are preserved, and existing sources are not overwritten. A URL still owned by a public entry, term, or taxonomy index is protected from a new automatic redirect.

Entry destinations use entry references so redirects follow later moves. Term and taxonomy-index destinations are updated to avoid redirect chains. When content reclaims an old URL, automatic redirects using that URL are removed. Deleting an entry, term, collection, or taxonomy removes the relevant automatic redirects to destinations that no longer exist; manual redirects are preserved.

## Boundaries

This release watches content saves and tree moves. It does not watch changes to collection route definitions or assigning, replacing, or removing a mount in collection settings. These configuration changes need separate event handling and are outside this release's agreed scope.

Changes made directly to files or database rows, or with `saveQuietly()`, bypass the content events. Arbitrary external changes to computed route values or on-demand Antlers tags are not tracked.

The existing save/move implementation also does not promise redirects for adding the first slug to an already-public slugless entry, empty or false overrides of collection `inject` route values, or intermediate child URLs after multiple parent changes in one Eloquent script. The Eloquent driver's routing limitations for overridden localized term slugs remain upstream behavior.
