import { writeFile, mkdir } from "node:fs/promises";
import { dirname, extname, join } from "node:path";
import { randomUUID } from "node:crypto";
import { ApiClient } from "../client.js";
import { logSection, log, progressBar } from "../logger.js";
import type {
  ConclaveDiscussionOutput,
  ConclaveReplyOutput,
  ConclaveUserOutput,
  ImageManifestEntry,
  SlateElementNode,
  SlateNode,
} from "../types.js";

const PLACEHOLDER_AVATAR = "placeholder-profile-picture.png";

interface ImageCollectionResult {
  manifest: ImageManifestEntry[];
  urlToLocalPath: Map<string, string>;
}

/**
 * Collect all image URLs from discussions, replies, and user avatars.
 * Download them to the output directory and return a mapping of URL → local path.
 */
export async function downloadImages(
  client: ApiClient,
  discussions: ConclaveDiscussionOutput[],
  replies: ConclaveReplyOutput[],
  users: ConclaveUserOutput[],
  imageOutputDir: string,
): Promise<ImageCollectionResult> {
  logSection("Downloading Images");

  // Collect all image URLs
  const manifest: ImageManifestEntry[] = [];
  const seenUrls = new Set<string>();

  // From discussions
  for (const discussion of discussions) {
    const urls = extractImageUrls(discussion.body_slate);
    for (const url of urls) {
      if (!seenUrls.has(url)) {
        seenUrls.add(url);
        manifest.push({
          source_url: url,
          local_path: null,
          referenced_by: [{ type: "discussion", id: discussion.nodebb_tid }],
        });
      } else {
        const entry = manifest.find((e) => e.source_url === url);
        entry?.referenced_by.push({
          type: "discussion",
          id: discussion.nodebb_tid,
        });
      }
    }
  }

  // From replies
  for (const reply of replies) {
    const urls = extractImageUrls(reply.body_slate);
    for (const url of urls) {
      if (!seenUrls.has(url)) {
        seenUrls.add(url);
        manifest.push({
          source_url: url,
          local_path: null,
          referenced_by: [{ type: "reply", id: reply.nodebb_pid }],
        });
      } else {
        const entry = manifest.find((e) => e.source_url === url);
        entry?.referenced_by.push({ type: "reply", id: reply.nodebb_pid });
      }
    }
  }

  // From user avatars (skip placeholders)
  for (const user of users) {
    if (user.avatar_url && !user.avatar_url.includes(PLACEHOLDER_AVATAR)) {
      if (!seenUrls.has(user.avatar_url)) {
        seenUrls.add(user.avatar_url);
        manifest.push({
          source_url: user.avatar_url,
          local_path: null,
          referenced_by: [{ type: "avatar", id: user.nodebb_uid }],
        });
      }
    }
  }

  log(`Found ${manifest.length} unique images to download`);

  // Download each image
  const urlToLocalPath = new Map<string, string>();
  await mkdir(join(imageOutputDir, "content"), { recursive: true });
  await mkdir(join(imageOutputDir, "avatars"), { recursive: true });

  let downloaded = 0;
  let failed = 0;

  for (let i = 0; i < manifest.length; i++) {
    const entry = manifest[i];
    progressBar(i + 1, manifest.length, "images");

    try {
      const isAvatar = entry.referenced_by.some((r) => r.type === "avatar");
      const subdir = isAvatar ? "avatars" : "content";
      const ext = getExtension(entry.source_url);
      const filename = `${randomUUID()}${ext}`;
      const localPath = join(subdir, filename);
      const fullPath = join(imageOutputDir, localPath);

      await mkdir(dirname(fullPath), { recursive: true });

      const response = await client.fetch(entry.source_url);
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const buffer = Buffer.from(await response.arrayBuffer());
      await writeFile(fullPath, buffer);

      entry.local_path = localPath;
      urlToLocalPath.set(entry.source_url, localPath);
      downloaded++;
    } catch (err) {
      failed++;
      if (failed <= 5) {
        log(`\n  Warning: Failed to download ${entry.source_url}: ${err}`);
      }
    }
  }

  log(`\nDownloaded ${downloaded} images, ${failed} failed`);

  return { manifest, urlToLocalPath };
}

/**
 * After downloading, update Slate JSON to reference local paths.
 */
export function rewriteImageUrls(
  slateNodes: SlateNode[],
  urlToLocalPath: Map<string, string>,
  storagePrefix: string,
): SlateNode[] {
  return slateNodes.map((node) => rewriteNode(node, urlToLocalPath, storagePrefix));
}

function rewriteNode(
  node: SlateNode,
  urlToLocalPath: Map<string, string>,
  storagePrefix: string,
): SlateNode {
  if ("text" in node) return node;

  const element = node as SlateElementNode;
  const updated = { ...element };

  if (element.type === "image" && element.src) {
    const localPath = urlToLocalPath.get(element.src);
    if (localPath) {
      updated.src = `${storagePrefix}/${localPath}`;
    }
  }

  if (element.children) {
    updated.children = element.children.map((child) =>
      rewriteNode(child, urlToLocalPath, storagePrefix),
    );
  }

  return updated;
}

/**
 * Recursively extract image URLs from Slate JSON.
 */
function extractImageUrls(nodes: SlateNode[]): string[] {
  const urls: string[] = [];

  for (const node of nodes) {
    if ("text" in node) continue;

    const element = node as SlateElementNode;
    if (element.type === "image" && element.src) {
      urls.push(element.src);
    }
    if (element.children) {
      urls.push(...extractImageUrls(element.children));
    }
  }

  return urls;
}

function getExtension(url: string): string {
  try {
    const pathname = new URL(url).pathname;
    const ext = extname(pathname).toLowerCase();
    if ([".jpg", ".jpeg", ".png", ".gif", ".webp", ".svg", ".bmp"].includes(ext)) {
      return ext;
    }
  } catch {
    // ignore
  }
  return ".jpg"; // Default
}
