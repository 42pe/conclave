import { useMemo } from "react";
import type { Descendant } from "slate";
import { createEditor } from "slate";
import { Editable, Slate, withReact } from "slate-react";
import { Element } from "./elements";
import { Leaf } from "./leaves";
import { withVoidElements } from "./plugins";
import { DEFAULT_INITIAL_VALUE } from "./types";

interface SlateRendererProps {
    value: Descendant[] | null | undefined;
}

export function SlateRenderer({ value }: SlateRendererProps) {
    const editor = useMemo(
        () => withVoidElements(withReact(createEditor())),
        [],
    );

    return (
        <Slate
            editor={editor}
            initialValue={value ?? DEFAULT_INITIAL_VALUE}
        >
            <Editable
                readOnly
                renderElement={(props) => <Element {...props} />}
                renderLeaf={(props) => <Leaf {...props} />}
                className="text-sm"
            />
        </Slate>
    );
}
