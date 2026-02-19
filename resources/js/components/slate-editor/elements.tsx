import type { RenderElementProps } from "slate-react";

export function Element({ attributes, children, element }: RenderElementProps) {
    switch (element.type) {
        case "heading-one":
            return (
                <h1 className="mb-3 text-2xl font-bold" {...attributes}>
                    {children}
                </h1>
            );
        case "heading-two":
            return (
                <h2 className="mb-2 text-xl font-semibold" {...attributes}>
                    {children}
                </h2>
            );
        case "heading-three":
            return (
                <h3 className="mb-2 text-lg font-semibold" {...attributes}>
                    {children}
                </h3>
            );
        case "bulleted-list":
            return (
                <ul className="mb-2 ml-6 list-disc" {...attributes}>
                    {children}
                </ul>
            );
        case "numbered-list":
            return (
                <ol className="mb-2 ml-6 list-decimal" {...attributes}>
                    {children}
                </ol>
            );
        case "list-item":
            return <li {...attributes}>{children}</li>;
        case "blockquote":
            return (
                <blockquote
                    className="mb-2 border-l-4 border-muted-foreground/30 pl-4 italic text-muted-foreground"
                    {...attributes}
                >
                    {children}
                </blockquote>
            );
        case "image":
            return (
                <div {...attributes} contentEditable={false}>
                    <img
                        src={element.src}
                        alt={element.alt ?? ""}
                        className="my-2 max-w-full rounded"
                    />
                    {children}
                </div>
            );
        case "video":
            return (
                <div {...attributes} contentEditable={false}>
                    <video
                        src={element.src}
                        controls
                        className="my-2 max-w-full rounded"
                    />
                    {children}
                </div>
            );
        case "document-embed":
            return (
                <div {...attributes} contentEditable={false}>
                    <a
                        href={element.src}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="my-2 inline-flex items-center gap-2 rounded border bg-muted px-3 py-2 text-sm hover:bg-muted/80"
                    >
                        {element.name ?? "Document"}
                    </a>
                    {children}
                </div>
            );
        default:
            return (
                <p className="mb-1" {...attributes}>
                    {children}
                </p>
            );
    }
}
