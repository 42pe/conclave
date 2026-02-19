import { Head, Link } from '@inertiajs/react';
import { Mail } from 'lucide-react';
import Heading from '@/components/heading';
import UserDisplay from '@/components/user-display';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ConversationItem } from '@/types';
import { usePage } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface MessagesIndexProps {
    conversations: {
        data: ConversationItem[];
        links: PaginationLink[];
    };
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Home', href: '/' },
    { title: 'Messages', href: '/messages' },
];

export default function MessagesIndex({ conversations }: MessagesIndexProps) {
    const { auth } = usePage().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Messages" />

            <div className="mx-auto w-full max-w-3xl space-y-6 p-4 lg:p-6">
                <Heading
                    title="Messages"
                    description="Your private conversations"
                />

                {conversations.data.length === 0 && (
                    <div className="flex flex-col items-center gap-2 py-16 text-muted-foreground">
                        <Mail className="h-10 w-10" />
                        <p>No conversations yet.</p>
                        <p className="text-sm">
                            Visit a user profile to start a conversation.
                        </p>
                    </div>
                )}

                <div className="space-y-2">
                    {conversations.data.map((conversation) => {
                        const otherParticipant = conversation.participants.find(
                            (p) => p.id !== (auth.user as { id: number }).id,
                        );

                        return (
                            <Link
                                key={conversation.id}
                                href={`/conversations/${conversation.id}`}
                            >
                                <Card
                                    className={`transition-colors hover:bg-muted/50 ${conversation.has_unread ? 'border-primary/50' : ''}`}
                                >
                                    <CardContent className="flex items-center gap-4 p-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                {otherParticipant && (
                                                    <UserDisplay
                                                        user={otherParticipant}
                                                        size="sm"
                                                    />
                                                )}
                                                {conversation.has_unread && (
                                                    <Badge variant="default" className="ml-auto shrink-0">
                                                        New
                                                    </Badge>
                                                )}
                                            </div>
                                            {conversation.latest_message && (
                                                <p className="mt-1 truncate text-xs text-muted-foreground">
                                                    {conversation.latest_message
                                                        .user
                                                        ?.display_name ?? ''}
                                                    :{' '}
                                                    {getPreviewText(
                                                        conversation
                                                            .latest_message
                                                            .body,
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                        {conversation.latest_message && (
                                            <span className="shrink-0 text-xs text-muted-foreground">
                                                {formatTimeAgo(
                                                    conversation.latest_message
                                                        .created_at,
                                                )}
                                            </span>
                                        )}
                                    </CardContent>
                                </Card>
                            </Link>
                        );
                    })}
                </div>

                {conversations.links.length > 3 && (
                    <nav className="flex items-center justify-center gap-1">
                        {conversations.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </Button>
                        ))}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}

function getPreviewText(body: unknown[]): string {
    if (!Array.isArray(body) || body.length === 0) return '';
    const firstBlock = body[0] as Record<string, unknown>;
    const children = firstBlock?.children as Array<Record<string, unknown>> | undefined;
    if (!children) return '';
    const texts = children.map((c) => (c.text as string) || '').join('');
    return texts.length > 80 ? texts.slice(0, 80) + '...' : texts;
}
