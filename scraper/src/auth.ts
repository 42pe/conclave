import { ApiClient } from "./client.js";

const COMMUNITY_BASE = "https://hub.greenwave.org/community";

/**
 * Authenticate the API client using cookies from the NODEBB_COOKIES env var.
 *
 * The Hub uses a JWT cookie (`gw-p2022-session`) which NodeBB middleware
 * reads to create an `express.sid` session. The flow is:
 * 1. Send JWT cookie to the community root page (not /api/)
 * 2. NodeBB validates the JWT and returns Set-Cookie: express.sid (HttpOnly)
 * 3. Subsequent requests include express.sid → authenticated
 */
export async function authenticate(
  client: ApiClient,
  cookieString: string,
): Promise<void> {
  client.setManualCookies(cookieString);

  // Hit the community root to trigger JWT → express.sid session creation.
  // The /api/ endpoints don't trigger the JWT middleware, but the root page does.
  console.log("  Establishing session...");
  const warmupRes = await client.fetch(`${COMMUNITY_BASE}/`);
  await warmupRes.text();

  // Verify we're authenticated via the API
  const res = await client.fetchJson<{ loggedIn?: boolean; uid?: number }>(
    `${COMMUNITY_BASE}/api/config`,
  );

  if (!res.loggedIn) {
    console.error(
      "\n  Cookie authentication failed. To get fresh cookies:",
    );
    console.error("  1. Log in at https://hub.greenwave.org/app in your browser");
    console.error("  2. Open DevTools > Console, run: document.cookie");
    console.error("  3. Set NODEBB_COOKIES env var with the output");
    throw new Error("Authentication failed — cookies may be expired");
  }

  console.log(`  Authenticated as uid ${res.uid}`);
}
