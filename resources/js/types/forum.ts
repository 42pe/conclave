export type TopicVisibility = 'public' | 'private' | 'restricted';

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
