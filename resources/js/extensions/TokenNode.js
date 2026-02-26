import { InputRule, Node, PasteRule } from '@tiptap/core';
import { ANTLERS_PATTERN } from '../utils/antlers.js';

const TOKEN_BASE = 'inline-flex items-center justify-center ring-1 ring-inset font-normal antialiased whitespace-nowrap text-xs leading-[1.375rem] px-2 rounded-sm select-none cursor-default align-top';
const TOKEN_VALID = 'bg-blue-50 ring-blue-300 text-blue-700 dark:bg-gray-800 dark:ring-blue-700 dark:text-blue-300';
const TOKEN_INVALID = 'bg-red-50 ring-red-400 text-red-700 dark:bg-gray-800 dark:ring-red-700 dark:text-red-300';

export const TokenNode = Node.create({
    name: 'token',
    inline: true,
    group: 'inline',
    atom: true,
    selectable: true,

    addOptions() {
        return {
            fields: [],
        };
    },

    addAttributes() {
        return {
            handle: {
                default: null,
                parseHTML: (element) => element.getAttribute('data-token-valid'),
            },
        };
    },

    parseHTML() {
        return [{
            tag: 'span[data-token-valid]',
        }];
    },

    renderHTML({ node }) {
        const handle = node.attrs.handle;
        const field = this.options.fields.find(field => field.handle === handle);
        const variant = field ? TOKEN_VALID : TOKEN_INVALID;
        const display = field?.display || handle;

        const attrs = {
            'data-token-valid': handle,
            contenteditable: 'false',
            class: `${TOKEN_BASE} ${variant}`,
        };

        if (!field) {
            attrs['data-token-invalid'] = '';
        }

        return ['span', attrs, display];
    },

    addCommands() {
        return {
            insertToken: (handle) => ({ chain }) => {
                return chain().insertContent([
                    { type: this.name, attrs: { handle } },
                    { type: 'text', text: ' ' },
                ]).run();
            },
        };
    },

    addInputRules() {
        return [
            new InputRule({
                // Matches {{ handle }} followed by a trailing space to trigger token creation while typing.
                find: new RegExp(ANTLERS_PATTERN.source + '\\s$'),
                handler: ({ state, range, match }) => {
                    const handle = match[1];
                    const token = this.type.create({ handle });
                    const space = state.schema.text(' ');

                    state.tr.replaceWith(range.from, range.to, [token, space]);
                },
            }),
        ];
    },

    addPasteRules() {
        return [
            new PasteRule({
                // Matches all {{ handle }} occurrences in pasted text to convert them into tokens.
                find: new RegExp(ANTLERS_PATTERN.source, 'g'),
                handler: ({ state, range, match }) => {
                    const handle = match[1];
                    const token = this.type.create({ handle });

                    state.tr.replaceWith(range.from, range.to, token);
                },
            }),
        ];
    },
});
