import isHotkey from 'is-hotkey';
import { useCallback, useMemo } from 'react';
import { type Descendant, Editor, createEditor } from 'slate';
import { Editable, Slate, withReact } from 'slate-react';
import { withHistory } from 'slate-history';
import { renderElement } from './elements';
import { renderLeaf } from './leaves';
import { HOTKEYS, withInlines, withVoids } from './plugins';
import Toolbar from './toolbar';
import type { MarkType } from './types';

interface SlateEditorProps {
    value: Descendant[];
    onChange: (value: Descendant[]) => void;
    placeholder?: string;
}

export default function SlateEditor({
    value,
    onChange,
    placeholder = 'Start writing...',
}: SlateEditorProps) {
    const editor = useMemo(
        () => withVoids(withInlines(withHistory(withReact(createEditor())))),
        [],
    );

    const handleKeyDown = useCallback(
        (event: React.KeyboardEvent<HTMLDivElement>) => {
            for (const hotkey in HOTKEYS) {
                if (isHotkey(hotkey, event)) {
                    event.preventDefault();
                    const mark = HOTKEYS[hotkey] as MarkType;
                    const isActive = Editor.marks(editor)?.[mark] === true;
                    if (isActive) {
                        Editor.removeMark(editor, mark);
                    } else {
                        Editor.addMark(editor, mark, true);
                    }
                }
            }
        },
        [editor],
    );

    return (
        <Slate editor={editor} initialValue={value} onChange={onChange}>
            <div className="rounded-md border">
                <Toolbar />
                <Editable
                    className="min-h-[200px] px-4 py-3 text-sm focus:outline-none"
                    renderElement={renderElement}
                    renderLeaf={renderLeaf}
                    placeholder={placeholder}
                    onKeyDown={handleKeyDown}
                    spellCheck
                />
            </div>
        </Slate>
    );
}
