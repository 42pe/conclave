import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { SlateRenderer } from '@/components/slate-editor/renderer';
import type { Descendant } from 'slate';

type MessageUser = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    preferred_name: string | null;
    is_deleted: boolean;
    display_name?: string;
};

export type MessageType = {
    id: number;
    body: Descendant[];
    created_at: string;
    user: MessageUser | null;
};

function getDisplayName(user: MessageUser | null): string {
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

interface MessageBubbleProps {
    message: MessageType;
    isOwn: boolean;
}

export function MessageBubble({ message, isOwn }: MessageBubbleProps) {
    const displayName = getDisplayName(message.user);
    const initials = getInitials(displayName);

    return (
        <div className={`flex gap-3 ${isOwn ? 'flex-row-reverse' : ''}`}>
            <Avatar className="size-8 shrink-0">
                {message.user?.avatar_path && (
                    <AvatarImage
                        src={`/storage/${message.user.avatar_path}`}
                        alt={displayName}
                    />
                )}
                <AvatarFallback className="text-xs">{initials}</AvatarFallback>
            </Avatar>

            <div className={`max-w-[75%] space-y-1 ${isOwn ? 'items-end' : ''}`}>
                <div className="flex items-center gap-2">
                    <span className="text-xs font-medium">{displayName}</span>
                    <span className="text-xs text-muted-foreground">
                        {new Date(message.created_at).toLocaleTimeString(undefined, {
                            hour: '2-digit',
                            minute: '2-digit',
                        })}
                    </span>
                </div>
                <div className={`rounded-lg px-3 py-2 ${isOwn ? 'bg-primary/10' : 'bg-muted'}`}>
                    <SlateRenderer value={message.body} />
                </div>
            </div>
        </div>
    );
}
