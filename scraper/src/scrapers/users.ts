import { ApiClient } from "../client.js";
import { logSection, log, progressBar } from "../logger.js";
import type {
  ConclaveUserOutput,
  NodeBBUserListItem,
  NodeBBUserProfile,
  NodeBBUsersResponse,
} from "../types.js";

const USERS_LIST_ENDPOINT = "/community/api/users";
const USER_PROFILE_ENDPOINT = "/community/api/user";
const PLACEHOLDER_AVATAR = "placeholder-profile-picture.png";

export async function scrapeUsers(
  client: ApiClient,
): Promise<ConclaveUserOutput[]> {
  logSection("Scraping Users");

  // Phase A: Get all users from paginated list
  log("Phase A: Fetching user list...");
  const allUsers = await fetchAllUserPages(client);
  log(`Found ${allUsers.length} users total`);

  // Phase B: Fetch full profiles for active users (postcount > 0 or topiccount > 0)
  const activeUsers = allUsers.filter(
    (u) => u.postcount > 0 || (u as NodeBBUserProfile).topiccount > 0,
  );
  log(
    `Phase B: Fetching ${activeUsers.length} active user profiles for bios...`,
  );
  const profileMap = await fetchActiveProfiles(client, activeUsers);

  // Build output
  const output: ConclaveUserOutput[] = allUsers.map((user) => {
    const profile = profileMap.get(user.uid);
    return userToOutput(user, profile);
  });

  log(`Processed ${output.length} users`);
  return output;
}

async function fetchAllUserPages(
  client: ApiClient,
): Promise<NodeBBUserListItem[]> {
  const allUsers: NodeBBUserListItem[] = [];

  // Fetch first page to get pagination info
  const firstPage = await client.fetchJson<NodeBBUsersResponse>(
    `${USERS_LIST_ENDPOINT}?page=1`,
  );
  allUsers.push(...firstPage.users);
  const totalPages = firstPage.pagination.pageCount;

  log(`  ${totalPages} pages to fetch`);

  for (let page = 2; page <= totalPages; page++) {
    progressBar(page, totalPages, "user list pages");
    const data = await client.fetchJson<NodeBBUsersResponse>(
      `${USERS_LIST_ENDPOINT}?page=${page}`,
    );
    allUsers.push(...data.users);
  }

  return allUsers;
}

async function fetchActiveProfiles(
  client: ApiClient,
  activeUsers: NodeBBUserListItem[],
): Promise<Map<number, NodeBBUserProfile>> {
  const profiles = new Map<number, NodeBBUserProfile>();
  let fetched = 0;

  for (const user of activeUsers) {
    fetched++;
    progressBar(fetched, activeUsers.length, "active user profiles");

    try {
      const profile = await client.fetchJson<NodeBBUserProfile>(
        `${USER_PROFILE_ENDPOINT}/${encodeURIComponent(user.userslug)}`,
      );
      profiles.set(user.uid, profile);
    } catch (err) {
      // Some profiles may be inaccessible (banned, deleted, etc.)
      log(
        `\n  Warning: Could not fetch profile for ${user.username} (uid ${user.uid}): ${err}`,
      );
    }
  }

  return profiles;
}

function userToOutput(
  user: NodeBBUserListItem,
  profile?: NodeBBUserProfile,
): ConclaveUserOutput {
  const fullname = profile?.fullname || user.fullname || "";
  const { firstName, lastName } = splitName(fullname);

  // Determine avatar: skip placeholders
  let avatarUrl: string | null = null;
  const picture = profile?.picture || user.picture;
  if (picture && !picture.includes(PLACEHOLDER_AVATAR)) {
    avatarUrl = picture;
  }

  return {
    nodebb_uid: user.uid,
    username: user.username,
    email: user.email || "",
    name: fullname,
    first_name: firstName,
    last_name: lastName,
    bio: decodeHtmlEntities(profile?.aboutme || ""),
    avatar_url: avatarUrl,
    avatar_local_path: null, // Filled later by image downloader
    joined_at:
      profile?.joindateISO ||
      new Date(user.lastonline || Date.now()).toISOString(),
    last_seen_at: user.lastonlineISO || "",
    is_banned: user.banned,
    country: profile?.country || user.country || "",
    state: profile?.state || user.state || "",
  };
}

function splitName(fullname: string): {
  firstName: string;
  lastName: string;
} {
  if (!fullname) return { firstName: "", lastName: "" };
  const parts = fullname.trim().split(/\s+/);
  return {
    firstName: parts[0] || "",
    lastName: parts.slice(1).join(" "),
  };
}

function decodeHtmlEntities(text: string): string {
  if (!text) return "";
  return text
    .replace(/&#x2F;/g, "/")
    .replace(/&amp;/g, "&")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&#x27;/g, "'");
}
