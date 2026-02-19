import { Toggle } from "@/components/ui/toggle";
import {
    Bold,
    Code,
    Heading1,
    Heading2,
    Heading3,
    ImageIcon,
    Italic,
    List,
    ListOrdered,
    Quote,
    Underline,
} from "lucide-react";
import { useCallback, useRef } from "react";
import { Editor, Element as SlateElement, Transforms } from "slate";
import { useSlate } from "slate-react";
import { insertDocument, insertImage, insertVideo } from "./plugins";
import type { BlockType, MarkType } from "./types";
import { LIST_TYPES } from "./types";

function isMarkActive(editor: Editor, format: MarkType): boolean {
    const marks = Editor.marks(editor);
    return marks ? marks[format] === true : false;
}

function toggleMark(editor: Editor, format: MarkType): void {
    if (isMarkActive(editor, format)) {
        Editor.removeMark(editor, format);
    } else {
        Editor.addMark(editor, format, true);
    }
}

function isBlockActive(editor: Editor, format: BlockType): boolean {
    const { selection } = editor;
    if (!selection) {
        return false;
    }

    const [match] = Editor.nodes(editor, {
        at: Editor.unhangRange(editor, selection),
        match: (n) =>
            !Editor.isEditor(n) &&
            SlateElement.isElement(n) &&
            n.type === format,
    });

    return !!match;
}

function toggleBlock(editor: Editor, format: BlockType): void {
    const isActive = isBlockActive(editor, format);
    const isList = LIST_TYPES.includes(format);

    Transforms.unwrapNodes(editor, {
        match: (n) =>
            !Editor.isEditor(n) &&
            SlateElement.isElement(n) &&
            LIST_TYPES.includes(n.type as BlockType),
        split: true,
    });

    Transforms.setNodes(editor, {
        type: isActive ? "paragraph" : isList ? "list-item" : format,
    });

    if (!isActive && isList) {
        Transforms.wrapNodes(editor, {
            type: format,
            children: [],
        } as SlateElement);
    }
}

interface ToolbarProps {
    onUploadMedia?: (file: File) => Promise<{ url: string; original_name: string; mime_type: string } | null>;
}

export function Toolbar({ onUploadMedia }: ToolbarProps) {
    const editor = useSlate();
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = useCallback(
        async (event: React.ChangeEvent<HTMLInputElement>) => {
            const file = event.target.files?.[0];
            if (!file || !onUploadMedia) {
                return;
            }

            const result = await onUploadMedia(file);
            if (!result) {
                return;
            }

            if (result.mime_type.startsWith("image/")) {
                insertImage(editor, result.url, result.original_name);
            } else if (result.mime_type.startsWith("video/")) {
                insertVideo(editor, result.url);
            } else {
                insertDocument(editor, result.url, result.original_name);
            }

            if (fileInputRef.current) {
                fileInputRef.current.value = "";
            }
        },
        [editor, onUploadMedia],
    );

    return (
        <div className="flex flex-wrap items-center gap-0.5 border-b px-1 py-1">
            <MarkButton format="bold" icon={<Bold className="size-4" />} />
            <MarkButton
                format="italic"
                icon={<Italic className="size-4" />}
            />
            <MarkButton
                format="underline"
                icon={<Underline className="size-4" />}
            />
            <MarkButton format="code" icon={<Code className="size-4" />} />

            <div className="mx-1 h-6 w-px bg-border" />

            <BlockButton
                format="heading-one"
                icon={<Heading1 className="size-4" />}
            />
            <BlockButton
                format="heading-two"
                icon={<Heading2 className="size-4" />}
            />
            <BlockButton
                format="heading-three"
                icon={<Heading3 className="size-4" />}
            />

            <div className="mx-1 h-6 w-px bg-border" />

            <BlockButton
                format="bulleted-list"
                icon={<List className="size-4" />}
            />
            <BlockButton
                format="numbered-list"
                icon={<ListOrdered className="size-4" />}
            />
            <BlockButton
                format="blockquote"
                icon={<Quote className="size-4" />}
            />

            {onUploadMedia && (
                <>
                    <div className="mx-1 h-6 w-px bg-border" />
                    <Toggle
                        size="sm"
                        pressed={false}
                        onPressedChange={() => fileInputRef.current?.click()}
                        aria-label="Insert media"
                    >
                        <ImageIcon className="size-4" />
                    </Toggle>
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,application/pdf"
                        className="hidden"
                        onChange={handleFileChange}
                    />
                </>
            )}
        </div>
    );
}

function MarkButton({ format, icon }: { format: MarkType; icon: React.ReactNode }) {
    const editor = useSlate();

    return (
        <Toggle
            size="sm"
            pressed={isMarkActive(editor, format)}
            onPressedChange={() => {
                toggleMark(editor, format);
            }}
            aria-label={format}
        >
            {icon}
        </Toggle>
    );
}

function BlockButton({ format, icon }: { format: BlockType; icon: React.ReactNode }) {
    const editor = useSlate();

    return (
        <Toggle
            size="sm"
            pressed={isBlockActive(editor, format)}
            onPressedChange={() => {
                toggleBlock(editor, format);
            }}
            aria-label={format}
        >
            {icon}
        </Toggle>
    );
}

export { isBlockActive, isMarkActive, toggleBlock, toggleMark };
