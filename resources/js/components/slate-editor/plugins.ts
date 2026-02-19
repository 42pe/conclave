import { Editor, Element as SlateElement, Transforms } from "slate";
import type { BlockType } from "./types";
import { VOID_TYPES } from "./types";

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

export { insertDocument, insertImage, insertVideo, withVoidElements };
