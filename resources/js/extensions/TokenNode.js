import { InputRule, Node, PasteRule } from "@tiptap/core";
import { NodeSelection, TextSelection } from "@tiptap/pm/state";
import { ANTLERS_PATTERN, normalizeHandle } from "../utils/antlers.js";

const fieldTokenClass =
    "inline-flex items-center justify-center border dark:border-0 font-normal antialiased whitespace-nowrap text-xs leading-5 dark:leading-5.5 px-2 rounded-sm select-none cursor-default align-top shadow-ui-sm bg-sky-50 border-sky-300 text-sky-700 dark:bg-gray-800 dark:text-sky-300 hover:!bg-sky-100 dark:hover:!bg-gray-700 [&.ProseMirror-selectednode]:!bg-sky-100 [&.is-selected]:!bg-sky-100 dark:[&.ProseMirror-selectednode]:!bg-gray-700 dark:[&.is-selected]:!bg-gray-700";
const expressionTokenClass =
    "inline-flex items-center justify-center gap-1.5 border dark:border-0 antialiased whitespace-nowrap text-xs leading-5 dark:leading-5.5 px-2 rounded-sm select-none cursor-default align-top shadow-ui-sm bg-gray-50 border-gray-300 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:!bg-gray-100 dark:hover:!bg-gray-700 [&.ProseMirror-selectednode]:!bg-gray-100 [&.is-selected]:!bg-gray-100 dark:[&.ProseMirror-selectednode]:!bg-gray-700 dark:[&.is-selected]:!bg-gray-700";
const expressionModifierClass = "border-s border-inherit ps-1.5 opacity-70";

// ─── Token resolution ────────────────────────────────────────────────────────

function parseExpression(handle) {
    const parts = handle
        .split("|")
        .map((p) => p.trim())
        .filter(Boolean);

    return {
        field: parts[0],
        modifiers: parts.slice(1),
    };
}

function resolveFieldToken(token) {
    return {
        display: token.display,
        attrs: {
            "data-token": token.handle,
            class: fieldTokenClass,
        },
    };
}

function resolveExpressionToken(handle) {
    const { field, modifiers } = parseExpression(handle);

    return {
        display: handle,
        field,
        modifiers,
        attrs: {
            "data-token": handle,
            class: expressionTokenClass,
        },
    };
}

function resolveToken(handle, tokens) {
    const token = tokens.find((token) => token.handle === handle);

    return token ? resolveFieldToken(token) : resolveExpressionToken(handle);
}

// ─── Node interactions ───────────────────────────────────────────────────────

// Select node on mousedown so ProseMirror handles drag instead of the browser.
function selectNode(event, editor, getPos) {
    if (event.button !== 0) return;

    editor.view.focus();

    const { state, dispatch } = editor.view;
    const selection = NodeSelection.create(state.doc, getPos());
    dispatch(state.tr.setSelection(selection));
}

function expandToken(editor, pos, node) {
    const text = `{{ ${node.attrs.handle} }}`;
    const textNode = editor.state.schema.text(text);

    const tr = editor.state.tr.replaceWith(pos, pos + node.nodeSize, textNode);
    tr.setSelection(TextSelection.create(tr.doc, pos + text.length - 3));

    editor.view.dispatch(tr);
    editor.view.focus();
}

export const TokenNode = Node.create({
    name: "token",
    inline: true,
    group: "inline",
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
        return [
            {
                tag: "span[data-token]",
            },
        ];
    },

    renderHTML({ node }) {
        const { attrs, display } = resolveToken(
            node.attrs.handle,
            this.options.tokens,
        );

        return ["span", attrs, display];
    },

    addNodeView() {
        return ({ node, getPos, editor }) => {
            const { attrs, display, field, modifiers } = resolveToken(
                node.attrs.handle,
                this.options.tokens,
            );

            const dom = document.createElement("span");

            Object.entries(attrs)
                .filter(([key, value]) => value !== undefined)
                .forEach(([key, value]) => dom.setAttribute(key, value));

            if (modifiers?.length) {
                const fieldSpan = document.createElement("span");
                fieldSpan.textContent = field;
                dom.appendChild(fieldSpan);

                const modSpan = document.createElement("span");
                modSpan.textContent = modifiers.length;
                modSpan.className = expressionModifierClass;
                dom.appendChild(modSpan);
            } else {
                dom.textContent = display;
            }

            const onMousedown = (event) => selectNode(event, editor, getPos);

            const onDblclick = (event) => {
                event.preventDefault();
                if (!editor.isEditable) return;

                expandToken(editor, getPos(), node);
            };

            dom.addEventListener("mousedown", onMousedown);
            dom.addEventListener("dblclick", onDblclick);

            return {
                dom,
                destroy: () => {
                    dom.removeEventListener("mousedown", onMousedown);
                    dom.removeEventListener("dblclick", onDblclick);
                },
            };
        };
    },

    addKeyboardShortcuts() {
        return {
            Enter: ({ editor }) => {
                const { selection } = editor.state;

                if (
                    !(selection instanceof NodeSelection) ||
                    selection.node.type.name !== this.name
                )
                    return false;

                expandToken(editor, selection.from, selection.node);

                return true;
            },
        };
    },

    addCommands() {
        return {
            insertToken:
                (handle) =>
                ({ chain }) => {
                    return chain()
                        .insertContent([
                            { type: this.name, attrs: { handle } },
                            { type: "text", text: " " },
                        ])
                        .run();
                },
        };
    },

    addInputRules() {
        return [
            new InputRule({
                // Matches {{ handle }} followed by a trailing space to trigger token creation while typing.
                find: new RegExp(ANTLERS_PATTERN.source + "\\s$"),
                handler: ({ state, range, match }) => {
                    const handle = normalizeHandle(match[1]);
                    if (!handle) return;

                    const token = this.type.create({ handle });
                    const space = state.schema.text(" ");

                    state.tr.replaceWith(range.from, range.to, [token, space]);
                },
            }),
        ];
    },

    addPasteRules() {
        return [
            new PasteRule({
                // Matches all {{ handle }} occurrences in pasted text to convert them into tokens.
                find: new RegExp(ANTLERS_PATTERN.source, "g"),
                handler: ({ state, range, match }) => {
                    const handle = normalizeHandle(match[1]);
                    if (!handle) return;

                    const token = this.type.create({ handle });

                    state.tr.replaceWith(range.from, range.to, token);
                },
            }),
        ];
    },
});
