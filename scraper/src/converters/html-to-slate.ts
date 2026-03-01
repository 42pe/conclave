import { load } from "cheerio";
import type { Cheerio } from "cheerio";
import type { AnyNode, Element } from "domhandler";
import type { SlateElementNode, SlateNode, SlateTextNode } from "../types.js";

/**
 * Convert HTML content to Slate.js JSON format.
 * Used as fallback when quillDelta is not available.
 */
export function htmlToSlate(html: string): SlateNode[] {
  if (!html || !html.trim()) {
    return [{ type: "paragraph", children: [{ text: "" }] }];
  }

  const $ = load(html, { xml: false });
  const blocks = processChildren($, $.root());

  // Wrap any loose inline nodes in paragraphs
  const wrapped = wrapLooseInlines(blocks);

  // Wrap consecutive list-items in list containers
  const withLists = wrapListItems(wrapped);

  return withLists.length > 0
    ? withLists
    : [{ type: "paragraph", children: [{ text: "" }] }];
}

function processChildren(
  $: ReturnType<typeof load>,
  parent: Cheerio<AnyNode>,
): SlateNode[] {
  const nodes: SlateNode[] = [];

  parent.contents().each((_i, el) => {
    if (el.type === "text") {
      const text = (el as unknown as { data: string }).data ?? "";
      if (text) {
        nodes.push({ text });
      }
      return;
    }

    if (el.type !== "tag") return;
    const tagName = (el as Element).tagName.toLowerCase();
    const $el = $(el);

    switch (tagName) {
      case "p":
        nodes.push({
          type: "paragraph",
          children: inlineChildren($, $el),
        });
        break;

      case "h1":
        nodes.push({
          type: "heading-one",
          children: inlineChildren($, $el),
        });
        break;

      case "h2":
        nodes.push({
          type: "heading-two",
          children: inlineChildren($, $el),
        });
        break;

      case "h3":
      case "h4":
      case "h5":
      case "h6":
        nodes.push({
          type: "heading-three",
          children: inlineChildren($, $el),
        });
        break;

      case "ul":
        nodes.push({
          type: "bulleted-list",
          children: listItemChildren($, $el),
        });
        break;

      case "ol":
        nodes.push({
          type: "numbered-list",
          children: listItemChildren($, $el),
        });
        break;

      case "li":
        nodes.push({
          type: "list-item",
          children: inlineChildren($, $el),
        });
        break;

      case "blockquote":
        nodes.push({
          type: "blockquote",
          children: blockquoteChildren($, $el),
        });
        break;

      case "img": {
        const src = $el.attr("src") ?? "";
        if (src) {
          nodes.push({
            type: "image",
            src,
            alt: $el.attr("alt") ?? "",
            children: [{ text: "" }],
          });
        }
        break;
      }

      case "br":
        nodes.push({ text: "\n" });
        break;

      case "div":
      case "section":
      case "article":
      case "main":
        // Block container — process children recursively
        nodes.push(...processChildren($, $el));
        break;

      case "pre": {
        // Code block — extract text with code marks
        const codeText = $el.text();
        nodes.push({
          type: "paragraph",
          children: [{ text: codeText, code: true }],
        });
        break;
      }

      // Inline elements at block level — wrap in paragraph later
      case "a":
      case "strong":
      case "b":
      case "em":
      case "i":
      case "u":
      case "code":
      case "span":
        nodes.push(...extractInline($, $el, {}));
        break;

      default:
        // Unknown element — try to extract text
        nodes.push(...processChildren($, $el));
        break;
    }
  });

  return nodes;
}

/**
 * Extract inline content from a block element.
 */
function inlineChildren(
  $: ReturnType<typeof load>,
  parent: Cheerio<AnyNode>,
): SlateNode[] {
  const children = extractInlineFromParent($, parent, {});
  return children.length > 0 ? children : [{ text: "" }];
}

function extractInlineFromParent(
  $: ReturnType<typeof load>,
  parent: Cheerio<AnyNode>,
  marks: Record<string, boolean>,
): SlateNode[] {
  const nodes: SlateNode[] = [];

  parent.contents().each((_i, el) => {
    if (el.type === "text") {
      const text = (el as unknown as { data: string }).data ?? "";
      if (text) {
        const node: SlateTextNode = { text };
        if (marks.bold) node.bold = true;
        if (marks.italic) node.italic = true;
        if (marks.underline) node.underline = true;
        if (marks.code) node.code = true;
        nodes.push(node);
      }
      return;
    }

    if (el.type !== "tag") return;
    nodes.push(...extractInline($, $(el), marks));
  });

  return nodes;
}

function extractInline(
  $: ReturnType<typeof load>,
  $el: Cheerio<AnyNode>,
  parentMarks: Record<string, boolean>,
): SlateNode[] {
  const el = $el[0] as Element;
  const tagName = el.tagName.toLowerCase();
  const marks = { ...parentMarks };

  switch (tagName) {
    case "strong":
    case "b":
      marks.bold = true;
      return extractInlineFromParent($, $el, marks);

    case "em":
    case "i":
      marks.italic = true;
      return extractInlineFromParent($, $el, marks);

    case "u":
      marks.underline = true;
      return extractInlineFromParent($, $el, marks);

    case "code":
      marks.code = true;
      return extractInlineFromParent($, $el, marks);

    case "a": {
      // Check for @mention link
      if (
        $el.hasClass("plugin-mentions-user") ||
        $el.hasClass("plugin-mentions-a")
      ) {
        const href = $el.attr("href") ?? "";
        const uidMatch = href.match(/\/uid\/(\d+)/);
        const username = $el.text().replace(/^@/, "").trim();
        return [
          {
            type: "mention",
            userId: uidMatch ? Number(uidMatch[1]) : 0,
            username,
            children: [{ text: "" }],
          } as SlateElementNode,
        ];
      }
      // Regular link — preserve as underlined text (Conclave doesn't have a link type)
      return extractInlineFromParent($, $el, marks);
    }

    case "img": {
      const src = $el.attr("src") ?? "";
      if (src) {
        return [
          {
            type: "image",
            src,
            alt: $el.attr("alt") ?? "",
            children: [{ text: "" }],
          } as SlateElementNode,
        ];
      }
      return [];
    }

    case "br":
      return [{ text: "\n" }];

    case "span":
      return extractInlineFromParent($, $el, marks);

    default:
      return extractInlineFromParent($, $el, marks);
  }
}

function listItemChildren(
  $: ReturnType<typeof load>,
  parent: Cheerio<AnyNode>,
): SlateElementNode[] {
  const items: SlateElementNode[] = [];
  parent.children("li").each((_i, el) => {
    items.push({
      type: "list-item",
      children: inlineChildren($, $(el)),
    });
  });
  return items.length > 0
    ? items
    : [{ type: "list-item", children: [{ text: "" }] }];
}

function blockquoteChildren(
  $: ReturnType<typeof load>,
  parent: Cheerio<AnyNode>,
): SlateNode[] {
  // Blockquotes might contain paragraphs or just inline text
  const blocks = processChildren($, parent);
  if (blocks.length === 0) return [{ text: "" }];

  // If the children are all inline (text nodes), return them directly
  const hasBlocks = blocks.some((n) => "type" in n);
  if (!hasBlocks) return blocks;

  // If there are block children, flatten to inline for blockquote
  const inlines: SlateNode[] = [];
  for (const block of blocks) {
    if ("type" in block && "children" in block) {
      inlines.push(...(block as SlateElementNode).children);
    } else {
      inlines.push(block);
    }
  }
  return inlines.length > 0 ? inlines : [{ text: "" }];
}

/**
 * Wrap any loose inline nodes (text, mention, etc.) in paragraph blocks.
 */
function wrapLooseInlines(nodes: SlateNode[]): SlateElementNode[] {
  const result: SlateElementNode[] = [];
  let pendingInlines: SlateNode[] = [];

  const flushInlines = () => {
    if (pendingInlines.length > 0) {
      result.push({ type: "paragraph", children: pendingInlines });
      pendingInlines = [];
    }
  };

  for (const node of nodes) {
    if ("text" in node) {
      pendingInlines.push(node);
    } else if ("type" in node) {
      const isBlock = [
        "paragraph",
        "heading-one",
        "heading-two",
        "heading-three",
        "bulleted-list",
        "numbered-list",
        "list-item",
        "blockquote",
        "image",
        "video",
        "document-embed",
      ].includes(node.type);

      if (isBlock) {
        flushInlines();
        result.push(node as SlateElementNode);
      } else {
        // Inline element (mention, etc.)
        pendingInlines.push(node);
      }
    }
  }

  flushInlines();
  return result;
}

/**
 * Wrap consecutive list-item elements in list containers.
 */
function wrapListItems(blocks: SlateElementNode[]): SlateElementNode[] {
  // This is mainly for stray list-items; normally HTML parsing handles <ul>/<ol>
  return blocks;
}
