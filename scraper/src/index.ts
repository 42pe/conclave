import { writeFile, mkdir } from "node:fs/promises";
import { join } from "node:path";
import { ApiClient } from "./client.js";
import { authenticate } from "./auth.js";
import { logSection, log } from "./logger.js";
import { scrapeTags } from "./scrapers/tags.js";
import { scrapeUsers } from "./scrapers/users.js";
import { scrapeDiscussions } from "./scrapers/discussions.js";
import { downloadImages, rewriteImageUrls } from "./scrapers/images.js";
import type { ScraperConfig } from "./types.js";

const HUB_BASE = "https://hub.greenwave.org";
const STORAGE_PREFIX = "/storage/uploads/migrated";

async function main(): Promise<void> {
  console.log("\n╔══════════════════════════════════════════════════════════╗");
  console.log("║         NodeBB → Conclave Migration Scraper             ║");
  console.log("╚══════════════════════════════════════════════════════════╝\n");

  const config: ScraperConfig = {
    hubLoginUrl: "https://hub.greenwave.org/app",
    communityBaseUrl: `${HUB_BASE}/community`,
    email: process.env.NODEBB_EMAIL ?? "",
    password: process.env.NODEBB_PASSWORD ?? "",
    requestDelayMs: Number(process.env.DELAY_MS ?? "150"),
    outputDir: join(import.meta.dirname, "..", "output"),
    imageOutputDir: join(import.meta.dirname, "..", "output", "images"),
    only: parseOnlyFlag(),
  };

  // Set up output directory
  await mkdir(config.outputDir, { recursive: true });

  // Create API client
  const client = new ApiClient(HUB_BASE, config.requestDelayMs);

  // Authenticate
  logSection("Authentication");
  const cookies = process.env.NODEBB_COOKIES;
  if (!cookies) {
    console.error("  NODEBB_COOKIES env var is required.");
    console.error("  1. Log in at https://hub.greenwave.org/app in your browser");
    console.error("  2. Open DevTools > Console, run: document.cookie");
    console.error("  3. Set NODEBB_COOKIES env var with the output");
    process.exit(1);
  }
  await authenticate(client, cookies);

  const shouldRun = (step: string) => !config.only || config.only === step;

  // ── Step 1: Tags ──────────────────────────────────────────────────
  let tags;
  if (shouldRun("tags")) {
    tags = await scrapeTags(client);
    await saveJson(config.outputDir, "tags.json", tags);
  }

  // ── Step 2: Users ─────────────────────────────────────────────────
  let users;
  if (shouldRun("users")) {
    users = await scrapeUsers(client);
    await saveJson(config.outputDir, "users.json", users);
  }

  // ── Step 3: Discussions + Replies ─────────────────────────────────
  let discussions;
  let replies;
  if (shouldRun("discussions")) {
    const result = await scrapeDiscussions(client);
    discussions = result.discussions;
    replies = result.replies;
    await saveJson(config.outputDir, "discussions.json", discussions);
    await saveJson(config.outputDir, "replies.json", replies);
  }

  // ── Step 4: Download Images ───────────────────────────────────────
  if (shouldRun("images") && discussions && replies && users) {
    const { manifest, urlToLocalPath } = await downloadImages(
      client,
      discussions,
      replies,
      users,
      config.imageOutputDir,
    );
    await saveJson(config.outputDir, "images.json", manifest);

    // Rewrite image URLs in Slate JSON to local paths
    if (urlToLocalPath.size > 0) {
      log("Rewriting image URLs in discussion bodies...");
      for (const discussion of discussions) {
        discussion.body_slate = rewriteImageUrls(
          discussion.body_slate,
          urlToLocalPath,
          STORAGE_PREFIX,
        );
      }
      for (const reply of replies) {
        reply.body_slate = rewriteImageUrls(
          reply.body_slate,
          urlToLocalPath,
          STORAGE_PREFIX,
        );
      }

      // Also update user avatar paths
      for (const user of users) {
        if (user.avatar_url) {
          const localPath = urlToLocalPath.get(user.avatar_url);
          if (localPath) {
            user.avatar_local_path = localPath;
          }
        }
      }

      // Re-save with updated paths
      await saveJson(config.outputDir, "discussions.json", discussions);
      await saveJson(config.outputDir, "replies.json", replies);
      await saveJson(config.outputDir, "users.json", users);
    }
  }

  // ── Summary ───────────────────────────────────────────────────────
  logSection("Summary");
  if (tags) log(`Tags → Topics: ${tags.length}`);
  if (users) log(`Users: ${users.length}`);
  if (discussions) log(`Discussions: ${discussions.length}`);
  if (replies) log(`Replies: ${replies.length}`);
  log(`\nOutput saved to: ${config.outputDir}`);
  console.log("\nDone!\n");
}

function parseOnlyFlag(): ScraperConfig["only"] {
  const args = process.argv.slice(2);
  const onlyArg = args.find((a) => a.startsWith("--only="));
  if (!onlyArg) return undefined;
  const value = onlyArg.split("=")[1];
  if (["tags", "users", "discussions", "images"].includes(value)) {
    return value as ScraperConfig["only"];
  }
  console.error(`Invalid --only value: ${value}. Use tags|users|discussions|images.`);
  process.exit(1);
}

async function saveJson(
  dir: string,
  filename: string,
  data: unknown,
): Promise<void> {
  const path = join(dir, filename);
  await writeFile(path, JSON.stringify(data, null, 2), "utf-8");
  log(`Saved ${filename}`);
}

main().catch((err) => {
  console.error("\nScraper failed:", err);
  process.exit(1);
});
