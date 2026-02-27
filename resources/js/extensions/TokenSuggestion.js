import { Extension } from '@tiptap/core';
import Suggestion from '@tiptap/suggestion';

export const TokenSuggestion = Extension.create({
    name: 'tokenSuggestion',

    addOptions() {
        return {
            tokens: [],
            suggestion: {},
        };
    },

    addProseMirrorPlugins() {
        return [
            Suggestion({
                editor: this.editor,
                char: '/',
                decorationClass: 'suggestion-active',
                allowedPrefixes: [' ', null],
                allowSpaces: false,
                items: ({ query }) => {
                    if (!query) {
                        return this.options.tokens;
                    }

                    const search = query.toLowerCase();

                    return this.options.tokens.filter(field => field.display.toLowerCase().includes(search) || field.handle.toLowerCase().includes(search));
                },
                command: ({ editor, range, props: field }) => {
                    editor.chain()
                        .deleteRange(range)
                        .insertContent([
                            { type: 'token', attrs: { handle: field.handle } },
                            { type: 'text', text: ' ' },
                        ])
                        .run();
                },
                ...this.options.suggestion,
            }),
        ];
    },
});
