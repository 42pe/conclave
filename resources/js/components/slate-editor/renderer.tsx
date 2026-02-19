import { FileDown } from 'lucide-react';
import type { Descendant } from 'slate';
import type { CustomElement, CustomText } from './types';

function RenderText({ leaf }: { leaf: CustomText }) {
    let el: React.ReactNode = leaf.text;

    if (!leaf.text) {
        return null;
    }

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

    return <>{el}</>;
}

function RenderNode({ node }: { node: Descendant }) {
    // Text node
    if ('text' in node) {
        return <RenderText leaf={node as CustomText} />;
    }

    const element = node as CustomElement;
    const children = element.children.map((child, i) => (
        <RenderNode key={i} node={child as Descendant} />
    ));

    switch (element.type) {
        case 'heading-one':
            return <h1 className="mb-3 text-2xl font-bold">{children}</h1>;
        case 'heading-two':
            return <h2 className="mb-2 text-xl font-semibold">{children}</h2>;
        case 'heading-three':
            return <h3 className="mb-2 text-lg font-semibold">{children}</h3>;
        case 'blockquote':
            return (
                <blockquote className="border-l-4 border-muted-foreground/30 pl-4 italic text-muted-foreground">
                    {children}
                </blockquote>
            );
        case 'bulleted-list':
            return <ul className="ml-6 list-disc space-y-1">{children}</ul>;
        case 'numbered-list':
            return <ol className="ml-6 list-decimal space-y-1">{children}</ol>;
        case 'list-item':
            return <li>{children}</li>;
        case 'link':
            return (
                <a
                    href={element.url}
                    className="text-primary underline"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    {children}
                </a>
            );
        case 'image':
            return (
                <img
                    src={element.url}
                    alt=""
                    className="my-2 max-h-96 rounded-md border object-contain"
                />
            );
        case 'video':
            return (
                <video
                    src={element.url}
                    controls
                    className="my-2 max-h-96 rounded-md border"
                />
            );
        case 'document-embed':
            return (
                <a
                    href={element.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="my-2 flex items-center gap-3 rounded-md border bg-muted/50 p-3 hover:bg-muted"
                >
                    <FileDown className="size-5 shrink-0 text-muted-foreground" />
                    <span className="truncate text-sm font-medium">
                        {element.originalName}
                    </span>
                </a>
            );
        default:
            return <p className="mb-1">{children}</p>;
    }
}

interface SlateRendererProps {
    value: Descendant[];
}

export default function SlateRenderer({ value }: SlateRendererProps) {
    return (
        <div className="prose dark:prose-invert max-w-none">
            {value.map((node, i) => (
                <RenderNode key={i} node={node} />
            ))}
        </div>
    );
}
