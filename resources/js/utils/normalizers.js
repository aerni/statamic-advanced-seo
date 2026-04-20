const normalizers = new Map();

// ─── Public API ──────────────────────────────────────────────────────────────

export function normalize(fieldtype, value, meta) {
    return normalizers.get(fieldtype)?.(value, meta);
}

export function add(fieldtype, normalizer) {
    normalizers.set(fieldtype, normalizer);
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function stripTags(html) {
    return String(html ?? '')
        .replace(/<[^>]*>|\s+/g, ' ')
        .trim();
}

// ─── Default Normalizers ─────────────────────────────────────────────────────

function normalizeText(value) {
    return stripTags(value);
}

function normalizeTextarea(value) {
    return stripTags(value);
}

function normalizeMarkdown(value) {
    return stripTags(markdown(value));
}

function normalizeBard(value) {
    const extractText = (nodes) => nodes
        .map(node => node.type === 'text' ? node.text ?? '' : extractText(node.content ?? []))
        .join(' ');

    return stripTags(extractText(value));
}

function normalizeUsers(value, meta) {
    const names = (meta.data ?? [])
        .map(item => item.title)
        .filter(Boolean);

    return new Intl.ListFormat('en', { type: 'conjunction' }).format(names);
}

add('text', normalizeText);
add('textarea', normalizeTextarea);
add('markdown', normalizeMarkdown);
add('bard', normalizeBard);
add('users', normalizeUsers);
