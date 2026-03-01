import { ApiClient } from "../client.js";
import { logSection, log } from "../logger.js";
import type { ConclaveTopicOutput, NodeBBTagsResponse } from "../types.js";

const TAGS_ENDPOINT = "/community/api/tags";

// Map NodeBB tag names to appropriate Lucide icons
const TAG_ICON_MAP: Record<string, string> = {
  "introductions + connections": "users",
  "bulletin board": "clipboard",
  "research + industry news": "newspaper",
  "processing + markets": "shopping-cart",
  "farm operations": "tractor",
  "kelp hatchery": "sprout",
  "farm design": "drafting-compass",
  "site evaluation + permitting": "map-pin",
};

export async function scrapeTags(
  client: ApiClient,
): Promise<ConclaveTopicOutput[]> {
  logSection("Scraping Tags → Topics");

  const data = await client.fetchJson<NodeBBTagsResponse>(TAGS_ENDPOINT);
  log(`Found ${data.tags.length} tags`);

  const topics: ConclaveTopicOutput[] = data.tags.map((tag, index) => ({
    nodebb_tag: tag.value,
    title: titleCase(tag.value),
    slug: tag.class,
    description: "",
    icon: TAG_ICON_MAP[tag.value] ?? "message-square",
    visibility: "public",
    sort_order: index + 1,
    discussion_count: tag.score,
  }));

  for (const topic of topics) {
    log(`  ${topic.title} (${topic.discussion_count} discussions) → ${topic.slug}`);
  }

  return topics;
}

function titleCase(str: string): string {
  return str
    .split(" ")
    .map((word) => {
      if (word === "+") return "+";
      return word.charAt(0).toUpperCase() + word.slice(1);
    })
    .join(" ");
}
