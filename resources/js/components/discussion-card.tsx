import { Link } from '@inertiajs/react';
import { MapPin, MessageSquare, Pin } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';

type Discussion = {
    id: number;
    title: string;
    slug: string;
    is_pinned: boolean;
    is_locked: boolean;
    reply_count: number;
    last_reply_at: string | null;
    created_at: string;
    user: {
        id: number;
        name: string;
        username: string;
        avatar_path: string | null;
        preferred_name: string | null;
        is_deleted: boolean;
    } | null;
    location: {
        id: number;
        name: string;
    } | null;
};

function formatTimeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    if (days < 30) return `${days}d ago`;

    return date.toLocaleDateString();
}

function getUserDisplayName(
    user: Discussion['user'],
): string {
    if (!user || user.is_deleted) return 'Deleted User';
    return user.preferred_name ?? user.name;
}

function getUserInitials(user: Discussion['user']): string {
    const name = getUserDisplayName(user);
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export function DiscussionCard({
    discussion,
    topicSlug,
}: {
    discussion: Discussion;
    topicSlug: string;
}) {
    const displayName = getUserDisplayName(discussion.user);
    const initials = getUserInitials(discussion.user);
    const activityTime = discussion.last_reply_at ?? discussion.created_at;

    return (
        <Link
            href={`/topics/${topicSlug}/discussions/${discussion.slug}`}
            className="flex items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-accent/50"
        >
            <Avatar className="size-10 shrink-0">
                {discussion.user?.avatar_path && (
                    <AvatarImage
                        src={`/storage/${discussion.user.avatar_path}`}
                        alt={displayName}
                    />
                )}
                <AvatarFallback>{initials}</AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1">
                <div className="flex items-center gap-2">
                    {discussion.is_pinned && (
                        <Pin className="size-3.5 shrink-0 text-amber-500" />
                    )}
                    <h3 className="truncate font-medium">{discussion.title}</h3>
                </div>
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                    <span>{displayName}</span>
                    <span>{formatTimeAgo(activityTime)}</span>
                    {discussion.location && (
                        <span className="flex items-center gap-1">
                            <MapPin className="size-3" />
                            {discussion.location.name}
                        </span>
                    )}
                </div>
            </div>

            <div className="flex shrink-0 items-center gap-1.5 text-sm text-muted-foreground">
                <MessageSquare className="size-4" />
                <span>{discussion.reply_count}</span>
            </div>
        </Link>
    );
}
