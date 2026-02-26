import { Extension } from '@tiptap/core';
import Suggestion from '@tiptap/suggestion';

export const FieldSuggestion = Extension.create({
    name: 'fieldSuggestion',

    addOptions() {
        return {
            fields: [],
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
                        return this.options.fields;
                    }

                    const search = query.toLowerCase();

                    return this.options.fields.filter(field => field.display.toLowerCase().includes(search) || field.handle.toLowerCase().includes(search));
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
