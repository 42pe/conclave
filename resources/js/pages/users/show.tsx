import { Head, Link } from '@inertiajs/react';
import { Calendar, Lock, Mail, MessageSquare, Shield, User as UserIcon } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { SlateRenderer } from '@/components/slate-editor/renderer';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Descendant } from 'slate';

type ProfileUser = {
    id: number;
    username: string;
    display_name: string;
    avatar_path: string | null;
    role: 'admin' | 'moderator' | 'user' | null;
    is_deleted: boolean;
    is_suspended: boolean;
    bio: string | null;
    first_name?: string;
    last_name?: string;
    email?: string;
    created_at: string;
};

type Discussion = {
    id: number;
    title: string;
    slug: string;
    reply_count: number;
    last_reply_at: string | null;
    created_at: string;
    is_pinned: boolean;
    is_locked: boolean;
    topic: {
        id: number;
        title: string;
        slug: string;
    };
};

type PaginatedDiscussions = {
    data: Discussion[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    total: number;
};

type RecentReply = {
    id: number;
    discussion_id: number;
    body: Descendant[];
    created_at: string;
    discussion: {
        id: number;
        title: string;
        slug: string;
        topic_id: number;
        topic: {
            id: number;
            title: string;
            slug: string;
        };
    };
};

type Props = {
    profileUser: ProfileUser;
    discussions: PaginatedDiscussions;
    recentReplies: RecentReply[];
};

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export default function UserProfile({ profileUser, discussions, recentReplies }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Directory', href: '/directory' },
        { title: profileUser.display_name, href: `/users/${profileUser.username}` },
    ];

    const initials = getInitials(profileUser.display_name);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={profileUser.display_name} />

            <div className="mx-auto max-w-4xl space-y-6 p-6">
                <Card>
                    <CardContent className="flex flex-col items-center gap-6 sm:flex-row sm:items-start">
                        <Avatar className="size-24">
                            {profileUser.avatar_path && (
                                <AvatarImage
                                    src={`/storage/${profileUser.avatar_path}`}
                                    alt={profileUser.display_name}
                                />
                            )}
                            <AvatarFallback className="text-2xl">
                                {initials}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex-1 space-y-3 text-center sm:text-left">
                            <div>
                                <div className="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                    <h1 className="text-2xl font-bold">
                                        {profileUser.display_name}
                                    </h1>
                                    {profileUser.role && profileUser.role !== 'user' && (
                                        <Badge
                                            variant={
                                                profileUser.role === 'admin'
                                                    ? 'destructive'
                                                    : 'secondary'
                                            }
                                        >
                                            <Shield className="mr-1 size-3" />
                                            {profileUser.role}
                                        </Badge>
                                    )}
                                    {profileUser.is_suspended && (
                                        <Badge variant="outline">
                                            <Lock className="mr-1 size-3" />
                                            Suspended
                                        </Badge>
                                    )}
                                </div>
                                {!profileUser.is_deleted && (
                                    <p className="text-sm text-muted-foreground">
                                        @{profileUser.username}
                                    </p>
                                )}
                            </div>

                            {!profileUser.is_deleted && profileUser.bio && (
                                <p className="text-sm">{profileUser.bio}</p>
                            )}

                            <div className="flex flex-wrap items-center justify-center gap-4 text-sm text-muted-foreground sm:justify-start">
                                {profileUser.first_name && (
                                    <span className="flex items-center gap-1">
                                        <UserIcon className="size-3.5" />
                                        {profileUser.first_name}{' '}
                                        {profileUser.last_name}
                                    </span>
                                )}
                                {profileUser.email && (
                                    <span className="flex items-center gap-1">
                                        <Mail className="size-3.5" />
                                        {profileUser.email}
                                    </span>
                                )}
                                <span className="flex items-center gap-1">
                                    <Calendar className="size-3.5" />
                                    Joined{' '}
                                    {new Date(
                                        profileUser.created_at,
                                    ).toLocaleDateString(undefined, {
                                        year: 'numeric',
                                        month: 'long',
                                    })}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {!profileUser.is_deleted && (
                    <Tabs defaultValue="discussions">
                        <TabsList>
                            <TabsTrigger value="discussions">
                                Discussions ({discussions.total})
                            </TabsTrigger>
                            <TabsTrigger value="replies">
                                Recent Replies ({recentReplies.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="discussions" className="space-y-2">
                            {discussions.data.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    No discussions yet.
                                </p>
                            ) : (
                                <>
                                    {discussions.data.map((discussion) => (
                                        <Card key={discussion.id}>
                                            <CardContent className="flex items-center justify-between gap-4">
                                                <div className="min-w-0">
                                                    <Link
                                                        href={`/topics/${discussion.topic.slug}/discussions/${discussion.slug}`}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {discussion.title}
                                                    </Link>
                                                    <p className="text-sm text-muted-foreground">
                                                        in{' '}
                                                        <Link
                                                            href={`/topics/${discussion.topic.slug}`}
                                                            className="hover:underline"
                                                        >
                                                            {discussion.topic.title}
                                                        </Link>
                                                        {' -- '}
                                                        {new Date(
                                                            discussion.created_at,
                                                        ).toLocaleDateString()}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                    <MessageSquare className="size-4" />
                                                    {discussion.reply_count}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}

                                    {discussions.last_page > 1 && (
                                        <div className="flex items-center justify-center gap-2 pt-4">
                                            {discussions.prev_page_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            discussions.prev_page_url
                                                        }
                                                    >
                                                        Previous
                                                    </Link>
                                                </Button>
                                            )}
                                            <span className="text-sm text-muted-foreground">
                                                Page {discussions.current_page}{' '}
                                                of {discussions.last_page}
                                            </span>
                                            {discussions.next_page_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            discussions.next_page_url
                                                        }
                                                    >
                                                        Next
                                                    </Link>
                                                </Button>
                                            )}
                                        </div>
                                    )}
                                </>
                            )}
                        </TabsContent>

                        <TabsContent value="replies" className="space-y-2">
                            {recentReplies.length === 0 ? (
                                <p className="py-8 text-center text-sm text-muted-foreground">
                                    No replies yet.
                                </p>
                            ) : (
                                recentReplies.map((reply) => (
                                    <Card key={reply.id}>
                                        <CardHeader>
                                            <CardTitle className="text-sm font-normal">
                                                Replied in{' '}
                                                <Link
                                                    href={`/topics/${reply.discussion.topic.slug}/discussions/${reply.discussion.slug}`}
                                                    className="font-medium hover:underline"
                                                >
                                                    {reply.discussion.title}
                                                </Link>
                                                <span className="ml-2 text-muted-foreground">
                                                    {new Date(
                                                        reply.created_at,
                                                    ).toLocaleDateString()}
                                                </span>
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="max-h-32 overflow-hidden text-sm">
                                                <SlateRenderer
                                                    value={reply.body}
                                                />
                                            </div>
                                        </CardContent>
                                    </Card>
                                ))
                            )}
                        </TabsContent>
                    </Tabs>
                )}
            </div>
        </AppLayout>
    );
}
