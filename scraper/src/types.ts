// ─── NodeBB API Response Types ───────────────────────────────────────────────

export interface NodeBBTag {
  value: string;
  score: number;
  valueEscaped: string;
  valueEncoded: string;
  class: string;
}

export interface NodeBBTagsResponse {
  tags: NodeBBTag[];
}

export interface NodeBBUserListItem {
  uid: number;
  username: string;
  userslug: string;
  picture: string | null;
  status: string;
  postcount: number;
  reputation: number;
  email: string;
  fullname: string;
  displayname: string;
  location: string;
  state: string;
  country: string;
  banned: boolean;
  lastonline: number;
  lastonlineISO: string;
}

export interface NodeBBUserProfile extends NodeBBUserListItem {
  aboutme: string;
  signature: string;
  website: string;
  birthday: string;
  uploadedpicture: string;
  joindate: number;
  joindateISO: string;
  profileviews: number;
  topiccount: number;
  followerCount: number;
  followingCount: number;
  city: string;
  headline: string;
  "cover:url": string;
  groupTitle: string;
  social_ig: string;
  social_fb: string;
  social_tw: string;
}

export interface NodeBBUsersResponse {
  users: NodeBBUserListItem[];
  pagination: {
    currentPage: number;
    pageCount: number;
  };
}

export interface NodeBBPost {
  pid: number;
  tid: number;
  uid: number;
  content: string;
  timestamp: number;
  timestampISO: string;
  toPid: string | number | null;
  votes: number;
  upvotes: number;
  downvotes: number;
  deleted: number;
  edited: number;
  editedISO: string;
  bookmarks: number;
  index: number;
  quillDelta?: string;
  user: {
    uid: number;
    username: string;
    fullname?: string;
    displayname?: string;
  };
  parent?: {
    username: string;
    displayname: string;
  };
  replies?: {
    count: number;
    users: unknown[];
  };
  responses?: NodeBBPost[];
}

export interface NodeBBTopicListItem {
  tid: number;
  title: string;
  titleRaw: string;
  slug: string;
  cid: number;
  uid: number;
  mainPid: number;
  postcount: number;
  viewcount: number;
  timestamp: number;
  timestampISO: string;
  lastposttime: number;
  lastposttimeISO: string;
  pinned: number;
  locked: number;
  deleted: number;
  upvotes: number;
  downvotes: number;
  votes: number;
  tags: { value: string }[];
  country: string;
  state: string;
  location: string;
  user: {
    uid: number;
    username: string;
    fullname?: string;
  };
  teaser?: {
    pid: number;
    uid: number;
    content: string;
    timestampISO: string;
  };
}

export interface NodeBBCategoryResponse {
  topics: NodeBBTopicListItem[];
  pagination: {
    currentPage: number;
    pageCount: number;
  };
}

export interface NodeBBTopicDetailResponse {
  tid: number;
  title: string;
  titleRaw: string;
  slug: string;
  cid: number;
  uid: number;
  mainPid: number;
  postcount: number;
  viewcount: number;
  timestamp: number;
  timestampISO: string;
  lastposttime: number;
  lastposttimeISO: string;
  pinned: number;
  locked: number;
  deleted: number;
  upvotes: number;
  downvotes: number;
  votes: number;
  tags: { value: string }[];
  country: string;
  state: string;
  location: string;
  topicpost: {
    posts: NodeBBPost[];
  };
  posts: NodeBBPost[];
}

// ─── Conclave Output Types ──────────────────────────────────────────────────

export interface ConclaveTopicOutput {
  nodebb_tag: string;
  title: string;
  slug: string;
  description: string;
  icon: string;
  visibility: string;
  sort_order: number;
  discussion_count: number;
}

export interface ConclaveUserOutput {
  nodebb_uid: number;
  username: string;
  email: string;
  name: string;
  first_name: string;
  last_name: string;
  bio: string;
  avatar_url: string | null;
  avatar_local_path: string | null;
  joined_at: string;
  last_seen_at: string;
  is_banned: boolean;
  country: string;
  state: string;
}

export interface ConclaveDiscussionOutput {
  nodebb_tid: number;
  nodebb_tag: string;
  title: string;
  slug: string;
  nodebb_uid: number;
  body_html: string;
  body_slate: SlateNode[];
  body_quill_delta: string | null;
  created_at: string;
  updated_at: string;
  is_pinned: boolean;
  is_locked: boolean;
  view_count: number;
  like_count: number;
  reply_count: number;
  country: string;
  state: string;
  location: string;
}

export interface ConclaveReplyOutput {
  nodebb_pid: number;
  nodebb_tid: number;
  nodebb_uid: number;
  nodebb_parent_pid: number | null;
  depth: number;
  body_html: string;
  body_slate: SlateNode[];
  body_quill_delta: string | null;
  created_at: string;
  updated_at: string | null;
  like_count: number;
}

export interface ImageManifestEntry {
  source_url: string;
  local_path: string | null;
  referenced_by: { type: "discussion" | "reply" | "avatar"; id: number }[];
}

// ─── Slate JSON Types ───────────────────────────────────────────────────────

export interface SlateTextNode {
  text: string;
  bold?: boolean;
  italic?: boolean;
  underline?: boolean;
  code?: boolean;
}

export interface SlateElementNode {
  type: string;
  children: SlateNode[];
  // Image/video/embed void elements
  src?: string;
  alt?: string;
  name?: string;
  // Link inline
  url?: string;
  // Mention inline void
  userId?: number;
  username?: string;
}

export type SlateNode = SlateTextNode | SlateElementNode;

// ─── Quill Delta Types ──────────────────────────────────────────────────────

export interface QuillDeltaOp {
  insert: string | { image?: string; video?: string; [key: string]: unknown };
  attributes?: {
    bold?: boolean;
    italic?: boolean;
    underline?: boolean;
    strike?: boolean;
    code?: boolean;
    link?: string;
    header?: 1 | 2 | 3;
    list?: "ordered" | "bullet";
    blockquote?: boolean;
    "code-block"?: boolean;
    mention?: {
      id: string | number;
      value: string;
      denotationChar: string;
    };
    [key: string]: unknown;
  };
}

export interface QuillDelta {
  ops: QuillDeltaOp[];
}

// ─── Scraper Config ─────────────────────────────────────────────────────────

export interface ScraperConfig {
  hubLoginUrl: string;
  communityBaseUrl: string;
  email: string;
  password: string;
  requestDelayMs: number;
  outputDir: string;
  imageOutputDir: string;
  only?: "tags" | "users" | "discussions" | "images";
}
