import { Head, Link, usePage } from '@inertiajs/react';
import { Calendar, Mail, MessageSquare, Shield, ShieldCheck } from 'lucide-react';
import { SlateRenderer } from '@/components/slate-editor';
import UserDisplay from '@/components/user-display';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Descendant } from 'slate';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ProfileUser {
    id: number;
    username: string;
    name: string;
    first_name: string | null;
    last_name: string | null;
    preferred_name: string | null;
    bio: string | null;
    avatar_path: string | null;
    role: 'admin' | 'moderator' | 'user';
    is_deleted: boolean;
    is_suspended: boolean;
    display_name: string;
    show_real_name: boolean;
    show_email: boolean;
    email?: string;
    created_at: string;
}

interface ProfileDiscussion {
    id: number;
    title: string;
    slug: string;
    reply_count: number;
    created_at: string;
    topic?: { id: number; title: string; slug: string };
    location?: { id: number; name: string } | null;
}

interface ProfileReply {
    id: number;
    body: unknown[];
    created_at: string;
    discussion?: {
        id: number;
        title: string;
        slug: string;
        topic_id: number;
        topic?: { id: number; title: string; slug: string };
    };
}

interface UserShowProps {
    profileUser: ProfileUser;
    discussions: {
        data: ProfileDiscussion[];
        links: PaginationLink[];
    };
    replies: {
        data: ProfileReply[];
        links: PaginationLink[];
    };
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
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

function Pagination({ links }: { links: PaginationLink[] }) {
    if (links.length <= 3) return null;

    return (
        <nav className="flex items-center justify-center gap-1">
            {links.map((link, index) => (
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
    );
}

export default function UserShow({
    profileUser,
    discussions,
    replies,
}: UserShowProps) {
    const { auth } = usePage().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
        { title: 'Directory', href: '/directory' },
        {
            title: profileUser.display_name,
            href: `/users/${profileUser.username}`,
        },
    ];

    const roleBadge =
        profileUser.role === 'admin' ? (
            <Badge variant="destructive">
                <Shield className="mr-1 h-3 w-3" />
                Admin
            </Badge>
        ) : profileUser.role === 'moderator' ? (
            <Badge variant="secondary">
                <ShieldCheck className="mr-1 h-3 w-3" />
                Moderator
            </Badge>
        ) : null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${profileUser.display_name} - Profile`} />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 lg:p-6">
                {/* Profile Header */}
                <div className="flex items-start gap-6">
                    <Avatar className="h-20 w-20 text-2xl">
                        {profileUser.avatar_path && (
                            <AvatarImage
                                src={`/storage/${profileUser.avatar_path}`}
                                alt={profileUser.display_name}
                            />
                        )}
                        <AvatarFallback>
                            {profileUser.is_deleted
                                ? '?'
                                : getInitials(profileUser.display_name)}
                        </AvatarFallback>
                    </Avatar>

                    <div className="min-w-0 space-y-2">
                        <div className="flex flex-wrap items-center gap-2">
                            <h1 className="text-2xl font-semibold">
                                {profileUser.display_name}
                            </h1>
                            {roleBadge}
                            {profileUser.is_suspended && (
                                <Badge variant="outline">Suspended</Badge>
                            )}
                        </div>

                        <p className="text-sm text-muted-foreground">
                            @{profileUser.username}
                        </p>

                        {profileUser.show_real_name &&
                            !profileUser.is_deleted &&
                            profileUser.preferred_name &&
                            profileUser.preferred_name !==
                                profileUser.name && (
                                <p className="text-sm text-muted-foreground">
                                    {profileUser.name}
                                </p>
                            )}

                        {profileUser.show_email &&
                            !profileUser.is_deleted &&
                            profileUser.email && (
                                <p className="text-sm text-muted-foreground">
                                    {profileUser.email}
                                </p>
                            )}

                        <div className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Calendar className="h-3 w-3" />
                            <span>
                                Joined {formatDate(profileUser.created_at)}
                            </span>
                        </div>

                        {auth.user &&
                            auth.user.id !== profileUser.id &&
                            !profileUser.is_deleted && (
                                <Button asChild size="sm" variant="outline">
                                    <Link href={`/conversations/start/${profileUser.id}`}>
                                        <Mail className="mr-1 h-4 w-4" />
                                        Send Message
                                    </Link>
                                </Button>
                            )}
                    </div>
                </div>

                {profileUser.bio && !profileUser.is_deleted && (
                    <p className="text-sm leading-relaxed">
                        {profileUser.bio}
                    </p>
                )}

                <Separator />

                {/* Discussions */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Discussions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {discussions.data.length === 0 && (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                No discussions yet.
                            </p>
                        )}

                        {discussions.data.map((discussion) => (
                            <div
                                key={discussion.id}
                                className="flex items-center gap-3 rounded-md border p-3"
                            >
                                <div className="min-w-0 flex-1">
                                    <Link
                                        href={
                                            discussion.topic
                                                ? `/topics/${discussion.topic.slug}/discussions/${discussion.slug}`
                                                : '#'
                                        }
                                        className="text-sm font-medium hover:underline"
                                    >
                                        {discussion.title}
                                    </Link>
                                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                        {discussion.topic && (
                                            <span>
                                                in {discussion.topic.title}
                                            </span>
                                        )}
                                        <span>&middot;</span>
                                        <span>
                                            {formatTimeAgo(
                                                discussion.created_at,
                                            )}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex shrink-0 items-center gap-1 text-xs text-muted-foreground">
                                    <MessageSquare className="h-3.5 w-3.5" />
                                    <span>{discussion.reply_count}</span>
                                </div>
                            </div>
                        ))}

                        <Pagination links={discussions.links} />
                    </CardContent>
                </Card>

                {/* Replies */}
                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Replies</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {replies.data.length === 0 && (
                            <p className="py-4 text-center text-sm text-muted-foreground">
                                No replies yet.
                            </p>
                        )}

                        {replies.data.map((reply) => (
                            <div
                                key={reply.id}
                                className="rounded-md border p-3"
                            >
                                {reply.discussion && (
                                    <Link
                                        href={
                                            reply.discussion.topic
                                                ? `/topics/${reply.discussion.topic.slug}/discussions/${reply.discussion.slug}`
                                                : '#'
                                        }
                                        className="mb-2 block text-xs text-muted-foreground hover:underline"
                                    >
                                        Re: {reply.discussion.title}
                                    </Link>
                                )}
                                <div className="prose dark:prose-invert prose-sm max-w-none line-clamp-3">
                                    <SlateRenderer
                                        value={reply.body as Descendant[]}
                                    />
                                </div>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {formatTimeAgo(reply.created_at)}
                                </p>
                            </div>
                        ))}

                        <Pagination links={replies.links} />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
