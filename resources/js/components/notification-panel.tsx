import { router } from '@inertiajs/react';
import { AtSign, Bell, Bookmark, CheckCheck, MessageCircle, MessageSquare } from 'lucide-react';
import { useCallback, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { SidebarMenuBadge, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';

type NotificationData = {
    type: 'new_reply' | 'new_message' | 'mention' | 'bookmark_activity';
    discussion_id?: number;
    discussion_title?: string;
    discussion_slug?: string;
    topic_id?: number;
    topic_slug?: string;
    reply_id?: number;
    replier_name?: string;
    replier_username?: string;
    replier_avatar?: string | null;
    conversation_id?: number;
    message_id?: number;
    sender_name?: string;
    sender_username?: string;
    sender_avatar?: string | null;
    mentioner_name?: string;
    mentioner_username?: string;
    mentioner_avatar?: string | null;
};

type AppNotification = {
    id: string;
    type: string;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
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
    if (days < 7) return `${days}d ago`;
    return date.toLocaleDateString();
}

function getNotificationIcon(type: string) {
    switch (type) {
        case 'new_reply':
            return <MessageCircle className="size-4 shrink-0" />;
        case 'new_message':
            return <MessageSquare className="size-4 shrink-0" />;
        case 'mention':
            return <AtSign className="size-4 shrink-0" />;
        case 'bookmark_activity':
            return <Bookmark className="size-4 shrink-0" />;
        default:
            return <Bell className="size-4 shrink-0" />;
    }
}

function getNotificationText(data: NotificationData): string {
    switch (data.type) {
        case 'new_reply':
            return `${data.replier_name ?? 'Someone'} replied in "${data.discussion_title}"`;
        case 'new_message':
            return `${data.sender_name ?? 'Someone'} sent you a message`;
        case 'mention':
            return `${data.mentioner_name ?? 'Someone'} mentioned you in "${data.discussion_title}"`;
        case 'bookmark_activity':
            return `New activity in "${data.discussion_title}"`;
        default:
            return 'New notification';
    }
}

function getNotificationUrl(data: NotificationData): string {
    switch (data.type) {
        case 'new_reply':
        case 'mention':
        case 'bookmark_activity':
            if (data.topic_slug && data.discussion_slug) {
                return `/topics/${data.topic_slug}/discussions/${data.discussion_slug}`;
            }
            return '/';
        case 'new_message':
            if (data.conversation_id) {
                return `/messages/${data.conversation_id}`;
            }
            return '/messages';
        default:
            return '/';
    }
}

export function NotificationPanel({ unreadCount }: { unreadCount: number }) {
    const [open, setOpen] = useState(false);
    const [notifications, setNotifications] = useState<AppNotification[]>([]);
    const [loading, setLoading] = useState(false);

    const fetchNotifications = useCallback(() => {
        setLoading(true);
        fetch('/notifications', {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((res) => res.json())
            .then((data: AppNotification[]) => {
                setNotifications(data);
                setLoading(false);
            })
            .catch(() => {
                setLoading(false);
            });
    }, []);

    function handleOpenChange(isOpen: boolean) {
        setOpen(isOpen);
        if (isOpen) {
            fetchNotifications();
        }
    }

    function handleNotificationClick(notification: AppNotification) {
        const url = getNotificationUrl(notification.data);

        if (!notification.read_at) {
            fetch(`/notifications/${notification.id}/read`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
            });
        }

        setOpen(false);
        router.visit(url);
    }

    function handleMarkAllAsRead() {
        fetch('/notifications/mark-all-read', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            },
        }).then(() => {
            setNotifications((prev) => prev.map((n) => ({ ...n, read_at: n.read_at ?? new Date().toISOString() })));
            router.reload({ only: ['unread_notifications_count'] });
        });
    }

    const hasUnread = notifications.some((n) => !n.read_at);

    return (
        <SidebarMenuItem>
            <Sheet open={open} onOpenChange={handleOpenChange}>
                <SheetTrigger asChild>
                    <SidebarMenuButton tooltip={{ children: 'Notifications' }}>
                        <Bell />
                        <span>Notifications</span>
                    </SidebarMenuButton>
                </SheetTrigger>
                <SheetContent className="flex flex-col">
                    <SheetHeader>
                        <div className="flex items-center justify-between">
                            <SheetTitle>Notifications</SheetTitle>
                            {hasUnread && (
                                <Button variant="ghost" size="sm" className="h-auto gap-1 px-2 py-1 text-xs" onClick={handleMarkAllAsRead}>
                                    <CheckCheck className="size-3.5" />
                                    Mark all read
                                </Button>
                            )}
                        </div>
                        <SheetDescription className="sr-only">Your recent notifications</SheetDescription>
                    </SheetHeader>

                    <div className="-mx-4 flex-1 overflow-y-auto">
                        {loading && (
                            <div className="flex items-center justify-center py-8">
                                <div className="size-6 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                            </div>
                        )}

                        {!loading && notifications.length === 0 && (
                            <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                                <Bell className="mb-2 size-8 opacity-50" />
                                <p className="text-sm">No notifications yet</p>
                            </div>
                        )}

                        {!loading &&
                            notifications.map((notification) => (
                                <button
                                    key={notification.id}
                                    type="button"
                                    className={`flex w-full items-start gap-3 border-b px-4 py-3 text-left transition-colors hover:bg-accent ${
                                        !notification.read_at ? 'bg-accent/50' : ''
                                    }`}
                                    onClick={() => handleNotificationClick(notification)}
                                >
                                    <div className="mt-0.5 flex size-8 items-center justify-center rounded-full bg-muted">
                                        {getNotificationIcon(notification.data.type)}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className={`text-sm leading-snug ${!notification.read_at ? 'font-medium' : 'text-muted-foreground'}`}>
                                            {getNotificationText(notification.data)}
                                        </p>
                                        <p className="mt-0.5 text-xs text-muted-foreground">{formatTimeAgo(notification.created_at)}</p>
                                    </div>
                                    {!notification.read_at && <div className="mt-2 size-2 shrink-0 rounded-full bg-primary" />}
                                </button>
                            ))}
                    </div>
                </SheetContent>
            </Sheet>
            {unreadCount > 0 && <SidebarMenuBadge>{unreadCount}</SidebarMenuBadge>}
        </SidebarMenuItem>
    );
}
