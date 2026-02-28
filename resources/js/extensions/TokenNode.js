import { InputRule, Node, PasteRule } from '@tiptap/core';
import { NodeSelection } from '@tiptap/pm/state';
import { ANTLERS_PATTERN } from '../utils/antlers.js';

function resolveToken(handle, tokens) {
    const token = tokens.find(token => token.handle === handle);

    return {
        display: token?.display || handle,
        attrs: {
            'data-token': handle,
            'class': 'inline-flex items-center justify-center border dark:border-0 font-normal antialiased whitespace-nowrap text-xs leading-5 dark:leading-5.5 px-2 rounded-sm select-none cursor-grab align-top shadow-ui-sm bg-sky-50 border-sky-300 text-sky-700 dark:bg-gray-800 dark:text-sky-300'
        },
    };
}

// Select node on mousedown so ProseMirror handles drag instead of the browser.
function selectNode(event, editor, getPos) {
    if (event.button !== 0) return;

    editor.view.focus();

    const { state, dispatch } = editor.view;
    const selection = NodeSelection.create(state.doc, getPos());
    dispatch(state.tr.setSelection(selection));
}

export const TokenNode = Node.create({
    name: 'token',
    inline: true,
    group: 'inline',
    atom: true,
    selectable: true,
    draggable: true,

    addOptions() {
        return {
            tokens: [],
        };
    },

    addAttributes() {
        return {
            handle: {
                default: null,
                parseHTML: (element) => element.dataset.token,
            },
        };
    },

    parseHTML() {
        return [{
            tag: 'span[data-token]',
        }];
    },

    renderHTML({ node }) {
        const { attrs, display } = resolveToken(node.attrs.handle, this.options.tokens);

        return ['span', attrs, display];
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            const { attrs, display } = resolveToken(node.attrs.handle, this.options.tokens);

            const dom = document.createElement('span');
            dom.textContent = display;

            Object.entries(attrs)
                .filter(([key, value]) => value !== undefined)
                .forEach(([key, value]) => dom.setAttribute(key, value));

            dom.addEventListener('mousedown', (event) => selectNode(event, editor, getPos));

            return { dom };
        };
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

                    if (!this.options.tokens.some(token => token.handle === handle)) return;

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

                    if (!this.options.tokens.some(token => token.handle === handle)) return;

                    const token = this.type.create({ handle });

                    state.tr.replaceWith(range.from, range.to, token);
                },
            }),
        ];
    },
});
