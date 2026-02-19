import type { BaseEditor } from 'slate';
import type { HistoryEditor } from 'slate-history';
import type { ReactEditor } from 'slate-react';

export type ParagraphElement = {
    type: 'paragraph';
    children: CustomDescendant[];
};

export type HeadingElement = {
    type: 'heading-one' | 'heading-two' | 'heading-three';
    children: CustomDescendant[];
};

export type BlockquoteElement = {
    type: 'blockquote';
    children: CustomDescendant[];
};

export type BulletedListElement = {
    type: 'bulleted-list';
    children: ListItemElement[];
};

export type NumberedListElement = {
    type: 'numbered-list';
    children: ListItemElement[];
};

export type ListItemElement = {
    type: 'list-item';
    children: CustomDescendant[];
};

export type LinkElement = {
    type: 'link';
    url: string;
    children: CustomText[];
};

export type ImageElement = {
    type: 'image';
    url: string;
    children: [CustomText];
};

export type VideoElement = {
    type: 'video';
    url: string;
    children: [CustomText];
};

export type DocumentEmbedElement = {
    type: 'document-embed';
    url: string;
    originalName: string;
    children: [CustomText];
};

export type CustomElement =
    | ParagraphElement
    | HeadingElement
    | BlockquoteElement
    | BulletedListElement
    | NumberedListElement
    | ListItemElement
    | LinkElement
    | ImageElement
    | VideoElement
    | DocumentEmbedElement;

export type CustomText = {
    text: string;
    bold?: boolean;
    italic?: boolean;
    underline?: boolean;
    code?: boolean;
};

export type CustomDescendant = CustomElement | CustomText;

export type CustomEditor = BaseEditor & ReactEditor & HistoryEditor;

export type BlockType = CustomElement['type'];
export type MarkType = keyof Omit<CustomText, 'text'>;

export const VOID_TYPES: BlockType[] = ['image', 'video', 'document-embed'];
export const INLINE_TYPES: BlockType[] = ['link'];
export const LIST_TYPES: BlockType[] = ['bulleted-list', 'numbered-list'];

export const EMPTY_DOCUMENT: ParagraphElement[] = [
    { type: 'paragraph', children: [{ text: '' }] },
];

/**
 * Normalize a Slate document loaded from the server.
 * Laravel's ConvertEmptyStringsToNull middleware converts "" to null,
 * but Slate requires text nodes to have string values.
 */
export function normalizeSlateValue(nodes: CustomDescendant[]): CustomDescendant[] {
    return nodes.map((node) => {
        if ('text' in node) {
            return { ...node, text: node.text ?? '' };
        }
        return { ...node, children: normalizeSlateValue(node.children as CustomDescendant[]) } as CustomElement;
    });
}
