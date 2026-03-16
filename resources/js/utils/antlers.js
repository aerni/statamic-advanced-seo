export const ANTLERS_PATTERN = /\{\{\s*([^}]+?)\s*\}\}/;

export function normalizeHandle(raw) {
    return raw.replace(/\s*\|\s*/g, ' | ').replace(/(\s*\|\s*)+$/, '').trim();
}

/**
 * Converts an Antlers template string into ProseMirror JSON
 * suitable for Tiptap's `setContent()`.
 *
 * All {{ ... }} matches become token nodes.
 * Known handles show as field tokens; others show as expression tokens.
 *
 * "Hello {{ title }} world" → doc > paragraph > [text, token, text]
 */
export function parse(value) {
    const doc = { type: 'doc', content: [{ type: 'paragraph' }] };

    if (value == null || value === '') {
        return doc;
    }

    const content = [];

    let lastIndex = 0;

    for (const match of value.matchAll(new RegExp(ANTLERS_PATTERN.source, 'g'))) {
        if (match.index > lastIndex) {
            content.push({ type: 'text', text: value.slice(lastIndex, match.index) });
        }

        const handle = normalizeHandle(match[1]);

        content.push(handle
            ? { type: 'token', attrs: { handle } }
            : { type: 'text', text: match[0] },
        );

        lastIndex = match.index + match[0].length;
    }

    if (lastIndex < value.length) {
        content.push({ type: 'text', text: value.slice(lastIndex) });
    }

    doc.content[0].content = content;

    return doc;
}

/**
 * Converts a Tiptap editor JSON document back into an Antlers template string.
 *
 * doc > paragraph > [text, token, text] → "Hello {{ title }} world"
 */
export function stringify(json) {
    const nodes = json?.content?.[0]?.content;

    if (!nodes) {
        return '';
    }

    return nodes.map(node => node.type === 'token' ? `{{ ${node.attrs.handle} }}` : node.text).join('');
}
