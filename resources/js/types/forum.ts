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
    created_at: string;
    updated_at: string;
};
