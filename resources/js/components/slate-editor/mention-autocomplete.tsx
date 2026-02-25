import { useCallback, useEffect, useRef, useState } from "react";
import { Editor, Range, Transforms } from "slate";
import { ReactEditor, useSlate } from "slate-react";
import { insertMention } from "./plugins";

type UserResult = {
    id: number;
    username: string;
    display_name: string;
    avatar_url: string | null;
};

export function MentionAutocomplete() {
    const editor = useSlate();
    const [target, setTarget] = useState<Range | null>(null);
    const [search, setSearch] = useState("");
    const [results, setResults] = useState<UserResult[]>([]);
    const [selectedIndex, setSelectedIndex] = useState(0);
    const dropdownRef = useRef<HTMLDivElement>(null);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    const closeMention = useCallback(() => {
        setTarget(null);
        setSearch("");
        setResults([]);
        setSelectedIndex(0);
    }, []);

    useEffect(() => {
        const { selection } = editor;

        if (!selection || !Range.isCollapsed(selection)) {
            closeMention();
            return;
        }

        const [start] = Range.edges(selection);
        const wordBefore = Editor.before(editor, start, { unit: "word" });
        const before = wordBefore && Editor.before(editor, wordBefore);
        const beforeRange = before && Editor.range(editor, before, start);
        const beforeText = beforeRange && Editor.string(editor, beforeRange);
        const beforeMatch = beforeText && beforeText.match(/^@(\w*)$/);

        const after = Editor.after(editor, start);
        const afterRange = Editor.range(editor, start, after);
        const afterText = Editor.string(editor, afterRange);
        const afterMatch = afterText.match(/^(\s|$)/);

        if (beforeMatch && afterMatch) {
            setTarget(beforeRange);
            setSearch(beforeMatch[1]);
            setSelectedIndex(0);
            return;
        }

        closeMention();
    });

    useEffect(() => {
        if (debounceRef.current) {
            clearTimeout(debounceRef.current);
        }

        if (!target || search.length === 0) {
            setResults([]);
            return;
        }

        debounceRef.current = setTimeout(async () => {
            try {
                const response = await fetch(
                    `/users/search?q=${encodeURIComponent(search)}`,
                );
                if (response.ok) {
                    const data: UserResult[] = await response.json();
                    setResults(data);
                }
            } catch {
                setResults([]);
            }
        }, 300);

        return () => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }
        };
    }, [search, target]);

    const selectMention = useCallback(
        (user: UserResult) => {
            if (target) {
                Transforms.select(editor, target);
                insertMention(editor, user.id, user.username);
                closeMention();
                ReactEditor.focus(editor);
            }
        },
        [editor, target, closeMention],
    );

    useEffect(() => {
        if (!target || results.length === 0) {
            return;
        }

        const handleKeyDown = (event: KeyboardEvent) => {
            switch (event.key) {
                case "ArrowDown":
                    event.preventDefault();
                    setSelectedIndex((i) =>
                        i >= results.length - 1 ? 0 : i + 1,
                    );
                    break;
                case "ArrowUp":
                    event.preventDefault();
                    setSelectedIndex((i) =>
                        i <= 0 ? results.length - 1 : i - 1,
                    );
                    break;
                case "Tab":
                case "Enter":
                    event.preventDefault();
                    selectMention(results[selectedIndex]);
                    break;
                case "Escape":
                    event.preventDefault();
                    closeMention();
                    break;
            }
        };

        document.addEventListener("keydown", handleKeyDown, true);
        return () => {
            document.removeEventListener("keydown", handleKeyDown, true);
        };
    }, [target, results, selectedIndex, selectMention, closeMention]);

    useEffect(() => {
        if (!target) {
            return;
        }

        const handleClickOutside = (event: MouseEvent) => {
            if (
                dropdownRef.current &&
                !dropdownRef.current.contains(event.target as Node)
            ) {
                closeMention();
            }
        };

        document.addEventListener("mousedown", handleClickOutside);
        return () => {
            document.removeEventListener("mousedown", handleClickOutside);
        };
    }, [target, closeMention]);

    if (!target || results.length === 0) {
        return null;
    }

    let top = 0;
    let left = 0;

    try {
        const domRange = ReactEditor.toDOMRange(editor, target);
        const rect = domRange.getBoundingClientRect();
        const editorEl = ReactEditor.toDOMNode(editor, editor);
        const editorRect = editorEl.getBoundingClientRect();

        top = rect.bottom - editorRect.top + 4;
        left = rect.left - editorRect.left;
    } catch {
        return null;
    }

    return (
        <div
            ref={dropdownRef}
            className="absolute z-50 max-h-48 w-64 overflow-y-auto rounded-md border bg-popover shadow-md"
            style={{ top, left }}
        >
            {results.map((user, index) => (
                <button
                    key={user.id}
                    type="button"
                    className={`flex w-full items-center gap-2 px-3 py-2 text-left text-sm ${
                        index === selectedIndex
                            ? "bg-accent text-accent-foreground"
                            : "hover:bg-accent/50"
                    }`}
                    onMouseDown={(e) => {
                        e.preventDefault();
                        selectMention(user);
                    }}
                    onMouseEnter={() => setSelectedIndex(index)}
                >
                    {user.avatar_url ? (
                        <img
                            src={user.avatar_url}
                            alt=""
                            className="size-6 rounded-full object-cover"
                        />
                    ) : (
                        <div className="flex size-6 items-center justify-center rounded-full bg-muted text-xs font-medium">
                            {user.display_name.charAt(0).toUpperCase()}
                        </div>
                    )}
                    <div className="min-w-0 flex-1">
                        <div className="truncate font-medium">
                            {user.display_name}
                        </div>
                        <div className="truncate text-xs text-muted-foreground">
                            @{user.username}
                        </div>
                    </div>
                </button>
            ))}
        </div>
    );
}
