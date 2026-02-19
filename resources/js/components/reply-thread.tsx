import { useState } from 'react';
import type { Descendant } from 'slate';
import { ReplyCard } from '@/components/reply-card';
import { ReplyForm } from '@/components/reply-form';

export type ReplyUser = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    preferred_name: string | null;
    is_deleted: boolean;
    role: 'admin' | 'moderator' | 'user';
};

export type ReplyType = {
    id: number;
    discussion_id: number;
    user_id: number | null;
    parent_id: number | null;
    depth: number;
    body: Descendant[];
    created_at: string;
    updated_at: string;
    user: ReplyUser | null;
    children: ReplyType[];
};

interface ReplyThreadProps {
    replies: ReplyType[];
    discussionId: number;
    canReply: boolean;
    isLocked: boolean;
    authUserId: number | null;
    authUserRole: string | null;
}

const DEPTH_INDENT: Record<number, string> = {
    0: '',
    1: 'ml-8',
    2: 'ml-16',
};

export function ReplyThread({
    replies,
    discussionId,
    canReply,
    isLocked,
    authUserId,
    authUserRole,
}: ReplyThreadProps) {
    const [activeReplyId, setActiveReplyId] = useState<number | null>(null);

    const handleReplyClick = (replyId: number) => {
        setActiveReplyId((current) =>
            current === replyId ? null : replyId,
        );
    };

    const renderReply = (reply: ReplyType) => (
        <div key={reply.id} className={DEPTH_INDENT[reply.depth] ?? 'ml-16'}>
            <div className="border-b py-4">
                <ReplyCard
                    reply={reply}
                    discussionId={discussionId}
                    canReply={canReply}
                    isLocked={isLocked}
                    authUserId={authUserId}
                    authUserRole={authUserRole}
                    onReplyClick={handleReplyClick}
                    activeReplyId={activeReplyId}
                />

                {activeReplyId === reply.id && (
                    <div className="mt-3 ml-11">
                        <ReplyForm
                            discussionId={discussionId}
                            parentId={reply.id}
                            onCancel={() => setActiveReplyId(null)}
                            placeholder={`Reply to ${reply.user?.preferred_name ?? reply.user?.name ?? 'Deleted User'}...`}
                        />
                    </div>
                )}
            </div>

            {reply.children?.length > 0 &&
                reply.children.map((child) => renderReply(child))}
        </div>
    );

    return (
        <div className="space-y-0">
            {replies.map((reply) => renderReply(reply))}
        </div>
    );
}
