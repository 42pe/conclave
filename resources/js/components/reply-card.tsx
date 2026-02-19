import { router, useForm } from '@inertiajs/react';
import { MessageSquare, Pencil, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import type { Descendant } from 'slate';
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
import { SlateEditor } from '@/components/slate-editor/editor';
import { SlateRenderer } from '@/components/slate-editor/renderer';
import type { ReplyType, ReplyUser } from '@/components/reply-thread';

function getUserDisplayName(user: ReplyUser | null): string {
    if (!user || user.is_deleted) return 'Deleted User';
    return user.preferred_name ?? user.name;
}

function getUserInitials(user: ReplyUser | null): string {
    const name = getUserDisplayName(user);
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

interface ReplyCardProps {
    reply: ReplyType;
    discussionId: number;
    canReply: boolean;
    isLocked: boolean;
    authUserId: number | null;
    authUserRole: string | null;
    onReplyClick: (replyId: number) => void;
    activeReplyId: number | null;
}

export function ReplyCard({
    reply,
    discussionId,
    canReply,
    isLocked,
    authUserId,
    authUserRole,
    onReplyClick,
    activeReplyId,
}: ReplyCardProps) {
    const [isEditing, setIsEditing] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);

    const editForm = useForm({
        body: reply.body as Descendant[],
    });

    const canEdit =
        authUserId !== null &&
        (authUserId === reply.user_id ||
            authUserRole === 'admin' ||
            authUserRole === 'moderator');

    const canDelete = canEdit;
    const canReplyToThis = canReply && !isLocked && reply.depth < 2;

    const displayName = getUserDisplayName(reply.user);
    const initials = getUserInitials(reply.user);

    const handleEdit = () => {
        editForm.patch(`/replies/${reply.id}`, {
            onSuccess: () => setIsEditing(false),
        });
    };

    const handleDelete = () => {
        router.delete(`/replies/${reply.id}`, {
            onFinish: () => setShowDeleteDialog(false),
        });
    };

    return (
        <div className="group">
            <div className="flex gap-3">
                <Avatar className="mt-1 size-8 shrink-0">
                    {reply.user?.avatar_path && (
                        <AvatarImage
                            src={`/storage/${reply.user.avatar_path}`}
                            alt={displayName}
                        />
                    )}
                    <AvatarFallback className="text-xs">
                        {initials}
                    </AvatarFallback>
                </Avatar>

                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <span className="text-sm font-medium">
                            {displayName}
                        </span>
                        {reply.user?.role &&
                            reply.user.role !== 'user' && (
                                <Badge
                                    variant={
                                        reply.user.role === 'admin'
                                            ? 'destructive'
                                            : 'secondary'
                                    }
                                    className="text-xs"
                                >
                                    {reply.user.role}
                                </Badge>
                            )}
                        <span className="text-xs text-muted-foreground">
                            {new Date(reply.created_at).toLocaleDateString(
                                undefined,
                                {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                },
                            )}
                        </span>
                    </div>

                    {isEditing ? (
                        <div className="mt-2 space-y-2">
                            <SlateEditor
                                initialValue={reply.body}
                                onChange={(value) =>
                                    editForm.setData('body', value)
                                }
                                placeholder="Edit your reply..."
                            />
                            <div className="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    onClick={handleEdit}
                                    disabled={editForm.processing}
                                >
                                    Save
                                </Button>
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onClick={() => setIsEditing(false)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="mt-1 text-sm">
                            <SlateRenderer value={reply.body} />
                        </div>
                    )}

                    {!isEditing && (
                        <div className="mt-1 flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                            {canReplyToThis && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 gap-1 px-2 text-xs"
                                    onClick={() => onReplyClick(reply.id)}
                                >
                                    {activeReplyId === reply.id ? (
                                        <>
                                            <X className="size-3" />
                                            Cancel
                                        </>
                                    ) : (
                                        <>
                                            <MessageSquare className="size-3" />
                                            Reply
                                        </>
                                    )}
                                </Button>
                            )}
                            {canEdit && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 gap-1 px-2 text-xs"
                                    onClick={() => setIsEditing(true)}
                                >
                                    <Pencil className="size-3" />
                                    Edit
                                </Button>
                            )}
                            {canDelete && (
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 gap-1 px-2 text-xs text-destructive"
                                    onClick={() => setShowDeleteDialog(true)}
                                >
                                    <Trash2 className="size-3" />
                                    Delete
                                </Button>
                            )}
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
                        <AlertDialogTitle>Delete Reply</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete this reply? This
                            action cannot be undone.
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
        </div>
    );
}
