import { BaseEditor, Descendant } from "slate";
import { HistoryEditor } from "slate-history";
import { ReactEditor } from "slate-react";

type CustomText = {
    text: string;
    bold?: boolean;
    italic?: boolean;
    underline?: boolean;
    code?: boolean;
};

type ParagraphElement = {
    type: "paragraph";
    children: Descendant[];
};

type HeadingOneElement = {
    type: "heading-one";
    children: Descendant[];
};

type HeadingTwoElement = {
    type: "heading-two";
    children: Descendant[];
};

type HeadingThreeElement = {
    type: "heading-three";
    children: Descendant[];
};

type BulletedListElement = {
    type: "bulleted-list";
    children: Descendant[];
};

type NumberedListElement = {
    type: "numbered-list";
    children: Descendant[];
};

type ListItemElement = {
    type: "list-item";
    children: Descendant[];
};

type BlockquoteElement = {
    type: "blockquote";
    children: Descendant[];
};

type ImageElement = {
    type: "image";
    src: string;
    alt?: string;
    children: [CustomText];
};

type VideoElement = {
    type: "video";
    src: string;
    children: [CustomText];
};

type DocumentEmbedElement = {
    type: "document-embed";
    src: string;
    name?: string;
    children: [CustomText];
};

type CustomElement =
    | ParagraphElement
    | HeadingOneElement
    | HeadingTwoElement
    | HeadingThreeElement
    | BulletedListElement
    | NumberedListElement
    | ListItemElement
    | BlockquoteElement
    | ImageElement
    | VideoElement
    | DocumentEmbedElement;

declare module "slate" {
    interface CustomTypes {
        Editor: BaseEditor & ReactEditor & HistoryEditor;
        Element: CustomElement;
        Text: CustomText;
    }
}
