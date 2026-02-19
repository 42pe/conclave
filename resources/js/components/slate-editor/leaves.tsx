import type { RenderLeafProps } from 'slate-react';

export function renderLeaf({ attributes, children, leaf }: RenderLeafProps) {
    let el = children;

    if (leaf.bold) {
        el = <strong>{el}</strong>;
    }

    if (leaf.italic) {
        el = <em>{el}</em>;
    }

    if (leaf.underline) {
        el = <u>{el}</u>;
    }

    if (leaf.code) {
        el = (
            <code className="rounded bg-muted px-1 py-0.5 font-mono text-sm">
                {el}
            </code>
        );
    }

    return <span {...attributes}>{el}</span>;
}
