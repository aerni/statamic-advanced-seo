import { Extension } from '@tiptap/core';

export const SingleLine = Extension.create({
    name: 'singleLine',

    addKeyboardShortcuts() {
        return { Enter: () => true };
    },
});
