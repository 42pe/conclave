import { Head, Link } from '@inertiajs/react';
import { Mail, PenSquare } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ConversationCard, type ConversationType } from '@/components/conversation-card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Props = {
    conversations: ConversationType[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Messages', href: '/messages' },
];

export default function MessagesIndex({ conversations }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Messages" />

            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Mail className="size-5" />
                        <h1 className="text-xl font-semibold">Messages</h1>
                    </div>
                    <Button asChild size="sm">
                        <Link href="/directory">
                            <PenSquare className="mr-2 size-4" />
                            New Conversation
                        </Link>
                    </Button>
                </div>

                {conversations.length > 0 ? (
                    <div className="space-y-2">
                        {conversations.map((conversation) => (
                            <ConversationCard
                                key={conversation.id}
                                conversation={conversation}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="py-12 text-center">
                        <Mail className="mx-auto mb-4 size-12 text-muted-foreground/50" />
                        <h2 className="text-lg font-medium">No conversations yet</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Start a conversation by visiting a user's profile from the directory.
                        </p>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
