import { Link } from '@inertiajs/react';
import { MessageSquareOff } from 'lucide-react';
import ReplyCard from '@/components/reply-card';
import ReplyForm from '@/components/reply-form';
import { Separator } from '@/components/ui/separator';
import type { Reply } from '@/types';

interface ReplyThreadProps {
    replies: Reply[];
    discussionId: number;
    discussionLocked: boolean;
    canReply: boolean;
}

export default function ReplyThread({
    replies,
    discussionId,
    discussionLocked,
    canReply,
}: ReplyThreadProps) {
    return (
        <div className="space-y-6">
            <h2 className="text-lg font-semibold">
                {replies.length === 0
                    ? 'No replies yet'
                    : `${replies.length} ${replies.length === 1 ? 'reply' : 'replies'}`}
            </h2>

            {replies.length === 0 && !canReply && (
                <div className="flex flex-col items-center gap-2 py-8 text-muted-foreground">
                    <MessageSquareOff className="h-8 w-8" />
                    <p>No replies yet.</p>
                </div>
            )}

            {replies.map((reply, index) => (
                <div key={reply.id}>
                    {index > 0 && <Separator className="mb-4" />}
                    <ReplyCard
                        reply={reply}
                        discussionId={discussionId}
                        discussionLocked={discussionLocked}
                        canReply={canReply}
                    />
                </div>
            ))}

            {canReply && (
                <>
                    <Separator />
                    <div>
                        <h3 className="mb-3 text-sm font-medium">
                            Leave a reply
                        </h3>
                        <ReplyForm discussionId={discussionId} />
                    </div>
                </>
            )}

            {!canReply && discussionLocked && (
                <p className="text-sm text-muted-foreground italic">
                    This discussion is locked. No new replies can be posted.
                </p>
            )}

            {!canReply && !discussionLocked && (
                <p className="text-sm text-muted-foreground">
                    <Link
                        href="/login"
                        className="text-foreground underline hover:no-underline"
                    >
                        Log in
                    </Link>{' '}
                    to join the conversation.
                </p>
            )}
        </div>
    );
}
