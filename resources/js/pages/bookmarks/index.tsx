import { Head, Link } from '@inertiajs/react';
import { Bookmark, MessageSquare } from 'lucide-react';
import { DynamicIcon } from '@/components/dynamic-icon';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Topic = {
    id: number;
    title: string;
    slug: string;
    icon: string | null;
};

type Discussion = {
    id: number;
    title: string;
    slug: string;
    topic_id: number;
    reply_count: number;
    last_reply_at: string | null;
    created_at: string;
    topic: Topic | null;
};

type BookmarkItem = {
    id: number;
    discussion_id: number;
    created_at: string;
    discussion: Discussion | null;
};

type PaginatedBookmarks = {
    data: BookmarkItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
};

type Props = {
    bookmarks: PaginatedBookmarks;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bookmarks', href: '/bookmarks' },
];

function formatTimeAgo(dateString: string | null): string {
    if (!dateString) return 'No activity';
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
}

export default function BookmarksIndex({ bookmarks }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Bookmarks" />

            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <div className="flex items-center gap-2">
                    <Bookmark className="size-5" />
                    <h1 className="text-xl font-semibold">Bookmarks</h1>
                </div>

                {bookmarks.data.length > 0 ? (
                    <div className="space-y-2">
                        {bookmarks.data.map((bookmark) => {
                            if (!bookmark.discussion) return null;
                            const discussion = bookmark.discussion;
                            const topic = discussion.topic;
                            const discussionUrl = topic
                                ? `/topics/${topic.slug}/discussions/${discussion.slug}`
                                : '/';

                            return (
                                <Link
                                    key={bookmark.id}
                                    href={discussionUrl}
                                    className="flex items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-accent"
                                >
                                    <div className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                        <DynamicIcon name={topic?.icon} className="size-5 text-muted-foreground" />
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate font-medium">{discussion.title}</p>
                                        <div className="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                            {topic && <span>{topic.title}</span>}
                                            <span className="flex items-center gap-1">
                                                <MessageSquare className="size-3" />
                                                {discussion.reply_count}
                                            </span>
                                            <span>
                                                {formatTimeAgo(discussion.last_reply_at ?? discussion.created_at)}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}

                        {bookmarks.last_page > 1 && (
                            <div className="flex justify-center gap-1 pt-4">
                                {bookmarks.links.map((link, i) => (
                                    <Link
                                        key={i}
                                        href={link.url ?? '#'}
                                        className={`rounded px-3 py-1 text-sm ${
                                            link.active
                                                ? 'bg-primary text-primary-foreground'
                                                : link.url
                                                  ? 'hover:bg-accent'
                                                  : 'cursor-default text-muted-foreground'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        preserveScroll
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                ) : (
                    <div className="py-12 text-center">
                        <Bookmark className="mx-auto mb-4 size-12 text-muted-foreground/50" />
                        <h2 className="text-lg font-medium">No bookmarked discussions yet</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Bookmark discussions to follow their activity.
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
