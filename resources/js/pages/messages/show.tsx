import { Head, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Send } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { Descendant } from 'slate';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { MessageBubble, type MessageType } from '@/components/message-bubble';
import { SlateEditor } from '@/components/slate-editor/editor';
import { DEFAULT_INITIAL_VALUE } from '@/components/slate-editor/types';
import AppLayout from '@/layouts/app-layout';
import type { Auth, BreadcrumbItem } from '@/types';
import { Link } from '@inertiajs/react';

type ConversationUser = {
    id: number;
    name: string;
    username: string;
    avatar_path: string | null;
    preferred_name: string | null;
    is_deleted: boolean;
    display_name: string;
};

type Conversation = {
    id: number;
    users: ConversationUser[];
};

type Props = {
    conversation: Conversation;
    messages: MessageType[];
};

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

export default function ConversationShow({ conversation, messages }: Props) {
    const { auth } = usePage<{ auth: Auth }>().props;
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const [editorKey, setEditorKey] = useState(0);

    const otherUser = conversation.users.find((u) => u.id !== auth.user.id);
    const otherDisplayName = getDisplayName(otherUser);
    const otherInitials = getInitials(otherDisplayName);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Messages', href: '/messages' },
        { title: otherDisplayName, href: `/messages/${conversation.id}` },
    ];

    const form = useForm<{ body: Descendant[] }>({
        body: DEFAULT_INITIAL_VALUE,
    });

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        form.post(`/messages/${conversation.id}/reply`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setEditorKey((k) => k + 1);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Message - ${otherDisplayName}`} />

            <div className="mx-auto flex h-[calc(100vh-8rem)] max-w-3xl flex-col p-6">
                <div className="mb-4 flex items-center gap-3 border-b pb-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/messages">
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <Avatar className="size-8">
                        {otherUser?.avatar_path && (
                            <AvatarImage
                                src={`/storage/${otherUser.avatar_path}`}
                                alt={otherDisplayName}
                            />
                        )}
                        <AvatarFallback className="text-xs">{otherInitials}</AvatarFallback>
                    </Avatar>
                    <div>
                        <h1 className="text-sm font-semibold">{otherDisplayName}</h1>
                        {otherUser && !otherUser.is_deleted && (
                            <Link
                                href={`/users/${otherUser.username}`}
                                className="text-xs text-muted-foreground hover:underline"
                            >
                                @{otherUser.username}
                            </Link>
                        )}
                    </div>
                </div>

                <div className="flex-1 space-y-4 overflow-y-auto pr-2">
                    {messages.map((message) => (
                        <MessageBubble
                            key={message.id}
                            message={message}
                            isOwn={message.user?.id === auth.user.id}
                        />
                    ))}
                    <div ref={messagesEndRef} />
                </div>

                <form onSubmit={handleSubmit} className="mt-4 space-y-3 border-t pt-4">
                    <SlateEditor
                        key={editorKey}
                        initialValue={DEFAULT_INITIAL_VALUE}
                        onChange={(value) => form.setData('body', value)}
                        placeholder="Write a message..."
                    />
                    {form.errors.body && (
                        <p className="text-sm text-destructive">{form.errors.body}</p>
                    )}
                    <div className="flex justify-end">
                        <Button type="submit" size="sm" disabled={form.processing}>
                            <Send className="mr-2 size-4" />
                            {form.processing ? 'Sending...' : 'Send'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
