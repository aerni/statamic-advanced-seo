import { injectPublishContext } from '@statamic/cms/ui';

/**
 * Truncate a string to a maximum length, appending ' ...' if needed.
 */
function truncate(value, max) {
    return value?.length > max
        ? value.substring(0, max - 4) + ' ...'
        : value;
}

/**
 * Strip HTML tags from a string.
 */
function stripTags(html) {
    return html.replace(/<[^>]*>/g, '');
}

/**
 * Extract plain text from a ProseMirror (Bard) JSON structure.
 * Iterative traversal collecting text nodes.
 */
function extractBardText(value) {
    if (typeof value === 'string') {
        return stripTags(value);
    }

    if (!Array.isArray(value)) return '';

    let text = '';

    const queue = [...value];

    while (queue.length > 0) {
        const item = queue.shift();

        if (!item?.type) continue;

        if (item.type === 'text') {
            text += ` ${item.text || ''}`;
        }

        queue.unshift(...(item.content ?? []));
    }

    return text.trim();
}

/**
 * Convert a field value to plain text based on the field type.
 */
function toPlainText(value, fieldType) {
    switch (fieldType) {
        case 'markdown':
            return stripTags(markdown(value));
        case 'bard':
            return extractBardText(value);
        default:
            return typeof value === 'string' ? value : '';
    }
}

/**
 * Composable for resolving SEO field values from the publish container.
 *
 * Provides reactive resolution of field values with support for:
 * - seo_source fields (auto, default, custom)
 * - Field type conversion (markdown, bard) to plain text
 * - Template interpolation ({{ handle }} and @field:handle)
 * - Auto chain following for recursive resolution
 *
 * @returns {{ resolve: Function, truncate: Function }}
 */
export function useSeoValues() {
    const publishContainer = injectPublishContext();

    const fields = Object.fromEntries(
        Object.values(publishContainer.blueprint.value.tabs)
            .flatMap(tab => tab.sections)
            .flatMap(section => section.fields)
            .map(field => [field.handle, field])
    );

    function getField(handle) {
        return fields[handle];
    }

    function getFieldValue(handle) {
        return publishContainer.values.value[handle];
    }

    function getFieldMeta(handle) {
        return publishContainer.meta.value[handle];
    }

    /**
     * Replace {{ handle }} and @field:handle references in a string
     * with the corresponding publish field values.
     */
    function interpolate(value) {
        if (!value || typeof value !== 'string') return value;

        return value
            .replace(/\{\{\s*([a-zA-Z0-9_\-]+)\s*\}\}/g, (_, handle) => toPlainText(getFieldValue(handle), getField(handle).type))
            .replace(/@field:([a-zA-Z0-9_\-]+)/g, (_, handle) => toPlainText(getFieldValue(handle), getField(handle).type));
    }

    /**
     * Resolve a seo_source text field value, following the field.auto chain.
     */
    function resolve(handle) {
        const value = getFieldValue(handle);
        const field = getField(handle);

        switch (value?.source) {
            case 'auto':
                return resolve(field.auto);
            case 'default':
                return interpolate(getFieldMeta(handle).default);
            case 'custom':
                return interpolate(value.value);
            default:
                return toPlainText(value, field.type);
        }
    }

    return {
        resolve,
        truncate,
    };
}
