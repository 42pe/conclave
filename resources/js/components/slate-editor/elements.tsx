import { FileDown } from 'lucide-react';
import type { RenderElementProps } from 'slate-react';

export function renderElement({
    attributes,
    children,
    element,
}: RenderElementProps) {
    switch (element.type) {
        case 'heading-one':
            return (
                <h1
                    {...attributes}
                    className="mb-3 text-2xl font-bold"
                >
                    {children}
                </h1>
            );
        case 'heading-two':
            return (
                <h2
                    {...attributes}
                    className="mb-2 text-xl font-semibold"
                >
                    {children}
                </h2>
            );
        case 'heading-three':
            return (
                <h3
                    {...attributes}
                    className="mb-2 text-lg font-semibold"
                >
                    {children}
                </h3>
            );
        case 'blockquote':
            return (
                <blockquote
                    {...attributes}
                    className="border-l-4 border-muted-foreground/30 pl-4 italic text-muted-foreground"
                >
                    {children}
                </blockquote>
            );
        case 'bulleted-list':
            return (
                <ul
                    {...attributes}
                    className="ml-6 list-disc space-y-1"
                >
                    {children}
                </ul>
            );
        case 'numbered-list':
            return (
                <ol
                    {...attributes}
                    className="ml-6 list-decimal space-y-1"
                >
                    {children}
                </ol>
            );
        case 'list-item':
            return <li {...attributes}>{children}</li>;
        case 'link':
            return (
                <a
                    {...attributes}
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
                <div {...attributes} contentEditable={false}>
                    <img
                        src={element.url}
                        alt=""
                        className="my-2 max-h-96 rounded-md border object-contain"
                    />
                    {children}
                </div>
            );
        case 'video':
            return (
                <div {...attributes} contentEditable={false}>
                    <video
                        src={element.url}
                        controls
                        className="my-2 max-h-96 rounded-md border"
                    />
                    {children}
                </div>
            );
        case 'document-embed':
            return (
                <div {...attributes} contentEditable={false}>
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
                    {children}
                </div>
            );
        default:
            return (
                <p {...attributes} className="mb-1">
                    {children}
                </p>
            );
    }
}
