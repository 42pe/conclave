import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DiscussionCard } from '@/components/discussion-card';
import { TopicHeader } from '@/components/topic-header';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Location = {
    id: number;
    name: string;
};

type User = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    preferred_name: string | null;
    is_deleted: boolean;
};

type Discussion = {
    id: number;
    title: string;
    slug: string;
    is_pinned: boolean;
    is_locked: boolean;
    reply_count: number;
    view_count: number;
    likes_count: number;
    user_has_liked: boolean;
    user_has_bookmarked: boolean;
    last_reply_at: string | null;
    created_at: string;
    user: User | null;
    location: Location | null;
};

type PaginatedDiscussions = {
    data: Discussion[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
};

type Topic = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    icon: string | null;
    header_image_path: string | null;
    visibility: 'public' | 'private' | 'restricted';
};

type Props = {
    topic: Topic;
    discussions: PaginatedDiscussions;
    locations: Location[];
    can: {
        create: boolean;
    };
    authUserId: number | null;
};

export default function TopicShow({
    topic,
    discussions,
    locations,
    can,
    authUserId,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Forum', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
    ];

    const handleLocationFilter = (value: string) => {
        if (value === 'all') {
            router.get(`/topics/${topic.slug}`, {}, { preserveState: true });
        } else {
            router.get(
                `/topics/${topic.slug}`,
                { location: value },
                { preserveState: true },
            );
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={topic.title} />

            <div className="space-y-6 p-6">
                <div className="flex items-start justify-between gap-4">
                    <TopicHeader topic={topic} />

                    {can.create && (
                        <Button asChild>
                            <Link
                                href={`/topics/${topic.slug}/discussions/create`}
                            >
                                <Plus className="mr-2 size-4" />
                                New Discussion
                            </Link>
                        </Button>
                    )}
                </div>

                {locations.length > 0 && (
                    <div className="flex items-center gap-2">
                        <Select
                            defaultValue="all"
                            onValueChange={handleLocationFilter}
                        >
                            <SelectTrigger className="w-[200px]">
                                <SelectValue placeholder="Filter by location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All Locations
                                </SelectItem>
                                {locations.map((location) => (
                                    <SelectItem
                                        key={location.id}
                                        value={String(location.id)}
                                    >
                                        {location.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                )}

                <div className="space-y-2">
                    {discussions.data.length === 0 ? (
                        <div className="rounded-lg border py-12 text-center text-muted-foreground">
                            No discussions yet.
                            {can.create && ' Be the first to start one!'}
                        </div>
                    ) : (
                        discussions.data.map((discussion) => (
                            <DiscussionCard
                                key={discussion.id}
                                discussion={discussion}
                                topicSlug={topic.slug}
                                authUserId={authUserId}
                            />
                        ))
                    )}
                </div>

                {discussions.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {discussions.prev_page_url && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={discussions.prev_page_url}>
                                    Previous
                                </Link>
                            </Button>
                        )}
                        <span className="text-sm text-muted-foreground">
                            Page {discussions.current_page} of{' '}
                            {discussions.last_page}
                        </span>
                        {discussions.next_page_url && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={discussions.next_page_url}>
                                    Next
                                </Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
