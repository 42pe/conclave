import {
    Bold,
    Code,
    Heading1,
    Heading2,
    Heading3,
    Image,
    Italic,
    Link,
    List,
    ListOrdered,
    Quote,
    Underline,
} from 'lucide-react';
import { useRef } from 'react';
import { Editor, Element as SlateElement, Transforms } from 'slate';
import { useSlate } from 'slate-react';
import { Separator } from '@/components/ui/separator';
import { Toggle } from '@/components/ui/toggle';
import { LIST_TYPES, type BlockType, type MarkType } from './types';

function isMarkActive(editor: Editor, mark: MarkType): boolean {
    const marks = Editor.marks(editor);
    return marks ? marks[mark] === true : false;
}

function toggleMark(editor: Editor, mark: MarkType): void {
    if (isMarkActive(editor, mark)) {
        Editor.removeMark(editor, mark);
    } else {
        Editor.addMark(editor, mark, true);
    }
}

function isBlockActive(editor: Editor, type: BlockType): boolean {
    const [match] = Editor.nodes(editor, {
        match: (n) =>
            !Editor.isEditor(n) &&
            SlateElement.isElement(n) &&
            n.type === type,
    });
    return !!match;
}

function toggleBlock(editor: Editor, type: BlockType): void {
    const isActive = isBlockActive(editor, type);
    const isList = LIST_TYPES.includes(type);

    Transforms.unwrapNodes(editor, {
        match: (n) =>
            !Editor.isEditor(n) &&
            SlateElement.isElement(n) &&
            LIST_TYPES.includes(n.type as BlockType),
        split: true,
    });

    Transforms.setNodes(editor, {
        type: isActive ? 'paragraph' : isList ? 'list-item' : type,
    });

    if (!isActive && isList) {
        Transforms.wrapNodes(editor, {
            type,
            children: [],
        } as SlateElement);
    }
}

function insertLink(editor: Editor): void {
    const url = window.prompt('Enter URL:');
    if (!url) {
        return;
    }

    const { selection } = editor;
    if (!selection) {
        return;
    }

    const isCollapsed = selection.anchor.offset === selection.focus.offset;

    if (isCollapsed) {
        Transforms.insertNodes(editor, {
            type: 'link',
            url,
            children: [{ text: url }],
        });
    } else {
        Transforms.wrapNodes(
            editor,
            { type: 'link', url, children: [] },
            { split: true },
        );
    }
}

function insertMedia(
    editor: Editor,
    url: string,
    mimeType: string,
    originalName: string,
): void {
    if (mimeType.startsWith('image/')) {
        Transforms.insertNodes(editor, {
            type: 'image',
            url,
            children: [{ text: '' }],
        });
    } else if (mimeType.startsWith('video/')) {
        Transforms.insertNodes(editor, {
            type: 'video',
            url,
            children: [{ text: '' }],
        });
    } else {
        Transforms.insertNodes(editor, {
            type: 'document-embed',
            url,
            originalName,
            children: [{ text: '' }],
        });
    }

    // Insert a new paragraph after the void element
    Transforms.insertNodes(editor, {
        type: 'paragraph',
        children: [{ text: '' }],
    });
}

export default function Toolbar() {
    const editor = useSlate();
    const fileInputRef = useRef<HTMLInputElement>(null);

    async function handleFileUpload(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (!file) {
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        try {
            const csrfToken = document
                .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                ?.getAttribute('content');

            const response = await fetch('/media/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    Accept: 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                throw new Error('Upload failed');
            }

            const data = await response.json();
            insertMedia(editor, data.url, data.mime_type, data.original_name);
        } catch {
            // Silently fail — user can retry
        }

        // Reset the input
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    return (
        <div className="flex flex-wrap items-center gap-0.5 border-b p-1">
            <Toggle
                size="sm"
                pressed={isMarkActive(editor, 'bold')}
                onPressedChange={() => toggleMark(editor, 'bold')}
                aria-label="Bold"
            >
                <Bold className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isMarkActive(editor, 'italic')}
                onPressedChange={() => toggleMark(editor, 'italic')}
                aria-label="Italic"
            >
                <Italic className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isMarkActive(editor, 'underline')}
                onPressedChange={() => toggleMark(editor, 'underline')}
                aria-label="Underline"
            >
                <Underline className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isMarkActive(editor, 'code')}
                onPressedChange={() => toggleMark(editor, 'code')}
                aria-label="Code"
            >
                <Code className="size-4" />
            </Toggle>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'heading-one')}
                onPressedChange={() => toggleBlock(editor, 'heading-one')}
                aria-label="Heading 1"
            >
                <Heading1 className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'heading-two')}
                onPressedChange={() => toggleBlock(editor, 'heading-two')}
                aria-label="Heading 2"
            >
                <Heading2 className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'heading-three')}
                onPressedChange={() => toggleBlock(editor, 'heading-three')}
                aria-label="Heading 3"
            >
                <Heading3 className="size-4" />
            </Toggle>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'blockquote')}
                onPressedChange={() => toggleBlock(editor, 'blockquote')}
                aria-label="Blockquote"
            >
                <Quote className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'bulleted-list')}
                onPressedChange={() => toggleBlock(editor, 'bulleted-list')}
                aria-label="Bulleted List"
            >
                <List className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'numbered-list')}
                onPressedChange={() => toggleBlock(editor, 'numbered-list')}
                aria-label="Numbered List"
            >
                <ListOrdered className="size-4" />
            </Toggle>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <Toggle
                size="sm"
                pressed={isBlockActive(editor, 'link')}
                onPressedChange={() => insertLink(editor)}
                aria-label="Link"
            >
                <Link className="size-4" />
            </Toggle>
            <Toggle
                size="sm"
                pressed={false}
                onPressedChange={() => fileInputRef.current?.click()}
                aria-label="Upload media"
            >
                <Image className="size-4" />
            </Toggle>

            <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,application/pdf"
                className="hidden"
                onChange={handleFileUpload}
            />
        </div>
    );
}
