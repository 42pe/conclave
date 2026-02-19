import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useEffect, useRef, useState } from 'react';
import type { Descendant } from 'slate';
import { SlateEditor, SlateRenderer, EMPTY_DOCUMENT } from '@/components/slate-editor';
import UserDisplay from '@/components/user-display';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type {
    BreadcrumbItem,
    ConversationItem,
    ConversationParticipant,
    MessageItem,
} from '@/types';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface MessageShowProps {
    conversation: ConversationItem;
    messages: {
        data: MessageItem[];
        links: PaginationLink[];
    };
}

type SlateNode = Record<string, string | boolean | number | null | SlateNode[]>;

function formatTime(dateString: string): string {
    return new Date(dateString).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

export default function MessageShow({
    conversation,
    messages,
}: MessageShowProps) {
    const { auth } = usePage().props;
    const currentUserId = (auth.user as { id: number }).id;
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const [editorKey, setEditorKey] = useState(0);

    const otherParticipant = conversation.participants.find(
        (p) => p.id !== currentUserId,
    );

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
        { title: 'Messages', href: '/messages' },
        {
            title: otherParticipant?.display_name ?? 'Conversation',
            href: `/conversations/${conversation.id}`,
        },
    ];

    const { data, setData, post, processing, reset } = useForm({
        conversation_id: conversation.id,
        body: EMPTY_DOCUMENT as unknown as SlateNode[],
    });

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages.data.length]);

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/messages', {
            preserveScroll: true,
            onSuccess: () => {
                reset('body');
                setEditorKey((k) => k + 1);
            },
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head
                title={`Message - ${otherParticipant?.display_name ?? 'Conversation'}`}
            />

            <div className="mx-auto flex h-full w-full max-w-3xl flex-col p-4 lg:p-6">
                <div className="mb-4 flex items-center gap-3">
                    {otherParticipant && (
                        <UserDisplay user={otherParticipant} size="default" />
                    )}
                </div>

                <Separator />

                <div className="flex-1 space-y-4 overflow-y-auto py-4">
                    {messages.data.map((message) => {
                        const isOwn = message.user_id === currentUserId;
                        return (
                            <div
                                key={message.id}
                                className={`flex ${isOwn ? 'justify-end' : 'justify-start'}`}
                            >
                                <div
                                    className={`max-w-[75%] rounded-lg p-3 ${
                                        isOwn
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-muted'
                                    }`}
                                >
                                    {!isOwn && message.user && (
                                        <p className="mb-1 text-xs font-medium">
                                            {message.user.display_name}
                                        </p>
                                    )}
                                    <div className="prose dark:prose-invert prose-sm max-w-none">
                                        <SlateRenderer
                                            value={
                                                message.body as Descendant[]
                                            }
                                        />
                                    </div>
                                    <p
                                        className={`mt-1 text-xs ${isOwn ? 'text-primary-foreground/70' : 'text-muted-foreground'}`}
                                    >
                                        {formatTime(message.created_at)}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                    <div ref={messagesEndRef} />
                </div>

                <Separator />

                <form onSubmit={handleSubmit} className="space-y-3 pt-4">
                    <SlateEditor
                        key={editorKey}
                        value={data.body as unknown as Descendant[]}
                        onChange={(value) =>
                            setData('body', value as unknown as SlateNode[])
                        }
                        placeholder="Type a message..."
                    />
                    <div className="flex justify-end">
                        <Button size="sm" disabled={processing}>
                            Send
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
