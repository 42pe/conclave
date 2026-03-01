import { ApiClient } from "../client.js";
import { quillDeltaToSlate } from "../converters/quill-to-slate.js";
import { htmlToSlate } from "../converters/html-to-slate.js";
import { logSection, log, progressBar } from "../logger.js";
import type {
  ConclaveDiscussionOutput,
  ConclaveReplyOutput,
  NodeBBCategoryResponse,
  NodeBBPost,
  NodeBBTopicDetailResponse,
  NodeBBTopicListItem,
  QuillDelta,
  SlateNode,
} from "../types.js";

const CATEGORY_DEFAULT_ENDPOINT = "/community/api/category/2/default";

interface DiscussionsResult {
  discussions: ConclaveDiscussionOutput[];
  replies: ConclaveReplyOutput[];
}

export async function scrapeDiscussions(
  client: ApiClient,
): Promise<DiscussionsResult> {
  logSection("Scraping Discussions + Replies");

  // Phase A: Get all topic IDs from category listing
  log("Phase A: Fetching topic list from category pages...");
  const topicListItems = await fetchAllTopicListItems(client);
  log(`Found ${topicListItems.length} topics total`);

  // Phase B: Fetch full topic detail for each
  log("Phase B: Fetching topic details...");
  const discussions: ConclaveDiscussionOutput[] = [];
  const allReplies: ConclaveReplyOutput[] = [];
  let fetched = 0;

  for (const topicItem of topicListItems) {
    fetched++;
    progressBar(fetched, topicListItems.length, "topic details");

    // Skip deleted topics
    if (topicItem.deleted === 1) continue;

    try {
      const detail = await client.fetchJson<NodeBBTopicDetailResponse>(
        `/community/api/topic/${topicItem.tid}/`,
      );

      // Extract discussion (OP)
      const discussion = extractDiscussion(topicItem, detail);
      discussions.push(discussion);

      // Extract replies
      const replies = extractReplies(detail);
      allReplies.push(...replies);
    } catch (err) {
      log(
        `\n  Warning: Could not fetch topic ${topicItem.tid}: ${err}`,
      );
    }
  }

  log(`\nExtracted ${discussions.length} discussions and ${allReplies.length} replies`);
  return { discussions, replies: allReplies };
}

async function fetchAllTopicListItems(
  client: ApiClient,
): Promise<NodeBBTopicListItem[]> {
  const allTopics: NodeBBTopicListItem[] = [];

  // Fetch Default category (cid=2), pages 1..N
  const firstPage = await client.fetchJson<NodeBBCategoryResponse>(
    `${CATEGORY_DEFAULT_ENDPOINT}?page=1`,
  );
  allTopics.push(...firstPage.topics);
  const totalPages = firstPage.pagination.pageCount;

  log(`  Default category: ${totalPages} pages`);

  for (let page = 2; page <= totalPages; page++) {
    progressBar(page, totalPages, "category pages");
    const data = await client.fetchJson<NodeBBCategoryResponse>(
      `${CATEGORY_DEFAULT_ENDPOINT}?page=${page}`,
    );
    allTopics.push(...data.topics);
  }

  return allTopics;
}

function extractDiscussion(
  topicItem: NodeBBTopicListItem,
  detail: NodeBBTopicDetailResponse,
): ConclaveDiscussionOutput {
  // OP is at topicpost.posts[0]
  const opPost = detail.topicpost?.posts?.[0];

  const bodyHtml = opPost?.content ?? "";
  const quillDeltaRaw = opPost?.quillDelta ?? null;
  const bodySlate = convertContent(bodyHtml, quillDeltaRaw);

  // Extract slug: NodeBB format is "tid/slug-text", we want just the slug part
  const slugParts = detail.slug.split("/");
  const slug = slugParts.length > 1 ? slugParts.slice(1).join("/") : detail.slug;

  const tag = topicItem.tags?.[0]?.value ?? "";

  return {
    nodebb_tid: detail.tid,
    nodebb_tag: tag,
    title: detail.titleRaw || detail.title,
    slug,
    nodebb_uid: detail.uid,
    body_html: bodyHtml,
    body_slate: bodySlate,
    body_quill_delta: quillDeltaRaw,
    created_at: detail.timestampISO || new Date(detail.timestamp).toISOString(),
    updated_at:
      detail.lastposttimeISO || new Date(detail.lastposttime).toISOString(),
    is_pinned: detail.pinned === 1,
    is_locked: detail.locked === 1,
    view_count: detail.viewcount,
    like_count: opPost?.upvotes ?? detail.upvotes ?? 0,
    reply_count: Math.max(0, (detail.postcount ?? 1) - 1),
    country: detail.country || "",
    state: detail.state || "",
    location: detail.location || "",
  };
}

function extractReplies(
  detail: NodeBBTopicDetailResponse,
): ConclaveReplyOutput[] {
  const mainPid = detail.mainPid;

  // Collect ALL posts from both posts[] and nested responses[]
  const allPosts = new Map<number, NodeBBPost>();

  // Posts from the main posts[] array
  for (const post of detail.posts ?? []) {
    if (post.pid === mainPid) continue; // Skip OP
    if (post.deleted === 1) continue; // Skip deleted
    allPosts.set(post.pid, post);

    // Also extract nested responses
    collectResponses(post.responses ?? [], allPosts);
  }

  // Also check topicpost.posts for any extra replies beyond the OP
  for (const post of detail.topicpost?.posts ?? []) {
    if (post.pid === mainPid) continue;
    if (post.deleted === 1) continue;
    if (!allPosts.has(post.pid)) {
      allPosts.set(post.pid, post);
      collectResponses(post.responses ?? [], allPosts);
    }
  }

  // Build pid → depth mapping
  const pidToDepth = new Map<number, number>();
  const pidToParent = new Map<number, number | null>();

  for (const post of allPosts.values()) {
    const toPid = post.toPid ? Number(post.toPid) : null;

    if (!toPid || toPid === mainPid) {
      // Top-level reply (direct reply to discussion)
      pidToDepth.set(post.pid, 0);
      pidToParent.set(post.pid, null);
    } else {
      // Nested reply — compute depth
      const parentDepth = pidToDepth.get(toPid);
      if (parentDepth !== undefined) {
        if (parentDepth >= 2) {
          // Flatten: max depth is 2, so this becomes a sibling of its parent
          pidToDepth.set(post.pid, 2);
          // Find the depth-1 ancestor
          pidToParent.set(post.pid, findDepthOneAncestor(toPid, pidToDepth, pidToParent));
        } else {
          pidToDepth.set(post.pid, parentDepth + 1);
          pidToParent.set(post.pid, toPid);
        }
      } else {
        // Parent not found (maybe deleted) — treat as top-level
        pidToDepth.set(post.pid, 0);
        pidToParent.set(post.pid, null);
      }
    }
  }

  // Convert to output format
  const replies: ConclaveReplyOutput[] = [];

  // Sort by timestamp to ensure parent-before-child ordering
  const sortedPosts = Array.from(allPosts.values()).sort(
    (a, b) => a.timestamp - b.timestamp,
  );

  // Second pass for any posts whose parents weren't processed yet
  for (const post of sortedPosts) {
    if (!pidToDepth.has(post.pid)) {
      const toPid = post.toPid ? Number(post.toPid) : null;
      if (!toPid || toPid === mainPid) {
        pidToDepth.set(post.pid, 0);
        pidToParent.set(post.pid, null);
      } else {
        const parentDepth = pidToDepth.get(toPid) ?? 0;
        const depth = Math.min(parentDepth + 1, 2);
        pidToDepth.set(post.pid, depth);
        pidToParent.set(
          post.pid,
          depth > 2
            ? findDepthOneAncestor(toPid, pidToDepth, pidToParent)
            : toPid,
        );
      }
    }
  }

  for (const post of sortedPosts) {
    const bodyHtml = post.content ?? "";
    const quillDeltaRaw = post.quillDelta ?? null;
    const bodySlate = convertContent(bodyHtml, quillDeltaRaw);

    replies.push({
      nodebb_pid: post.pid,
      nodebb_tid: post.tid,
      nodebb_uid: post.uid,
      nodebb_parent_pid: pidToParent.get(post.pid) ?? null,
      depth: pidToDepth.get(post.pid) ?? 0,
      body_html: bodyHtml,
      body_slate: bodySlate,
      body_quill_delta: quillDeltaRaw,
      created_at: post.timestampISO || new Date(post.timestamp).toISOString(),
      updated_at:
        post.edited && post.editedISO ? post.editedISO : null,
      like_count: post.upvotes ?? 0,
    });
  }

  return replies;
}

/**
 * Recursively collect nested responses into the allPosts map.
 */
function collectResponses(
  responses: NodeBBPost[],
  allPosts: Map<number, NodeBBPost>,
): void {
  for (const post of responses) {
    if (post.deleted === 1) continue;
    if (!allPosts.has(post.pid)) {
      allPosts.set(post.pid, post);
    }
    if (post.responses?.length) {
      collectResponses(post.responses, allPosts);
    }
  }
}

/**
 * Find the depth-1 ancestor for flattening deeply nested replies.
 */
function findDepthOneAncestor(
  pid: number,
  pidToDepth: Map<number, number>,
  pidToParent: Map<number, number | null>,
): number | null {
  let current: number | null = pid;
  while (current !== null) {
    const depth = pidToDepth.get(current);
    if (depth === 1) return current;
    current = pidToParent.get(current) ?? null;
  }
  return pid; // Fallback to the immediate parent
}

/**
 * Convert content using quillDelta (preferred) or HTML fallback.
 */
function convertContent(
  html: string,
  quillDeltaRaw: string | null,
): SlateNode[] {
  // Prefer quillDelta if available
  if (quillDeltaRaw) {
    try {
      const delta: QuillDelta = JSON.parse(quillDeltaRaw);
      if (delta?.ops?.length) {
        return quillDeltaToSlate(delta);
      }
    } catch {
      // Fall through to HTML conversion
    }
  }

  // Fallback to HTML conversion
  return htmlToSlate(html);
}
