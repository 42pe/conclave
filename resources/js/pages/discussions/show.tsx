import { Head, Link, router, usePage } from '@inertiajs/react';
import { Lock, MapPin, MessageSquare, Pencil, Pin, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { SlateRenderer } from '@/components/slate-editor/renderer';
import { ReplyForm } from '@/components/reply-form';
import { ReplyThread } from '@/components/reply-thread';
import type { ReplyType } from '@/components/reply-thread';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Descendant } from 'slate';

type Topic = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    icon: string | null;
    visibility: 'public' | 'private' | 'restricted';
};

type User = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    preferred_name: string | null;
    is_deleted: boolean;
    bio: string | null;
    role: 'admin' | 'moderator' | 'user';
};

type Discussion = {
    id: number;
    title: string;
    slug: string;
    body: Descendant[];
    is_pinned: boolean;
    is_locked: boolean;
    reply_count: number;
    created_at: string;
    updated_at: string;
    user: User | null;
    location: {
        id: number;
        name: string;
    } | null;
};

type Props = {
    topic: Topic;
    discussion: Discussion;
    replies: ReplyType[];
    can: {
        update: boolean;
        delete: boolean;
        reply: boolean;
    };
};

function getUserDisplayName(user: Discussion['user']): string {
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

export default function DiscussionShow({ topic, discussion, replies, can }: Props) {
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const { auth } = usePage().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Forum', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
        {
            title: discussion.title,
            href: `/topics/${topic.slug}/discussions/${discussion.slug}`,
        },
    ];

    const displayName = getUserDisplayName(discussion.user);
    const initials = getUserInitials(discussion.user);

    const handleDelete = () => {
        router.delete(
            `/topics/${topic.slug}/discussions/${discussion.slug}`,
            {
                onFinish: () => setShowDeleteDialog(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${discussion.title} - ${topic.title}`} />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <div className="space-y-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                {discussion.is_pinned && (
                                    <Pin className="size-4 text-amber-500" />
                                )}
                                <h1 className="text-2xl font-bold tracking-tight">
                                    {discussion.title}
                                </h1>
                                {discussion.is_locked && (
                                    <Badge variant="secondary">Locked</Badge>
                                )}
                            </div>
                            <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                                <div className="flex items-center gap-2">
                                    <Avatar className="size-6">
                                        {discussion.user?.avatar_path && (
                                            <AvatarImage
                                                src={`/storage/${discussion.user.avatar_path}`}
                                                alt={displayName}
                                            />
                                        )}
                                        <AvatarFallback className="text-xs">
                                            {initials}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span>{displayName}</span>
                                    {discussion.user?.role &&
                                        discussion.user.role !== 'user' && (
                                            <Badge
                                                variant={
                                                    discussion.user.role ===
                                                    'admin'
                                                        ? 'destructive'
                                                        : 'secondary'
                                                }
                                                className="text-xs"
                                            >
                                                {discussion.user.role}
                                            </Badge>
                                        )}
                                </div>
                                <span>
                                    {new Date(
                                        discussion.created_at,
                                    ).toLocaleDateString(undefined, {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                </span>
                                {discussion.location && (
                                    <span className="flex items-center gap-1">
                                        <MapPin className="size-3.5" />
                                        {discussion.location.name}
                                    </span>
                                )}
                            </div>
                        </div>

                        {(can.update || can.delete) && (
                            <div className="flex shrink-0 items-center gap-1">
                                {can.update && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        asChild
                                    >
                                        <Link
                                            href={`/topics/${topic.slug}/discussions/${discussion.slug}/edit`}
                                        >
                                            <Pencil className="size-4" />
                                        </Link>
                                    </Button>
                                )}
                                {can.delete && (
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() =>
                                            setShowDeleteDialog(true)
                                        }
                                    >
                                        <Trash2 className="size-4 text-destructive" />
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="rounded-lg border p-6">
                        <SlateRenderer value={discussion.body} />
                    </div>
                </div>

                <Separator />

                <div className="space-y-4">
                    <div className="flex items-center gap-2">
                        <MessageSquare className="size-5" />
                        <h2 className="text-lg font-semibold">
                            Replies ({discussion.reply_count})
                        </h2>
                    </div>

                    {discussion.is_locked && (
                        <div className="flex items-center gap-2 rounded-lg border border-dashed p-4 text-sm text-muted-foreground">
                            <Lock className="size-4" />
                            This discussion is locked. New replies are not allowed.
                        </div>
                    )}

                    {replies.length > 0 ? (
                        <ReplyThread
                            replies={replies}
                            discussionId={discussion.id}
                            canReply={can.reply}
                            isLocked={discussion.is_locked}
                            authUserId={auth.user?.id ?? null}
                            authUserRole={auth.user?.role ?? null}
                        />
                    ) : (
                        <p className="py-4 text-center text-sm text-muted-foreground">
                            No replies yet. Be the first to respond.
                        </p>
                    )}

                    {can.reply && !discussion.is_locked && (
                        <div className="pt-4">
                            <h3 className="mb-3 text-sm font-medium">
                                Leave a reply
                            </h3>
                            <ReplyForm discussionId={discussion.id} />
                        </div>
                    )}
                </div>
            </div>

            <AlertDialog
                open={showDeleteDialog}
                onOpenChange={setShowDeleteDialog}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Discussion</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete &ldquo;
                            {discussion.title}&rdquo;? This action cannot be
                            undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
