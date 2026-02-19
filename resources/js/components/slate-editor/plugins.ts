import type { Editor } from 'slate';
import { INLINE_TYPES, VOID_TYPES, type BlockType } from './types';

export function withVoids(editor: Editor): Editor {
    const { isVoid } = editor;

    editor.isVoid = (element) => {
        return VOID_TYPES.includes(element.type as BlockType)
            ? true
            : isVoid(element);
    };

    return editor;
}

export function withInlines(editor: Editor): Editor {
    const { isInline } = editor;

    editor.isInline = (element) => {
        return INLINE_TYPES.includes(element.type as BlockType)
            ? true
            : isInline(element);
    };

    return editor;
}

export const HOTKEYS: Record<string, string> = {
    'mod+b': 'bold',
    'mod+i': 'italic',
    'mod+u': 'underline',
};
