# AI Integration — Implementation Plan

## Overview

Add AI-powered content generation to text-based SEO fields, triggered from the existing `/` picker dropdown in the `TokenInputFieldtype`. Uses the Laravel AI SDK's agent-based architecture for multi-provider support.

---

## 1. Config: Add `enable_ai` Flag

**File:** `config/advanced-seo.php`

Add an `ai` section with an `enabled` flag, following the existing pattern of feature toggles (like `social_images.generator.enabled`, `sitemap.enabled`, etc.):

```php
'ai' => [
    'enabled' => false,
],
```

Default `false` — user must opt in.

---

## 2. Feature Gate: `Ai` Feature Class

**File:** `src/Features/Ai.php`

Follow the existing `Feature` pattern (see `SocialImagesGenerator`, `Favicons`, etc.):

```php
class Ai extends Feature
{
    public static function enabled(?Context $context = null): bool
    {
        return config('advanced-seo.ai.enabled', false)
            && class_exists(\Laravel\Ai\AiServiceProvider::class);
    }
}
```

Two conditions: config flag AND SDK installed. Uses `class_exists()` to check SDK availability — no need to use Statamic's `Composer::isInstalled()` (which is a real-time check via `composer show`). The `class_exists` approach is fast, reliable, and follows how Laravel itself checks for optional packages.

---

## 3. Extract Token Resolution: `TokenResolver`

**File:** `src/Support/TokenResolver.php`

Currently, token resolution lives in `TokenInputFieldtype` as protected methods (`resolveFieldTokens()`, `resolveSiteTokens()`, `defaultsBlueprints()`, `contentBlueprint()`, `allowedFieldTypeFields()`, `allowedSeoFields()`). The AI controller also needs token resolution. Extract this logic into a standalone class that both can use.

### Design

```php
namespace Aerni\AdvancedSeo\Support;

class TokenResolver
{
    protected const ALLOWED_FIELD_TYPES = [
        'token_input', 'text', 'textarea', 'markdown', 'bard',
    ];

    protected const ALLOWED_SEO_FIELDS = [
        'seo_title', 'seo_description', 'seo_og_title', 'seo_og_description',
    ];

    protected const SITE_TOKEN_FIELDS = [
        'separator', 'site_name',
    ];

    public function __construct(
        protected mixed $parent,
    ) {}

    public function fieldTokens(): Collection { ... }
    public function siteTokens(): Collection { ... }
    public function all(): Collection { ... }
}
```

- `$parent` is the field's parent — either an `Entry`, `Term`, `SeoSetLocalization`, `Collection`, or `Taxonomy`.
- For `Collection`/`Taxonomy` parents, `contentBlueprint()` uses `request('blueprint')` internally to resolve the specific blueprint variant — same pattern the fieldtype already uses. This keeps the constructor clean and works in both contexts (fieldtype preload and controller action) since both have `blueprint` as a request parameter.
- `fieldTokens()` replaces `resolveFieldTokens()`.
- `siteTokens()` replaces `resolveSiteTokens()`.
- `all()` returns `fieldTokens()->merge(siteTokens())->values()`.

The existing helpers (`defaultsBlueprints()`, `contentBlueprint()`, `allowedFieldTypeFields()`, `allowedSeoFields()`) move to protected methods on `TokenResolver`. The constants move there too.

### Refactor `TokenInputFieldtype`

After extraction, `TokenInputFieldtype` simplifies to:

```php
public function preload(): array
{
    $resolver = new TokenResolver($this->field->parent());

    $tokens = $resolver->all()
        ->reject(fn (array $field) => $field['handle'] === $this->field->handle())
        ->values();

    return [
        'tokens' => $tokens,
        'aiEnabled' => Ai::enabled(),
    ];
}
```

The fieldtype no longer needs the resolution methods, constants, or `SeoSetLocalization`/`Collection`/`Taxonomy` imports — `TokenResolver` handles it all.

### Usage in Controller

The controller uses the same class:

```php
$tokens = (new TokenResolver($content))->all();
```

### Blink Caching

The existing `Blink::once()` caching stays — it moves into `TokenResolver`'s methods. The cache keys remain namespaced by `$parent->id()`.

---

## 4. Agent: `SeoContentAgent`

**File:** `src/Ai/SeoContentAgent.php`

A Laravel AI SDK agent that generates SEO content. Implements `Agent`, uses `Promptable`:

```php
namespace Aerni\AdvancedSeo\Ai;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Concerns\Promptable;

class SeoContentAgent implements Agent
{
    use Promptable;

    public function __construct(
        protected string $fieldContext,
        protected array $availableTokens,
        protected ?string $currentValue,
        protected array $entryData,
    ) {}

    public function instructions(): string
    {
        return $this->buildSystemPrompt();
    }
}
```

The `instructions()` method builds a system prompt from the field context. The agent is instantiated per request with the specific field context, not a singleton.

### System Prompt Structure

The `buildSystemPrompt()` method assembles:

1. **Role and purpose** — "You are an SEO specialist writing {field purpose} for a content management system."
2. **Field context and character target** — based on the field context map:
   - `meta_title` → ~60 chars, SEO title for search results
   - `meta_description` → ~155 chars, SEO description for search results
   - `og_title` → ~60 chars, social sharing title
   - `og_description` → ~155 chars, social sharing description
3. **Available tokens** — listed with their handles and display names, instructing the AI to use `{{ handle }}` syntax where dynamic content makes sense
4. **Entry data** — the entry's title, collection, taxonomy, field values (truncated to reasonable lengths)
5. **Current value** — so the AI can see existing patterns
6. **Output rules**:
   - Output only the raw Antlers string, no explanation or wrapping
   - Use `{{ handle }}` for dynamic content where appropriate
   - Write static text where a compelling hook is needed
   - For titles: preserve `{{ separator }} {{ site_name }}` at the end unless there's a reason not to
   - Stay within the character target
   - No markdown formatting

### User Prompt

The user prompt passed to `->prompt()` is simply: "Generate an optimized {field purpose} for this content." — kept minimal since all context is in the system prompt.

---

## 5. Backend Controller: `AiGenerateController`

**File:** `src/Http/Controllers/Cp/AiGenerateController.php`

### Route

**File:** `routes/cp.php` — add inside the existing `advanced-seo` prefix group:

```php
Route::post('/ai/generate', AiGenerateController::class)->name('ai.generate');
```

This uses the CP route group, which gives us Statamic's CP middleware (auth, session, CSRF). The route name becomes `statamic.cp.advanced-seo.ai.generate` and the full URL is `/cp/advanced-seo/ai/generate`.

### Why CP routes, not action routes

The `routes/actions.php` file is for unauthenticated web-facing routes (like social image rendering). AI generation is a CP-only operation that needs authentication and CSRF protection — the CP route group provides this automatically.

### Controller Logic

```php
class AiGenerateController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        // 1. Validate request
        $validated = $request->validate([
            'field_context' => ['required', 'string', Rule::in([
                'meta_title', 'meta_description', 'og_title', 'og_description',
            ])],
            'current_value' => ['nullable', 'string'],
            'reference' => ['required', 'string'],
            'site' => ['required', 'string'],
            'blueprint' => ['nullable', 'string'], // Read by TokenResolver via request()

        ]);

        // 2. Check AI feature is enabled
        abort_unless(Ai::enabled(), 404);

        // 3. Resolve the entry/term from reference
        $content = Data::find($validated['reference']);
        abort_unless($content, 404);

        $content = $content->in($validated['site']);
        abort_unless($content, 404);

        // 4. Resolve blueprint fields (available tokens)
        $tokens = (new TokenResolver($content))->all();

        // 5. Build entry data context
        $entryData = $this->buildEntryData($content, $tokens);

        // 6. Run AI agent
        $agent = new SeoContentAgent(
            fieldContext: $validated['field_context'],
            availableTokens: $tokens,
            currentValue: $validated['current_value'],
            entryData: $entryData,
        );

        $response = $agent->prompt(
            "Generate an optimized {$this->fieldPurpose($validated['field_context'])} for this content."
        );

        return response()->json([
            'text' => (string) $response,
        ]);
    }
}
```

### Token Resolution

Uses the shared `TokenResolver` (see step 3):

```php
$tokens = (new TokenResolver($content))->all();
```

### Entry Data Extraction

`buildEntryData()` extracts useful context from the entry/term:

```php
protected function buildEntryData($content): array
{
    $data = [
        'title' => $content->get('title'),
    ];

    if ($content instanceof Entry) {
        $data['collection'] = $content->collection()->title();
        $data['url'] = $content->url();
    }

    if ($content instanceof Term) {
        $data['taxonomy'] = $content->taxonomy()->title();
    }

    // Include text-based field values (truncated)
    $blueprint = $content->blueprint();

    $blueprint->fields()->all()
        ->filter(fn (Field $field) => in_array($field->type(), ['text', 'textarea', 'markdown', 'bard']))
        ->take(10)
        ->each(function (Field $field) use ($content, &$data) {
            $value = $content->get($field->handle());
            if ($value && is_string($value)) {
                $data['fields'][$field->handle()] = Str::limit($value, 500);
            }
        });

    return $data;
}
```

### Error Handling

Wrap the AI call in a try/catch. Return JSON error responses:

```php
try {
    $response = $agent->prompt(...);
    return response()->json(['text' => (string) $response]);
} catch (Throwable $e) {
    report($e);
    return response()->json([
        'error' => __('advanced-seo::messages.ai_generation_failed'),
    ], 500);
}
```

---

## 6. Pass AI Availability to Frontend

**File:** `src/Fieldtypes/TokenInputFieldtype.php`

Add `aiEnabled` to the `preload()` return. After the `TokenResolver` refactor (step 3), the method is already simplified:

```php
public function preload(): array
{
    $resolver = new TokenResolver($this->field->parent());

    $tokens = $resolver->all()
        ->reject(fn (array $field) => $field['handle'] === $this->field->handle())
        ->values();

    return [
        'tokens' => $tokens,
        'aiEnabled' => Ai::enabled(),
    ];
}
```

The value becomes available as `props.meta.aiEnabled` in the Vue component.

---

## 7. Frontend: Add "Ask AI" to Token Suggestion Dropdown

### 6a. TokenSuggestionList.vue

**File:** `resources/js/components/ui/TokenSuggestionList.vue`

Add an "Ask AI" item at the top of the dropdown, visually separated from the field list. This item is conditionally rendered based on a new `aiEnabled` prop:

```
┌─────────────────────────────────┐
│ ✨ Ask AI                        │  ← new, conditionally shown
├─────────────────────────────────┤
│   Author Name                   │
│   Content                       │
│   Date                          │
│   Title                         │
└─────────────────────────────────┘
```

**Changes:**

1. Add props: `aiEnabled` (Boolean), `aiLoading` (Boolean)
2. Add the "Ask AI" button at the top, before the items list, with a divider
3. The "Ask AI" item is only visible when `aiEnabled` is `true` and there's no search query (it disappears when filtering)
4. When clicked/selected, emit an `askAi` event instead of calling the `command` prop
5. Keyboard navigation treats "Ask AI" as index `-1` (before the first field item). Arrow navigation wraps naturally.
6. The "Ask AI" item has a sparkle icon (✨) and distinct styling to differentiate it from field tokens

### 6b. TokenInputFieldtype.vue

**File:** `resources/js/components/fieldtypes/TokenInputFieldtype.vue`

**Changes:**

1. Add `aiEnabled` computed from `props.meta.aiEnabled`
2. Add `aiLoading` ref (Boolean, default `false`)
3. Add `generateUrl` — the CP route URL for AI generation
4. Pass `aiEnabled` and `aiLoading` props to `TokenSuggestionList`
5. Listen for `@askAi` event from `TokenSuggestionList`
6. Add `askAi()` method:

```javascript
async function askAi() {
    // Close dropdown
    if (suggestionState.value) {
        editor.value.chain().focus().deleteRange(suggestionState.value.range).run();
    }

    aiLoading.value = true;

    // Save current value for undo (TipTap History handles this natively)
    const fieldContext = resolveFieldContext(props.config.handle);

    try {
        const { $axios } = getCurrentInstance().appContext.config.globalProperties;

        const response = await $axios.post(
            cp_url('advanced-seo/ai/generate'),
            {
                field_context: fieldContext,
                current_value: props.value,
                reference: publishContext.reference.value,
                site: publishContext.site.value,
                blueprint: publishContext.blueprint?.value?.handle,
            }
        );

        // Replace entire field content with AI result
        withInternalUpdate(() => {
            editor.value.commands.setContent(parse(response.data.text, tokens.value));
            fieldtype.update(response.data.text);
        });
    } catch (error) {
        Statamic.$toast.error(
            error.response?.data?.error
            ?? __('advanced-seo::messages.ai_generation_failed')
        );
    } finally {
        aiLoading.value = false;
    }
}
```

7. Add `resolveFieldContext()` helper that maps the SEO field handle to the field context string:

```javascript
function resolveFieldContext(handle) {
    const map = {
        seo_title: 'meta_title',
        seo_description: 'meta_description',
        seo_og_title: 'og_title',
        seo_og_description: 'og_description',
    };
    return map[handle];
}
```

### 6c. Loading State

When `aiLoading` is `true`, the editor shows a loading animation. This can be a CSS animation on the editor container — a subtle shimmer or pulsing border effect to indicate work in progress, without blocking interaction.

Options (pick one during implementation):
- **Shimmer overlay** on the input field (preferred — simple, non-intrusive)
- **Pulsing border** color animation
- **Skeleton-style** placeholder text

### 6d. Undo Support

TipTap's `History` extension is already configured in the editor. Since we use `editor.value.commands.setContent(...)`, the previous state is in the undo history. `Cmd+Z` will restore the previous value naturally. No additional work needed.

---

## 8. Context from Publish Container

The frontend needs to send the entry/term reference and site to the backend. These are available from Statamic's publish context:

- `publishContext.reference.value` — the entry/term ID (e.g., `"entry::abc-123"`)
- `publishContext.site.value` — the current site handle (e.g., `"en"`)
- `publishContext.blueprint.value.handle` — the blueprint handle

**Important:** On SeoSet localization pages (`publishContext.name.value === 'seo-set-localizations'`), there is no specific entry/term reference — the user is editing defaults for an entire collection/taxonomy. In this case, the "Ask AI" item should be hidden because there's no entry context to generate from. The `aiEnabled` check in the Vue component should include:

```javascript
const aiAvailable = computed(() => {
    return props.meta.aiEnabled
        && publishContext.name.value !== 'seo-set-localizations';
});
```

---

## 9. Translation Strings

**File:** `lang/en/messages.php` (or wherever the addon stores translations)

Add:
- `ai_ask` → `"Ask AI"`
- `ai_generation_failed` → `"AI generation failed. Please try again."`

---

## 10. Tests

### Backend Tests (Pest)

**File:** `tests/Feature/AiGenerateTest.php`

1. **AI disabled** — returns 404 when `enable_ai` is false
2. **AI enabled but SDK not installed** — returns 404 (mock `class_exists` to return false, or skip if SDK isn't in dev dependencies)
3. **Validation** — rejects invalid `field_context` values
4. **Missing entry** — returns 404 for non-existent reference
5. **Successful generation** — mock the AI agent, verify the response structure
6. **Error handling** — mock an AI exception, verify 500 response with error message

Since the Laravel AI SDK won't be a dev dependency of the addon, the agent class tests should be conditional on SDK availability. Use `Ai::fake()` from the SDK for mocking when available.

### Frontend Tests

Manual testing is sufficient for the Vue components since the addon doesn't have a JS test setup. Key scenarios:
- "Ask AI" visible when `aiEnabled` is true and not on seo-set-localizations
- "Ask AI" hidden when `aiEnabled` is false
- Loading state displays correctly
- Error toast shows on failure
- Undo works after AI generation
- Dropdown closes before AI request fires

---

## File Change Summary

| File | Change |
|------|--------|
| `config/advanced-seo.php` | Add `ai.enabled` config key |
| `src/Features/Ai.php` | **New** — Feature gate class |
| `src/Support/TokenResolver.php` | **New** — Extracted token resolution logic |
| `src/Fieldtypes/TokenInputFieldtype.php` | Refactor to use `TokenResolver`, add `aiEnabled` to `preload()` |
| `src/Ai/SeoContentAgent.php` | **New** — Laravel AI SDK agent |
| `src/Http/Controllers/Cp/AiGenerateController.php` | **New** — POST endpoint |
| `routes/cp.php` | Add AI generate route |
| `resources/js/components/fieldtypes/TokenInputFieldtype.vue` | AI trigger, loading state, axios call |
| `resources/js/components/ui/TokenSuggestionList.vue` | "Ask AI" item in dropdown |
| `lang/en/messages.php` | AI-related translation strings |
| `tests/Feature/AiGenerateTest.php` | **New** — Backend tests |

---

## Implementation Order

1. Config + Feature gate (`config/advanced-seo.php`, `src/Features/Ai.php`)
2. Extract `TokenResolver` + refactor `TokenInputFieldtype` (`src/Support/TokenResolver.php`, `src/Fieldtypes/TokenInputFieldtype.php`)
3. Agent (`src/Ai/SeoContentAgent.php`)
4. Controller + route (`src/Http/Controllers/Cp/AiGenerateController.php`, `routes/cp.php`)
5. Backend preload — `aiEnabled` in `TokenInputFieldtype`
6. Frontend dropdown changes (`TokenSuggestionList.vue`)
7. Frontend trigger + request (`TokenInputFieldtype.vue`)
8. Translation strings
9. Tests
