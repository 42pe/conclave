# NodeBB → Conclave Migration Plan

## Nomenclature Mapping

| NodeBB          | Conclave   | Notes                                |
| --------------- | ---------- | ------------------------------------ |
| Tag             | Topic      | 8 tags total                         |
| Topic           | Discussion | 996 total (991 Default + 5 Featured) |
| Post / Response | Reply      | ~2,977 total                         |
| User            | User       | 8,798 total                          |

---

## 1. Data Volumes & API Overview

### Authentication

- Login via `https://hub.greenwave.org/app` (Hub login form)
- Session cookies transfer to `/community/` domain
- All `/community/api/*` calls must include `credentials: 'include'` and be executed from the community domain context
- The scraper should use a cookie jar / session that persists after Hub login

### Endpoints & Pagination

| Endpoint                                                  | Returns                  | Pagination                      | Total     |
| --------------------------------------------------------- | ------------------------ | ------------------------------- | --------- |
| `GET /community/api/tags/`                                | All 8 tags               | None (single response)          | 8         |
| `GET /community/api/category/2/default?page={n}`          | 20 topics/page           | 50 pages                        | 991       |
| `GET /community/api/category/5/featured-content?page={n}` | Topics                   | 1 page                          | 5         |
| `GET /community/api/topic/{tid}/`                         | Full topic + all replies | Usually 1 page (max ~8 replies) | per-topic |
| `GET /community/api/users?page={n}`                       | 50 users/page            | 176 pages                       | 8,798     |
| `GET /community/api/user/{userslug}`                      | Full user profile        | N/A                             | per-user  |

---

## 2. Scraping Strategy — Order of Operations

### Step 1: Scrape Tags (→ Topics)

**Endpoint**: `GET /community/api/tags/`

**Response structure**:

```json
{
    "tags": [
        {
            "value": "introductions + connections",
            "score": 367,
            "valueEscaped": "introductions + connections",
            "valueEncoded": "introductions%20%2B%20connections",
            "class": "introductions-+-connections"
        }
    ]
}
```

**Fields to extract**:
| NodeBB field | Conclave field | Transform |
|---|---|---|
| `tag.value` | `topic.title` | Direct |
| `tag.class` | `topic.slug` | Direct (already slugified) |
| `tag.score` | — | Informational (topic count) |

**All 8 tags**:

1. introductions + connections (367 discussions)
2. bulletin board (185)
3. research + industry news (108)
4. processing + markets (84)
5. farm operations (81)
6. kelp hatchery (80)
7. farm design (59)
8. site evaluation + permitting (32)

**Notes**:

- Each NodeBB topic has exactly **one** tag (no multi-tagging found)
- Map to Conclave Topics with `visibility: public`
- The 5 "Featured Content" category topics also each have one tag

---

### Step 2: Scrape Users

**Phase A — User list** (for basic info + building uid→userslug map):

**Endpoint**: `GET /community/api/users?page={n}` (pages 1–176, 50 users/page)

**Response structure** (per user in list):

```json
{
    "uid": 7893,
    "username": "Erkman_Engineering",
    "userslug": "erkman_engineering",
    "picture": "https://ds.hub.greenwave.org/assets/placeholder-profile-picture.png",
    "status": "offline",
    "postcount": 0,
    "reputation": 0,
    "email": "erkman.joshua@gmail.com",
    "fullname": "Joshua Erkman",
    "displayname": "Erkman_Engineering",
    "location": "Canby OR, US",
    "state": "OR",
    "country": "US",
    "banned": false,
    "lastonline": 1736939407788,
    "lastonlineISO": "2025-01-15T11:10:07.788Z"
}
```

**Phase B — Full profiles** (for bio, website, etc.):

**Endpoint**: `GET /community/api/user/{userslug}` (per user, 8,798 calls)

**Response structure** (additional fields beyond list):

```json
{
    "uid": 246,
    "username": "danica_sheean",
    "userslug": "danica_sheean",
    "email": "danica.sheean@gmail.com",
    "fullname": "Danica Sheean",
    "displayname": "danica_sheean",
    "aboutme": "Business Development // Motion Picture Producing",
    "signature": "",
    "website": "",
    "location": "",
    "birthday": "",
    "picture": "https://assets.hub.greenwave.org/images/...",
    "uploadedpicture": "...",
    "reputation": 0,
    "postcount": 5,
    "topiccount": 0,
    "profileviews": 0,
    "joindate": 1650395034613,
    "joindateISO": "2022-04-19T19:03:54.613Z",
    "lastonline": 1654043769853,
    "lastonlineISO": "2022-06-01T00:36:09.853Z",
    "banned": false,
    "banned:expire": 0,
    "status": "offline",
    "followerCount": 0,
    "followingCount": 0,
    "country": "",
    "state": "",
    "city": "",
    "headline": "",
    "cover:url": "",
    "groupTitle": "[null]",
    "social_ig": "",
    "social_fb": "",
    "social_tw": ""
}
```

**Fields to extract → Conclave mapping**:
| NodeBB field | Conclave field | Transform |
|---|---|---|
| `uid` | — | Keep as `nodebb_uid` for foreign key mapping |
| `username` | `user.username` | Lowercase, validate against Conclave regex `[a-z][a-z0-9_-]{4,15}` |
| `email` | `user.email` | Direct |
| `fullname` | `user.name` | Direct; split into `first_name` + `last_name` if needed |
| `aboutme` | `user.bio` | HTML-decode entities (`&#x2F;` → `/`) |
| `picture` | `user.avatar_path` | Download image, re-upload to Conclave storage |
| `website` | — | Optional: store in bio or custom field |
| `joindate` / `joindateISO` | `user.created_at` | Parse ISO timestamp |
| `lastonline` / `lastonlineISO` | — | Informational |
| `banned` | `user.is_deleted` or status | Map banned users |
| `country` | — | Store in location if Conclave has locations |
| `state` | — | Store in location if Conclave has locations |
| `location` | — | Free-text location |
| `signature` | — | Not migrated (Conclave has no signatures) |

**Username validation concerns**:

- NodeBB allows uppercase: `Erkman_Engineering` → need to lowercase
- NodeBB may allow shorter usernames or special chars
- Conclave requires: `^[a-z][a-z0-9_-]{4,15}$`
- Strategy: lowercase, truncate/pad as needed, generate fallback usernames for invalid ones

**Optimization**: The user list endpoint provides most fields. Only fetch full profiles for users with `postcount > 0` or `topiccount > 0` (active users) to get `aboutme`, `website`, etc. This reduces ~8,798 profile calls to maybe ~1,000–2,000.

---

### Step 3: Scrape Topics (→ Discussions)

**Phase A — Get all topic IDs from category listing**:

**Endpoint**: `GET /community/api/category/2/default?page={n}` (pages 1–50)
**Endpoint**: `GET /community/api/category/5/featured-content?page={n}` (page 1)

**Response structure** (per topic in category list):

```json
{
    "tid": 1009,
    "title": "Seaweed Grower Interviews!",
    "titleRaw": "...",
    "slug": "1009/seaweed-grower-interviews",
    "cid": 2,
    "uid": 8930,
    "mainPid": 3073,
    "postcount": 2,
    "viewcount": 20,
    "timestamp": 1740506954086,
    "timestampISO": "2026-02-25T18:29:14.086Z",
    "lastposttime": 1740600349629,
    "lastposttimeISO": "...",
    "pinned": 0,
    "locked": 0,
    "deleted": 0,
    "upvotes": 0,
    "downvotes": 0,
    "votes": 0,
    "tags": [{ "value": "introductions + connections" }],
    "country": "",
    "state": "",
    "location": "",
    "user": { "uid": 8930, "username": "...", "fullname": "..." },
    "teaser": { "pid": 3074, "uid": 1, "content": "...", "timestampISO": "..." }
}
```

This gives us the topic metadata and tag assignment, but NOT the body content.

**Phase B — Get full topic detail (OP body + all replies)**:

**Endpoint**: `GET /community/api/topic/{tid}/` (per topic, ~996 calls)

**OP (main post) location**: `response.topicpost.posts[0]`

```json
{
    "content": "<p>HTML content of the discussion body...</p>",
    "pid": 159,
    "tid": 112,
    "timestamp": 1650395654676,
    "timestampISO": "2022-04-19T19:14:14.676Z",
    "uid": 243,
    "upvotes": 6,
    "downvotes": 0,
    "votes": 6,
    "deleted": 0,
    "edited": 0,
    "bookmarks": 0,
    "quillDelta": "{\"ops\":[...]}",
    "user": { "uid": 243, "username": "ashlee_ellery" },
    "firstPost": true
}
```

**Replies location**: `response.posts[]` (index starts at 1, OP is excluded)

**Fields to extract → Conclave mapping**:
| NodeBB field | Conclave field | Transform |
|---|---|---|
| `topic.tid` | — | Keep as `nodebb_tid` for reference |
| `topic.title` / `titleRaw` | `discussion.title` | Direct |
| `topic.slug` | `discussion.slug` | Extract slug part after `{tid}/` |
| `topic.uid` | `discussion.user_id` | Map via uid→user_id lookup |
| `topic.tags[0].value` | `discussion.topic_id` | Map via tag→topic_id lookup |
| `topic.timestamp` / `timestampISO` | `discussion.created_at` | Parse ISO |
| `topic.lastposttime` / `lastposttimeISO` | `discussion.updated_at` | Parse ISO |
| `topic.viewcount` | views table entries | Create view records |
| `topic.pinned` | `discussion.is_pinned` | `0`→`false`, `1`→`true` |
| `topic.locked` | `discussion.is_locked` | `0`→`false`, `1`→`true` |
| `topic.deleted` | — | Skip deleted topics or soft-delete |
| `topic.votes` / `upvotes` | likes table | Create like records |
| `topic.country/state/location` | `discussion.location_id` | Map to Conclave locations if applicable |
| `topicpost.posts[0].content` | `discussion.body` | **HTML → Slate JSON** (see Section 4) |
| `topicpost.posts[0].quillDelta` | — | Alternative source for body conversion |
| `topic.cid` | — | Category (2=Default, 5=Featured); "Featured" topics could be pinned |

---

### Step 4: Scrape Posts (→ Replies)

Replies come bundled inside the topic detail response from Step 3.

**Location in response**: `response.posts[]`

**Reply structure**:

```json
{
  "pid": 270,
  "tid": 112,
  "uid": 246,
  "content": "<p>HTML content...</p>",
  "timestamp": 1650422410276,
  "timestampISO": "2022-04-20T02:40:10.276Z",
  "toPid": "159",
  "votes": 0,
  "upvotes": 0,
  "downvotes": 0,
  "deleted": 0,
  "edited": 0,
  "editedISO": "",
  "bookmarks": 0,
  "index": 1,
  "isThisAReply": false,
  "quillDelta": "{\"ops\":[...]}",
  "user": { "uid": 246, "username": "danica_sheean", "fullname": "Danica Sheean" },
  "parent": { "username": "ashlee_ellery", "displayname": "ashlee_ellery" },
  "replies": {
    "count": 2,
    "users": [...]
  },
  "responses": [
    {
      "pid": 471,
      "toPid": "452",
      "uid": 246,
      "content": "<p>Nested reply content...</p>",
      "timestampISO": "2022-04-26T17:54:41.397Z",
      "responses": [],
      "user": { "uid": 246, "username": "..." }
    }
  ]
}
```

**Fields to extract → Conclave mapping**:
| NodeBB field | Conclave field | Transform |
|---|---|---|
| `post.pid` | — | Keep as `nodebb_pid` for reference |
| `post.tid` | `reply.discussion_id` | Map via tid→discussion_id lookup |
| `post.uid` | `reply.user_id` | Map via uid→user_id lookup |
| `post.content` | `reply.body` | **HTML → Slate JSON** (see Section 4) |
| `post.timestamp` / `timestampISO` | `reply.created_at` | Parse ISO |
| `post.edited` / `editedISO` | `reply.updated_at` | Parse ISO if edited > 0 |
| `post.votes` / `upvotes` | likes table | Create like records |
| `post.deleted` | — | Skip or soft-delete |
| `post.toPid` | `reply.parent_id` | **Threading logic** (see below) |
| `post.responses[]` | Nested replies | Recursively extract (see below) |

### Reply Threading Logic

NodeBB uses `toPid` to reference the parent post:

```
toPid === mainPid  →  Top-level reply (depth 0) — reply.parent_id = NULL
toPid === other_pid →  Nested reply — reply.parent_id = mapped reply ID
```

**Conclave max depth = 2** (3 levels: depth 0, 1, 2). NodeBB allows unlimited nesting.

**Flattening strategy**:

1. `toPid === mainPid` → depth 0 (direct reply to discussion), `parent_id = NULL`
2. `toPid` points to a depth-0 reply → depth 1, `parent_id = that reply`
3. `toPid` points to a depth-1+ reply → **flatten to depth 2**, `parent_id = the depth-1 ancestor`
4. Nested replies also appear in `responses[]` array inline — extract these recursively

**Important**: Some replies in `responses[]` are duplicated in the main `posts[]` array. Deduplicate by `pid`.

---

## 3. Content Conversion: HTML → Slate JSON

NodeBB stores post content as HTML (rendered from Quill editor). Conclave uses Slate.js JSON.

### Source formats available

1. **`content`** — Rendered HTML string (primary source)
2. **`quillDelta`** — Quill Delta JSON (available on some posts, may be more reliable)

### HTML → Slate.js conversion

The HTML content uses these elements that need mapping to Slate blocks/marks:

| HTML element                       | Slate type                                            | Notes                                          |
| ---------------------------------- | ----------------------------------------------------- | ---------------------------------------------- |
| `<p>`                              | `{ type: 'paragraph' }`                               | Block                                          |
| `<strong>`, `<b>`                  | `{ bold: true }` mark                                 | Inline mark                                    |
| `<em>`, `<i>`                      | `{ italic: true }` mark                               | Inline mark                                    |
| `<u>`                              | `{ underline: true }` mark                            | Inline mark                                    |
| `<code>`                           | `{ code: true }` mark                                 | Inline mark                                    |
| `<h1>`, `<h2>`, `<h3>`             | `{ type: 'heading-one' }` etc.                        | Block                                          |
| `<ul>` / `<li>`                    | `{ type: 'bulleted-list' }` / `{ type: 'list-item' }` | Block                                          |
| `<ol>` / `<li>`                    | `{ type: 'numbered-list' }` / `{ type: 'list-item' }` | Block                                          |
| `<blockquote>`                     | `{ type: 'block-quote' }`                             | Block                                          |
| `<a href="...">`                   | `{ type: 'link', url: '...' }`                        | Inline                                         |
| `<img src="...">`                  | `{ type: 'image', url: '...' }`                       | Void block                                     |
| `<br>`                             | `\n` in text node                                     | Line break                                     |
| `<a class="plugin-mentions-user">` | `{ type: 'mention' }`                                 | Inline void (extract uid + username from href) |

### @Mention conversion

NodeBB mentions render as:

```html
<a
    class="plugin-mentions-user plugin-mentions-a"
    href="http://hub.greenwave.org/community/uid/243"
    >@ashlee_ellery</a
>
```

Convert to Conclave Slate mention:

```json
{
    "type": "mention",
    "userId": 243,
    "username": "ashlee_ellery",
    "children": [{ "text": "" }]
}
```

Note: `userId` needs to be remapped from NodeBB uid → Conclave user ID.

### Image handling

Images are hosted on `assets.hub.greenwave.org`. Options:

1. **Keep external URLs** — fastest, but depends on GreenWave keeping the old URLs alive
2. **Download and re-host** — download each image, upload to Conclave storage (`uploads/{user_id}/{Y}/{m}/{uuid}.{ext}`), update URLs in Slate JSON

Recommendation: **Download and re-host** for data independence.

---

## 4. Scraper Output Format

The scraper should produce JSON files matching Conclave's data model:

### `output/tags.json` (→ Conclave Topics)

```json
[
    {
        "nodebb_tag": "introductions + connections",
        "title": "Introductions + Connections",
        "slug": "introductions-connections",
        "description": "",
        "icon": "users",
        "visibility": "public",
        "sort_order": 1,
        "discussion_count": 367
    }
]
```

### `output/users.json` (→ Conclave Users)

```json
[
    {
        "nodebb_uid": 246,
        "username": "danica_sheean",
        "email": "danica.sheean@gmail.com",
        "name": "Danica Sheean",
        "first_name": "Danica",
        "last_name": "Sheean",
        "bio": "Business Development // Motion Picture Producing",
        "avatar_url": "https://assets.hub.greenwave.org/images/...",
        "avatar_local_path": null,
        "joined_at": "2022-04-19T19:03:54.613Z",
        "last_seen_at": "2022-06-01T00:36:09.853Z",
        "is_banned": false,
        "country": "",
        "state": ""
    }
]
```

### `output/discussions.json` (→ Conclave Discussions)

```json
[
    {
        "nodebb_tid": 112,
        "nodebb_tag": "introductions + connections",
        "title": "Hello from Oregon!",
        "slug": "hello-from-oregon",
        "nodebb_uid": 243,
        "body_html": "<p>I hadn't been so excited...</p>",
        "body_slate": [
            { "type": "paragraph", "children": [{ "text": "..." }] }
        ],
        "created_at": "2022-04-19T19:14:14.676Z",
        "is_pinned": false,
        "is_locked": false,
        "is_deleted": false,
        "view_count": 31,
        "like_count": 6,
        "reply_count": 4,
        "country": "",
        "state": "",
        "location": ""
    }
]
```

### `output/replies.json` (→ Conclave Replies)

```json
[
    {
        "nodebb_pid": 270,
        "nodebb_tid": 112,
        "nodebb_uid": 246,
        "nodebb_parent_pid": 159,
        "depth": 0,
        "body_html": "<p>@ashlee_ellery, hi!...</p>",
        "body_slate": [
            { "type": "paragraph", "children": [{ "text": "..." }] }
        ],
        "created_at": "2022-04-20T02:40:10.276Z",
        "updated_at": null,
        "is_deleted": false,
        "like_count": 0
    }
]
```

### `output/images.json` (image download manifest)

```json
[
    {
        "source_url": "https://assets.hub.greenwave.org/images/...",
        "local_path": "images/downloaded/...",
        "referenced_by": [{ "type": "discussion", "nodebb_tid": 112 }]
    }
]
```

---

## 5. Scraper Architecture

### Technology

- **Node.js / TypeScript** script (can run outside DDEV)
- Uses `fetch` with a cookie jar for authenticated requests
- Rate limiting: add 100–200ms delay between requests to avoid overloading NodeBB

### Execution flow

```
1. LOGIN
   → POST https://hub.greenwave.org/app (form submit)
   → Extract session cookies

2. SCRAPE TAGS (1 request)
   → GET /community/api/tags/
   → Save to output/tags.json

3. SCRAPE USERS (176 requests for list + ~1000 for active profiles)
   → GET /community/api/users?page=1..176
   → For users with postcount > 0: GET /community/api/user/{userslug}
   → Build uid → user mapping
   → Save to output/users.json

4. SCRAPE DISCUSSIONS + REPLIES (50 category pages + 996 topic details)
   → GET /community/api/category/2/default?page=1..50
   → GET /community/api/category/5/featured-content?page=1
   → Collect all tids
   → For each tid: GET /community/api/topic/{tid}/
     → Extract OP from topicpost.posts[0] → discussions.json
     → Extract replies from posts[] + responses[] → replies.json
     → Deduplicate replies by pid
     → Compute reply depth with flattening (max depth 2)
   → Save to output/discussions.json + output/replies.json

5. CONVERT CONTENT
   → Parse HTML content → Slate JSON for each discussion body and reply body
   → Extract image URLs → output/images.json
   → Extract @mention references → remap UIDs

6. DOWNLOAD IMAGES (optional, in parallel)
   → Download all referenced images to output/images/
   → Update image URLs in Slate JSON to local paths
```

### Estimated request count

| Step           | Requests   | Notes                      |
| -------------- | ---------- | -------------------------- |
| Login          | 1          |                            |
| Tags           | 1          |                            |
| User list      | 176        | 50 users/page              |
| User profiles  | ~1,000     | Only active users          |
| Category pages | 51         | 50 + 1 Featured            |
| Topic details  | 996        | One per topic              |
| **Total**      | **~2,225** | At 5 req/sec ≈ 7.5 minutes |

---

## 6. Laravel Seeder Strategy

After scraping, create a Laravel seeder (`database/seeders/NodeBBMigrationSeeder.php`) that:

1. **Reads the JSON files** from the scraper output
2. **Creates Topics** from `tags.json` (8 records)
3. **Creates Users** from `users.json` (8,798 records)
    - Generate random passwords (users will need password reset)
    - Download and store avatars
    - Map `nodebb_uid` → new `user.id`
4. **Creates Discussions** from `discussions.json` (996 records)
    - Map `nodebb_uid` → `user_id`
    - Map `nodebb_tag` → `topic_id`
    - Store Slate JSON body
    - Create view records for view counts
    - Create like records for upvotes
    - Map `nodebb_tid` → new `discussion.id`
5. **Creates Replies** from `replies.json` (~2,977 records)
    - Map `nodebb_uid` → `user_id`
    - Map `nodebb_tid` → `discussion_id`
    - Map `nodebb_parent_pid` → `parent_id` using pid mapping
    - Store Slate JSON body
    - Create like records for upvotes
6. **Handles timestamps** — set `created_at` and `updated_at` from original data

### ID Mapping Tables (in-memory during seeding)

```php
$uidMap = [];      // nodebb_uid → conclave user_id
$tidMap = [];      // nodebb_tid → conclave discussion_id
$pidMap = [];      // nodebb_pid → conclave reply_id
$tagMap = [];      // nodebb_tag_value → conclave topic_id
```

---

## 7. Edge Cases & Decisions Needed

1. **Username conflicts**: NodeBB allows usernames that don't match Conclave's `[a-z][a-z0-9_-]{4,15}` regex. Strategy: lowercase, truncate, generate fallbacks.

- Change Conclave username regex to allow uppercase letters and hyphens.

2. **Deleted content**: NodeBB has `deleted: 1` on some topics/posts. Decision: skip entirely, or import as soft-deleted?

- Skip entirely.

3. **Featured Content category**: The 5 topics in cid=5 also have tags. Import them normally (via their tag) and optionally pin them?

- Do not import the featured content category for now. Add a TODO to GitHub.

4. **User avatars**: Many users have the placeholder `placeholder-profile-picture.png`. Skip downloading placeholders, only download real uploads.

- Skip downloading placeholders, only download real uploads. Add a TODO to GitHub.

5. **Email privacy**: User emails are visible to the authenticated admin account. In Conclave, `show_email` defaults to `false` — keep that default.

- Keep `show_email` as `false` by default.

6. **Likes without user attribution**: NodeBB provides `upvotes` count but NOT which users upvoted. We can store the count but cannot create individual like records. Options:
    - Store the count directly on the model (add a `migrated_like_count` field or just set `likes_count`)
    - Skip like migration entirely

- Store the count on the as "likes_count". Somewhere in the NodeBB data there's a relationship representing likes, but we'll skip this for now.

7. **Reply depth flattening**: Some NodeBB threads may nest 3+ levels deep. Flatten to Conclave's max depth of 2.

- Flatten for now. Add a TODO to GitHub.

8. **Image re-hosting**: Download images from `assets.hub.greenwave.org` and store in Conclave's `public/storage/uploads/` structure? Or keep external URLs temporarily?

- Download images to a folder called `public/storage/uploads/migrated/` and update the Slate JSON to reference the local paths.

9. **@Mention UID remapping**: Mentions in HTML reference NodeBB UIDs. After user import, remap these to Conclave user IDs in the Slate JSON.

- Do this in the seeder.

10. **Quill Delta vs HTML**: Some posts have `quillDelta` field (Quill editor format). Could potentially convert Quill Delta → Slate JSON more accurately than HTML → Slate JSON. Worth investigating.

- If this is an easier convertion do it from the quillDelta.
