import type {
  QuillDelta,
  QuillDeltaOp,
  SlateElementNode,
  SlateNode,
  SlateTextNode,
} from "../types.js";

/**
 * Convert a Quill Delta document to Slate.js JSON format.
 *
 * Quill Delta format:
 * - Text inserts with inline attributes (bold, italic, etc.)
 * - "\n" inserts mark block boundaries
 * - "\n" with block attributes (header, list, blockquote) define block types
 * - Embed inserts ({ image: url }) are inline objects
 *
 * Slate JSON format:
 * - Array of block elements (paragraph, heading-one, etc.)
 * - Each block has children (text nodes with marks, or inline elements)
 * - Void elements (image) have { type, src, children: [{ text: "" }] }
 */
export function quillDeltaToSlate(delta: QuillDelta): SlateNode[] {
  if (!delta?.ops?.length) {
    return [{ type: "paragraph", children: [{ text: "" }] }];
  }

  // Step 1: Normalize ops — split multi-line text inserts into separate ops
  const normalizedOps = normalizeOps(delta.ops);

  // Step 2: Group ops into lines (split by \n characters)
  const lines = groupIntoLines(normalizedOps);

  // Step 3: Convert each line into a Slate block
  const blocks = linesToSlateBlocks(lines);

  // Step 4: Post-process — wrap consecutive list-items in list containers
  const wrapped = wrapListItems(blocks);

  return wrapped.length > 0
    ? wrapped
    : [{ type: "paragraph", children: [{ text: "" }] }];
}

interface Line {
  inlineOps: QuillDeltaOp[];
  blockAttributes: Record<string, unknown>;
}

/**
 * Normalize ops: split text inserts containing multiple \n into separate ops.
 * This ensures each \n is its own op for clean line splitting.
 */
function normalizeOps(ops: QuillDeltaOp[]): QuillDeltaOp[] {
  const result: QuillDeltaOp[] = [];

  for (const op of ops) {
    if (typeof op.insert !== "string") {
      result.push(op);
      continue;
    }

    const text = op.insert;
    const parts = text.split("\n");

    for (let i = 0; i < parts.length; i++) {
      if (parts[i]) {
        result.push({ insert: parts[i], attributes: op.attributes });
      }
      if (i < parts.length - 1) {
        // The \n inherits block attributes from the original op
        result.push({ insert: "\n", attributes: op.attributes });
      }
    }
  }

  return result;
}

/**
 * Group normalized ops into lines. A line ends at each \n op.
 * The \n op's attributes determine the block type for that line.
 */
function groupIntoLines(ops: QuillDeltaOp[]): Line[] {
  const lines: Line[] = [];
  let currentInlineOps: QuillDeltaOp[] = [];

  for (const op of ops) {
    if (typeof op.insert === "string" && op.insert === "\n") {
      lines.push({
        inlineOps: currentInlineOps,
        blockAttributes: (op.attributes as Record<string, unknown>) ?? {},
      });
      currentInlineOps = [];
    } else {
      currentInlineOps.push(op);
    }
  }

  // Remaining ops without a trailing \n form the last line
  if (currentInlineOps.length > 0) {
    lines.push({
      inlineOps: currentInlineOps,
      blockAttributes: {},
    });
  }

  return lines;
}

/**
 * Convert each line into a Slate block element based on its block attributes.
 */
function linesToSlateBlocks(lines: Line[]): SlateElementNode[] {
  const blocks: SlateElementNode[] = [];

  for (const line of lines) {
    const children = inlineOpsToSlateChildren(line.inlineOps);
    const attrs = line.blockAttributes;

    if (attrs.header === 1) {
      blocks.push({ type: "heading-one", children });
    } else if (attrs.header === 2) {
      blocks.push({ type: "heading-two", children });
    } else if (attrs.header === 3) {
      blocks.push({ type: "heading-three", children });
    } else if (attrs.blockquote) {
      blocks.push({ type: "blockquote", children });
    } else if (attrs.list === "bullet") {
      blocks.push({ type: "list-item", children, _listType: "bulleted-list" } as SlateElementNode & { _listType: string });
    } else if (attrs.list === "ordered") {
      blocks.push({ type: "list-item", children, _listType: "numbered-list" } as SlateElementNode & { _listType: string });
    } else if (attrs["code-block"]) {
      // Code blocks: render as paragraph with code marks on all text
      const codeChildren = children.map((child) => {
        if ("text" in child) {
          return { ...child, code: true };
        }
        return child;
      });
      blocks.push({ type: "paragraph", children: codeChildren });
    } else {
      // Check if the line contains only an image/video embed
      const embedBlock = extractEmbedBlock(line.inlineOps);
      if (embedBlock) {
        blocks.push(embedBlock);
      } else {
        blocks.push({ type: "paragraph", children });
      }
    }
  }

  return blocks;
}

/**
 * Convert inline ops to Slate children (text nodes + inline elements).
 */
function inlineOpsToSlateChildren(ops: QuillDeltaOp[]): SlateNode[] {
  if (ops.length === 0) {
    return [{ text: "" }];
  }

  const children: SlateNode[] = [];

  for (const op of ops) {
    if (typeof op.insert === "string") {
      const textNode: SlateTextNode = { text: op.insert };
      if (op.attributes?.bold) textNode.bold = true;
      if (op.attributes?.italic) textNode.italic = true;
      if (op.attributes?.underline) textNode.underline = true;
      if (op.attributes?.code) textNode.code = true;

      // Links: Conclave doesn't have a link inline type in ALLOWED_BLOCK_TYPES
      // or INLINE_VOID_TYPES. Links appear as underlined text in the original.
      // For now, just preserve the text with its marks.
      // If link support is needed later, this is where to add it.

      // Mentions from Quill
      if (op.attributes?.mention) {
        const mention = op.attributes.mention as {
          id: string | number;
          value: string;
        };
        children.push({
          type: "mention",
          userId: Number(mention.id),
          username: String(mention.value).replace(/^@/, ""),
          children: [{ text: "" }],
        } as SlateElementNode);
        continue;
      }

      children.push(textNode);
    } else if (typeof op.insert === "object" && op.insert !== null) {
      // Embed objects
      if ("image" in op.insert && op.insert.image) {
        children.push({
          type: "image",
          src: String(op.insert.image),
          children: [{ text: "" }],
        });
      } else if ("video" in op.insert && op.insert.video) {
        children.push({
          type: "video",
          src: String(op.insert.video),
          children: [{ text: "" }],
        });
      } else if ("mention" in op.insert) {
        const mention = op.insert.mention as {
          id: string | number;
          value: string;
        };
        children.push({
          type: "mention",
          userId: Number(mention.id),
          username: String(mention.value).replace(/^@/, ""),
          children: [{ text: "" }],
        } as SlateElementNode);
      }
    }
  }

  return children.length > 0 ? children : [{ text: "" }];
}

/**
 * Check if the inline ops consist of a single embed (image/video).
 * If so, return it as a top-level block.
 */
function extractEmbedBlock(ops: QuillDeltaOp[]): SlateElementNode | null {
  if (ops.length !== 1) return null;
  const op = ops[0];
  if (typeof op.insert !== "object" || op.insert === null) return null;

  if ("image" in op.insert && op.insert.image) {
    return {
      type: "image",
      src: String(op.insert.image),
      children: [{ text: "" }],
    };
  }
  if ("video" in op.insert && op.insert.video) {
    return {
      type: "video",
      src: String(op.insert.video),
      children: [{ text: "" }],
    };
  }

  return null;
}

/**
 * Wrap consecutive list-item elements in their appropriate list container.
 * E.g., consecutive list-items with _listType="bulleted-list" get wrapped
 * in a { type: "bulleted-list", children: [...list-items] } node.
 */
function wrapListItems(blocks: SlateElementNode[]): SlateElementNode[] {
  const result: SlateElementNode[] = [];
  let i = 0;

  while (i < blocks.length) {
    const block = blocks[i];
    const listType = (block as SlateElementNode & { _listType?: string })
      ._listType;

    if (block.type === "list-item" && listType) {
      // Collect consecutive list items of the same type
      const listChildren: SlateElementNode[] = [];
      while (i < blocks.length) {
        const current = blocks[i] as SlateElementNode & {
          _listType?: string;
        };
        if (current.type !== "list-item" || current._listType !== listType) {
          break;
        }
        // Remove the temporary _listType marker
        const { _listType: _, ...cleanItem } = current;
        listChildren.push(cleanItem as SlateElementNode);
        i++;
      }
      result.push({ type: listType, children: listChildren });
    } else {
      // Remove _listType if present on non-list items (shouldn't happen)
      const { _listType: _, ...clean } = block as SlateElementNode & {
        _listType?: string;
      };
      result.push(clean as SlateElementNode);
      i++;
    }
  }

  return result;
}
