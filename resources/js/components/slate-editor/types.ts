import type { Descendant } from "slate";

export type BlockType =
    | "paragraph"
    | "heading-one"
    | "heading-two"
    | "heading-three"
    | "bulleted-list"
    | "numbered-list"
    | "list-item"
    | "blockquote"
    | "image"
    | "video"
    | "document-embed";

export type MarkType = "bold" | "italic" | "underline" | "code";

export const VOID_TYPES: BlockType[] = ["image", "video", "document-embed"];

export const INLINE_TYPES = ["mention"] as const;

export type InlineType = (typeof INLINE_TYPES)[number];

export const LIST_TYPES: BlockType[] = ["bulleted-list", "numbered-list"];

export const DEFAULT_INITIAL_VALUE: Descendant[] = [
    {
        type: "paragraph",
        children: [{ text: "" }],
    },
];
