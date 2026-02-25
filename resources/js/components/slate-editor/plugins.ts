import { Editor, Element as SlateElement, Transforms } from "slate";
import type { BlockType } from "./types";
import { INLINE_TYPES, VOID_TYPES } from "./types";

function withVoidElements(editor: Editor): Editor {
    const { isVoid } = editor;

    editor.isVoid = (element: SlateElement) => {
        return VOID_TYPES.includes(element.type as BlockType)
            ? true
            : isVoid(element);
    };

    return editor;
}

function insertImage(editor: Editor, src: string, alt?: string): void {
    Transforms.insertNodes(editor, {
        type: "image",
        src,
        alt,
        children: [{ text: "" }],
    });
    Transforms.insertNodes(editor, {
        type: "paragraph",
        children: [{ text: "" }],
    });
}

function insertVideo(editor: Editor, src: string): void {
    Transforms.insertNodes(editor, {
        type: "video",
        src,
        children: [{ text: "" }],
    });
    Transforms.insertNodes(editor, {
        type: "paragraph",
        children: [{ text: "" }],
    });
}

function insertDocument(
    editor: Editor,
    src: string,
    name?: string,
): void {
    Transforms.insertNodes(editor, {
        type: "document-embed",
        src,
        name,
        children: [{ text: "" }],
    });
    Transforms.insertNodes(editor, {
        type: "paragraph",
        children: [{ text: "" }],
    });
}

function withMentions(editor: Editor): Editor {
    const { isInline, isVoid } = editor;

    editor.isInline = (element: SlateElement) => {
        return INLINE_TYPES.includes(element.type as (typeof INLINE_TYPES)[number])
            ? true
            : isInline(element);
    };

    editor.isVoid = (element: SlateElement) => {
        return element.type === "mention" ? true : isVoid(element);
    };

    return editor;
}

function insertMention(
    editor: Editor,
    userId: number,
    username: string,
): void {
    Transforms.insertNodes(editor, {
        type: "mention",
        userId,
        username,
        children: [{ text: "" }],
    });
    Transforms.move(editor);
}

export {
    insertDocument,
    insertImage,
    insertMention,
    insertVideo,
    withMentions,
    withVoidElements,
};
