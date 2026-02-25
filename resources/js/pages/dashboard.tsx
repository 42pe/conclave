import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Mail,
    MessageCircle,
    MessageSquare,
    PenLine,
    Plus,
} from 'lucide-react';
import { DynamicIcon } from '@/components/dynamic-icon';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';

type UserStats = {
    discussions_count: number;
    replies_count: number;
};

type RecentReply = {
    id: number;
    created_at: string;
    user: {
        id: number;
        name: string;
        username: string;
        display_name: string;
        avatar_path: string | null;
    };
    discussion: {
        id: number;
        title: string;
        slug: string;
        topic_id: number;
        topic: {
            id: number;
            title: string;
            slug: string;
            icon: string | null;
        };
    };
};

type ActiveTopic = {
    id: number;
    title: string;
    slug: string;
    icon: string | null;
    discussions_count: number;
};

type RecentDiscussion = {
    id: number;
    title: string;
    slug: string;
    updated_at: string;
    reply_count: number;
    user: {
        id: number;
        name: string;
        username: string;
        display_name: string;
    };
    topic: {
        id: number;
        title: string;
        slug: string;
        icon: string | null;
    };
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

function StatsSkeleton() {
    return (
        <div className="space-y-2">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-8 w-12" />
        </div>
    );
}

function ListSkeleton({ rows = 3 }: { rows?: number }) {
    return (
        <div className="space-y-3">
            {Array.from({ length: rows }).map((_, i) => (
                <div key={i} className="flex items-center gap-3">
                    <Skeleton className="size-8 rounded-full" />
                    <div className="flex-1 space-y-1.5">
                        <Skeleton className="h-4 w-3/4" />
                        <Skeleton className="h-3 w-1/2" />
                    </div>
                </div>
            ))}
        </div>
    );
}

export default function Dashboard({
    userStats,
    recentReplies,
    activeTopics,
    recentDiscussions,
}: {
    userStats: UserStats;
    recentReplies?: RecentReply[];
    activeTopics?: ActiveTopic[];
    recentDiscussions?: RecentDiscussion[];
}) {
    const { unread_messages_count } = usePage().props as { unread_messages_count: number };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="space-y-6 p-6">
                {/* Top row: Stats, Messages, Quick Actions */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Your Stats
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-2xl font-bold">
                                        {userStats.discussions_count}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Discussions
                                    </p>
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">
                                        {userStats.replies_count}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        Replies
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <Mail className="size-4" />
                                Unread Messages
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="mb-3 text-2xl font-bold">
                                {unread_messages_count}
                            </p>
                            <Button variant="outline" size="sm" asChild>
                                <Link href="/messages">
                                    View Messages
                                    <ArrowRight className="ml-1 size-3" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm font-medium">
                                Quick Actions
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col gap-2">
                                <Button variant="outline" size="sm" asChild>
                                    <Link href="/topics">
                                        <MessageSquare className="mr-2 size-3" />
                                        Browse Topics
                                    </Link>
                                </Button>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href="/messages">
                                        <PenLine className="mr-2 size-3" />
                                        New Message
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Bottom row: Recent Replies, Active Topics, Recent Discussions */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <MessageCircle className="size-4" />
                                Recent Replies to Your Discussions
                            </CardTitle>
                            <CardDescription>
                                Latest replies from other users
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Deferred
                                data="recentReplies"
                                fallback={<ListSkeleton />}
                            >
                                {recentReplies && recentReplies.length > 0 ? (
                                    <ul className="space-y-3">
                                        {recentReplies.map((reply) => (
                                            <li key={reply.id}>
                                                <Link
                                                    href={`/topics/${reply.discussion.topic.slug}/discussions/${reply.discussion.slug}`}
                                                    className="group flex items-start gap-2 rounded-md p-1 -mx-1 hover:bg-muted/50"
                                                >
                                                    <DynamicIcon
                                                        name={
                                                            reply.discussion
                                                                .topic.icon
                                                        }
                                                        className="mt-0.5 size-4 text-muted-foreground"
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium group-hover:underline">
                                                            {
                                                                reply.discussion
                                                                    .title
                                                            }
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            by{' '}
                                                            {
                                                                reply.user
                                                                    .display_name
                                                            }
                                                        </p>
                                                    </div>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No recent replies yet.
                                    </p>
                                )}
                            </Deferred>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <Plus className="size-4" />
                                Active Topics
                            </CardTitle>
                            <CardDescription>
                                Topics with recent activity
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Deferred
                                data="activeTopics"
                                fallback={<ListSkeleton />}
                            >
                                {activeTopics && activeTopics.length > 0 ? (
                                    <ul className="space-y-3">
                                        {activeTopics.map((topic) => (
                                            <li key={topic.id}>
                                                <Link
                                                    href={`/topics/${topic.slug}`}
                                                    className="group flex items-center gap-2 rounded-md p-1 -mx-1 hover:bg-muted/50"
                                                >
                                                    <DynamicIcon
                                                        name={topic.icon}
                                                        className="size-4 text-muted-foreground"
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium group-hover:underline">
                                                            {topic.title}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {
                                                                topic.discussions_count
                                                            }{' '}
                                                            {topic.discussions_count ===
                                                            1
                                                                ? 'discussion'
                                                                : 'discussions'}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No active topics.
                                    </p>
                                )}
                            </Deferred>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-sm font-medium">
                                <MessageSquare className="size-4" />
                                Recent Discussions
                            </CardTitle>
                            <CardDescription>
                                Recently updated discussions
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Deferred
                                data="recentDiscussions"
                                fallback={<ListSkeleton />}
                            >
                                {recentDiscussions &&
                                recentDiscussions.length > 0 ? (
                                    <ul className="space-y-3">
                                        {recentDiscussions.map((discussion) => (
                                            <li key={discussion.id}>
                                                <Link
                                                    href={`/topics/${discussion.topic.slug}/discussions/${discussion.slug}`}
                                                    className="group flex items-start gap-2 rounded-md p-1 -mx-1 hover:bg-muted/50"
                                                >
                                                    <DynamicIcon
                                                        name={
                                                            discussion.topic
                                                                .icon
                                                        }
                                                        className="mt-0.5 size-4 text-muted-foreground"
                                                    />
                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-medium group-hover:underline">
                                                            {discussion.title}
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            in{' '}
                                                            {
                                                                discussion.topic
                                                                    .title
                                                            }{' '}
                                                            &middot;{' '}
                                                            {
                                                                discussion.reply_count
                                                            }{' '}
                                                            {discussion.reply_count ===
                                                            1
                                                                ? 'reply'
                                                                : 'replies'}
                                                        </p>
                                                    </div>
                                                </Link>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="text-sm text-muted-foreground">
                                        No recent discussions.
                                    </p>
                                )}
                            </Deferred>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
