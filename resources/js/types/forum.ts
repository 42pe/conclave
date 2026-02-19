export type TopicVisibility = 'public' | 'private' | 'restricted';

export type Media = {
    id: number;
    url: string;
    original_name: string;
    mime_type: string;
    size: number;
};

export type Topic = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    icon: string | null;
    header_image_path: string | null;
    visibility: TopicVisibility;
    sort_order: number;
    created_by: number;
    creator?: { id: number; name: string };
    discussions_count?: number;
    created_at: string;
    updated_at: string;
};

export type Discussion = {
    id: number;
    topic_id: number;
    user_id: number | null;
    location_id: number | null;
    title: string;
    slug: string;
    body: unknown[];
    is_pinned: boolean;
    is_locked: boolean;
    reply_count: number;
    last_reply_at: string | null;
    user?: {
        id: number;
        name: string;
        username: string;
        avatar_path: string | null;
        is_deleted: boolean;
        display_name: string;
    };
    location?: { id: number; name: string } | null;
    topic?: Topic;
    created_at: string;
    updated_at: string;
};

export type Reply = {
    id: number;
    discussion_id: number;
    user_id: number | null;
    parent_id: number | null;
    depth: number;
    body: unknown[];
    user?: {
        id: number;
        name: string;
        username: string;
        avatar_path: string | null;
        is_deleted: boolean;
        display_name: string;
    };
    children?: Reply[];
    created_at: string;
    updated_at: string;
};

export type LocationItem = {
    id: number;
    name: string;
};

export type LocationType = 'any' | 'us_state' | 'country';

export type AdminLocation = {
    id: number;
    name: string;
    iso_code: string;
    type: LocationType;
    is_active: boolean;
    sort_order: number;
};

export type ConversationParticipant = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    is_deleted: boolean;
    display_name: string;
    pivot?: { last_read_at: string | null };
};

export type MessageItem = {
    id: number;
    conversation_id: number;
    user_id: number | null;
    body: unknown[];
    user?: {
        id: number;
        name: string;
        username: string;
        avatar_path: string | null;
        is_deleted: boolean;
        display_name: string;
    };
    created_at: string;
    updated_at: string;
};

export type ConversationItem = {
    id: number;
    participants: ConversationParticipant[];
    latest_message?: MessageItem | null;
    has_unread?: boolean;
    created_at: string;
    updated_at: string;
};
