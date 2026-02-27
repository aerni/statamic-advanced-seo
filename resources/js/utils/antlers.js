export const ANTLERS_PATTERN = /\{\{\s*([a-zA-Z0-9_\-]+)\s*\}\}/;

/**
 * Converts an Antlers template string into ProseMirror JSON
 * suitable for Tiptap's `setContent()`.
 *
 * Only recognized token handles become token nodes.
 * Unrecognized handles are kept as raw Antlers text.
 *
 * "Hello {{ title }} world" → doc > paragraph > [text, token, text]
 */
export function parse(value, tokens) {
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

        const handle = match[1];

        content.push(tokens.some(token => token.handle === handle)
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
