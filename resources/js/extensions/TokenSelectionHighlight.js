import { Extension } from '@tiptap/core';
import { Plugin } from '@tiptap/pm/state';
import { Decoration, DecorationSet } from '@tiptap/pm/view';

export const TokenSelectionHighlight = Extension.create({
    name: 'tokenSelectionHighlight',

    addProseMirrorPlugins() {
        return [new Plugin({
            props: {
                decorations(state) {
                    const { selection, doc } = state;
                    if (selection.empty) return DecorationSet.empty;

                    const decorations = [];
                    doc.nodesBetween(selection.from, selection.to, (node, pos) => {
                        if (node.type.name === 'token') {
                            decorations.push(Decoration.node(pos, pos + node.nodeSize, { class: 'is-selected' }));
                        }
                    });

                    return DecorationSet.create(doc, decorations);
                },
            },
        })];
    },
});
