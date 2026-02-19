import isHotkey from "is-hotkey";
import { useCallback, useMemo } from "react";
import type { Descendant } from "slate";
import { createEditor } from "slate";
import { withHistory } from "slate-history";
import { Editable, Slate, withReact } from "slate-react";
import { Element } from "./elements";
import { Leaf } from "./leaves";
import { withVoidElements } from "./plugins";
import { Toolbar, toggleMark } from "./toolbar";
import type { MarkType } from "./types";
import { DEFAULT_INITIAL_VALUE } from "./types";

const HOTKEYS: Record<string, MarkType> = {
    "mod+b": "bold",
    "mod+i": "italic",
    "mod+u": "underline",
};

interface SlateEditorProps {
    initialValue?: Descendant[];
    onChange?: (value: Descendant[]) => void;
    placeholder?: string;
    readOnly?: boolean;
    onUploadMedia?: (file: File) => Promise<{ url: string; original_name: string; mime_type: string } | null>;
}

export function SlateEditor({
    initialValue,
    onChange,
    placeholder = "Start writing...",
    readOnly = false,
    onUploadMedia,
}: SlateEditorProps) {
    const editor = useMemo(
        () => withVoidElements(withHistory(withReact(createEditor()))),
        [],
    );

    const handleKeyDown = useCallback(
        (event: React.KeyboardEvent<HTMLDivElement>) => {
            for (const hotkey in HOTKEYS) {
                if (isHotkey(hotkey, event.nativeEvent)) {
                    event.preventDefault();
                    toggleMark(editor, HOTKEYS[hotkey]);
                }
            }
        },
        [editor],
    );

    const renderElement = useCallback(
        (props: Parameters<typeof Element>[0]) => <Element {...props} />,
        [],
    );

    const renderLeaf = useCallback(
        (props: Parameters<typeof Leaf>[0]) => <Leaf {...props} />,
        [],
    );

    return (
        <div className="rounded-md border bg-background">
            <Slate
                editor={editor}
                initialValue={initialValue ?? DEFAULT_INITIAL_VALUE}
                onChange={(value) => {
                    const isAstChange = editor.operations.some(
                        (op) => op.type !== "set_selection",
                    );
                    if (isAstChange && onChange) {
                        onChange(value);
                    }
                }}
            >
                {!readOnly && <Toolbar onUploadMedia={onUploadMedia} />}
                <Editable
                    readOnly={readOnly}
                    placeholder={placeholder}
                    className="min-h-[150px] px-3 py-2 text-sm focus:outline-none"
                    renderElement={renderElement}
                    renderLeaf={renderLeaf}
                    onKeyDown={readOnly ? undefined : handleKeyDown}
                    spellCheck
                />
            </Slate>
        </div>
    );
}
