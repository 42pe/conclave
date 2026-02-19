import { Head, Link, router, usePage } from '@inertiajs/react';
import { Lock, MapPin, Pencil, Pin, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { SlateRenderer } from '@/components/slate-editor';
import ReplyThread from '@/components/reply-thread';
import UserDisplay from '@/components/user-display';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Discussion, Reply, Topic } from '@/types';
import type { Descendant } from 'slate';

interface DiscussionShowProps {
    topic: Topic;
    discussion: Discussion;
    replies: Reply[];
    canEdit: boolean;
    canDelete: boolean;
    canReply: boolean;
}

function formatTimeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);
    if (days < 30) {
        return `${days}d ago`;
    }

    const months = Math.floor(days / 30);
    if (months < 12) {
        return `${months}mo ago`;
    }

    return `${Math.floor(months / 12)}y ago`;
}

export default function DiscussionShow({
    topic,
    discussion,
    replies,
    canEdit,
    canDelete,
    canReply,
}: DiscussionShowProps) {
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
        {
            title: discussion.title,
            href: `/topics/${topic.slug}/discussions/${discussion.slug}`,
        },
    ];

    function handleDelete() {
        router.delete(
            `/topics/${topic.slug}/discussions/${discussion.slug}`,
            {
                onFinish: () => setIsDeleteDialogOpen(false),
            },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={discussion.title} />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 lg:p-6">
                <div className="space-y-4">
                    <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0 space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                {discussion.is_pinned && (
                                    <Badge variant="default">
                                        <Pin className="mr-1 h-3 w-3" />
                                        Pinned
                                    </Badge>
                                )}
                                {discussion.is_locked && (
                                    <Badge variant="secondary">
                                        <Lock className="mr-1 h-3 w-3" />
                                        Locked
                                    </Badge>
                                )}
                                {discussion.location && (
                                    <Badge variant="outline">
                                        <MapPin className="mr-1 h-3 w-3" />
                                        {discussion.location.name}
                                    </Badge>
                                )}
                            </div>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {discussion.title}
                            </h1>
                        </div>

                        {(canEdit || canDelete) && (
                            <div className="flex shrink-0 items-center gap-1">
                                {canEdit && (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        asChild
                                    >
                                        <Link
                                            href={`/topics/${topic.slug}/discussions/${discussion.slug}/edit`}
                                        >
                                            <Pencil className="mr-1 h-4 w-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                )}

                                {canDelete && (
                                    <Dialog
                                        open={isDeleteDialogOpen}
                                        onOpenChange={setIsDeleteDialogOpen}
                                    >
                                        <DialogTrigger asChild>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Trash2 className="mr-1 h-4 w-4" />
                                                Delete
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>
                                                    Delete discussion
                                                </DialogTitle>
                                                <DialogDescription>
                                                    Are you sure you want to
                                                    delete &ldquo;
                                                    {discussion.title}&rdquo;?
                                                    This action cannot be
                                                    undone.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button variant="outline">
                                                        Cancel
                                                    </Button>
                                                </DialogClose>
                                                <Button
                                                    variant="destructive"
                                                    onClick={handleDelete}
                                                >
                                                    Delete
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                )}
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-3 text-sm text-muted-foreground">
                        {discussion.user && (
                            <UserDisplay user={discussion.user} size="sm" />
                        )}
                        <span>&middot;</span>
                        <span>{formatTimeAgo(discussion.created_at)}</span>
                        {discussion.updated_at !== discussion.created_at && (
                            <>
                                <span>&middot;</span>
                                <span>
                                    edited{' '}
                                    {formatTimeAgo(discussion.updated_at)}
                                </span>
                            </>
                        )}
                    </div>
                </div>

                <Separator />

                <div className="prose dark:prose-invert max-w-none">
                    <SlateRenderer
                        value={discussion.body as Descendant[]}
                    />
                </div>

                <Separator />

                <ReplyThread
                    replies={replies}
                    discussionId={discussion.id}
                    discussionLocked={discussion.is_locked}
                    canReply={canReply}
                />
            </div>
        </AppLayout>
    );
}
