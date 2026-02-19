import { Link, usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import type { Auth } from '@/types';
import type { Descendant } from 'slate';

type ConversationUser = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    preferred_name: string | null;
    is_deleted: boolean;
    display_name: string;
};

type LatestMessage = {
    id: number;
    body: Descendant[];
    created_at: string;
    user: {
        id: number;
        name: string;
        username: string;
        preferred_name: string | null;
        is_deleted: boolean;
        display_name: string;
    } | null;
};

export type ConversationType = {
    id: number;
    users: ConversationUser[];
    latest_message: LatestMessage | null;
    unread_count: number;
    created_at: string;
};

function getOtherUser(conversation: ConversationType, currentUserId: number): ConversationUser | undefined {
    return conversation.users.find((u) => u.id !== currentUserId);
}

function getDisplayName(user: ConversationUser | undefined): string {
    if (!user || user.is_deleted) return 'Deleted User';
    return user.display_name ?? user.preferred_name ?? user.name;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function getMessagePreview(message: LatestMessage | null): string {
    if (!message?.body?.length) return 'No messages yet';

    const firstBlock = message.body[0];
    if (typeof firstBlock === 'object' && 'children' in firstBlock) {
        const children = (firstBlock as { children: Array<{ text?: string }> }).children;
        const text = children.map((c) => c.text ?? '').join('');
        if (text.length > 80) return text.slice(0, 80) + '...';
        return text || 'Sent a message';
    }
    return 'Sent a message';
}

function formatRelativeTime(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString();
}

export function ConversationCard({ conversation }: { conversation: ConversationType }) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const otherUser = getOtherUser(conversation, auth.user.id);
    const displayName = getDisplayName(otherUser);
    const initials = getInitials(displayName);
    const hasUnread = conversation.unread_count > 0;

    return (
        <Link
            href={`/messages/${conversation.id}`}
            className={`flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/50 ${hasUnread ? 'border-primary/30 bg-primary/5' : ''}`}
            prefetch
        >
            <Avatar className="size-10 shrink-0">
                {otherUser?.avatar_path && (
                    <AvatarImage
                        src={`/storage/${otherUser.avatar_path}`}
                        alt={displayName}
                    />
                )}
                <AvatarFallback>{initials}</AvatarFallback>
            </Avatar>

            <div className="min-w-0 flex-1">
                <div className="flex items-center justify-between gap-2">
                    <span className={`truncate text-sm ${hasUnread ? 'font-semibold' : 'font-medium'}`}>
                        {displayName}
                    </span>
                    {conversation.latest_message && (
                        <span className="shrink-0 text-xs text-muted-foreground">
                            {formatRelativeTime(conversation.latest_message.created_at)}
                        </span>
                    )}
                </div>
                <div className="flex items-center justify-between gap-2">
                    <p className={`truncate text-sm ${hasUnread ? 'text-foreground' : 'text-muted-foreground'}`}>
                        {getMessagePreview(conversation.latest_message)}
                    </p>
                    {hasUnread && (
                        <Badge variant="default" className="shrink-0 text-xs">
                            {conversation.unread_count}
                        </Badge>
                    )}
                </div>
            </div>
        </Link>
    );
}
