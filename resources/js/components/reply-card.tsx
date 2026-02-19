import { router, usePage } from '@inertiajs/react';
import { MessageSquare, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { Descendant } from 'slate';
import { SlateRenderer } from '@/components/slate-editor';
import ReplyForm from '@/components/reply-form';
import UserDisplay from '@/components/user-display';
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
import type { Reply } from '@/types';

interface ReplyCardProps {
    reply: Reply;
    discussionId: number;
    discussionLocked: boolean;
    canReply: boolean;
}

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
    const months = Math.floor(days / 30);
    if (months < 12) return `${months}mo ago`;
    return `${Math.floor(months / 12)}y ago`;
}

export default function ReplyCard({
    reply,
    discussionId,
    discussionLocked,
    canReply,
}: ReplyCardProps) {
    const { auth } = usePage().props as unknown as {
        auth: { user: { id: number; role: string } | null };
    };
    const [showReplyForm, setShowReplyForm] = useState(false);
    const [showEditForm, setShowEditForm] = useState(false);
    const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);

    const user = auth?.user;
    const isOwner = user && reply.user_id === user.id;
    const isAdminOrMod =
        user && (user.role === 'admin' || user.role === 'moderator');
    const canEdit = isOwner || isAdminOrMod;
    const canDelete = isOwner || isAdminOrMod;
    const canNest = canReply && reply.depth < 2;

    function handleDelete() {
        router.delete(`/replies/${reply.id}`, {
            preserveScroll: true,
            onFinish: () => setIsDeleteDialogOpen(false),
        });
    }

    return (
        <div className="group">
            <div className="space-y-2">
                <div className="flex items-center gap-2 text-sm text-muted-foreground">
                    {reply.user && (
                        <UserDisplay user={reply.user} size="sm" />
                    )}
                    <span>&middot;</span>
                    <span>{formatTimeAgo(reply.created_at)}</span>
                    {reply.updated_at !== reply.created_at && (
                        <>
                            <span>&middot;</span>
                            <span className="italic">edited</span>
                        </>
                    )}
                </div>

                {showEditForm ? (
                    <ReplyForm
                        discussionId={discussionId}
                        replyId={reply.id}
                        initialBody={reply.body as never[]}
                        onCancel={() => setShowEditForm(false)}
                    />
                ) : (
                    <div className="prose dark:prose-invert prose-sm max-w-none">
                        <SlateRenderer
                            value={reply.body as Descendant[]}
                        />
                    </div>
                )}

                <div className="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                    {canNest && !discussionLocked && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 text-xs"
                            onClick={() => setShowReplyForm(!showReplyForm)}
                        >
                            <MessageSquare className="mr-1 h-3 w-3" />
                            Reply
                        </Button>
                    )}
                    {canEdit && (
                        <Button
                            variant="ghost"
                            size="sm"
                            className="h-7 text-xs"
                            onClick={() => setShowEditForm(!showEditForm)}
                        >
                            <Pencil className="mr-1 h-3 w-3" />
                            Edit
                        </Button>
                    )}
                    {canDelete && (
                        <Dialog
                            open={isDeleteDialogOpen}
                            onOpenChange={setIsDeleteDialogOpen}
                        >
                            <DialogTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 text-xs"
                                >
                                    <Trash2 className="mr-1 h-3 w-3" />
                                    Delete
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete reply</DialogTitle>
                                    <DialogDescription>
                                        Are you sure you want to delete this
                                        reply? This action cannot be undone.
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
            </div>

            {showReplyForm && (
                <div className="mt-3 ml-4">
                    <ReplyForm
                        discussionId={discussionId}
                        parentId={reply.id}
                        onCancel={() => setShowReplyForm(false)}
                    />
                </div>
            )}

            {reply.children && reply.children.length > 0 && (
                <div className="mt-4 space-y-4 border-l-2 border-muted pl-4">
                    {reply.children.map((child) => (
                        <ReplyCard
                            key={child.id}
                            reply={child}
                            discussionId={discussionId}
                            discussionLocked={discussionLocked}
                            canReply={canReply}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}
