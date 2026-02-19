import { Head, Link, router, usePage } from '@inertiajs/react';
import { Lock, MessageSquare, Pin, Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { getIconComponent } from '@/components/icon-picker';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Discussion, LocationItem, Topic } from '@/types';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface TopicShowProps {
    topic: Topic;
    discussions: {
        data: Discussion[];
        links: PaginationLink[];
        meta: { current_page: number; last_page: number };
    };
    locations: LocationItem[];
}

function formatTimeAgo(dateString: string): string {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (seconds < 60) {
        return 'just now';
    }

    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) {
        return `${minutes}m ago`;
    }

    const hours = Math.floor(minutes / 60);
    if (hours < 24) {
        return `${hours}h ago`;
    }

    const days = Math.floor(hours / 24);
    if (days < 30) {
        return `${days}d ago`;
    }

    const months = Math.floor(days / 30);
    if (months < 12) {
        return `${months}mo ago`;
    }

    return `${Math.floor(months / 12)}y ago`;
}

export default function TopicShow({
    topic,
    discussions,
    locations,
}: TopicShowProps) {
    const { auth } = usePage().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
    ];

    const TopicIcon = topic.icon ? getIconComponent(topic.icon) : null;

    const currentParams = new URLSearchParams(window.location.search);
    const currentLocationId = currentParams.get('location_id') ?? '';

    function handleLocationFilter(value: string) {
        const url = `/topics/${topic.slug}`;
        if (value === 'all') {
            router.get(url);
        } else {
            router.get(url, { location_id: value }, { preserveState: true });
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={topic.title} />

            <div className="mx-auto w-full max-w-4xl space-y-6 p-4 lg:p-6">
                <div className="flex items-start justify-between gap-4">
                    <div className="flex items-start gap-3">
                        {TopicIcon && (
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted">
                                <TopicIcon className="h-5 w-5 text-muted-foreground" />
                            </div>
                        )}
                        <div>
                            <Heading title={topic.title} />
                            {topic.description && (
                                <p className="text-sm text-muted-foreground">
                                    {topic.description}
                                </p>
                            )}
                        </div>
                    </div>

                    {auth.user && (
                        <Button asChild size="sm">
                            <Link
                                href={`/topics/${topic.slug}/discussions/create`}
                            >
                                <Plus className="mr-1 h-4 w-4" />
                                New Discussion
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="flex items-center gap-4">
                    <Select
                        value={currentLocationId || 'all'}
                        onValueChange={handleLocationFilter}
                    >
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Filter by location" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All locations</SelectItem>
                            {locations.map((location) => (
                                <SelectItem
                                    key={location.id}
                                    value={String(location.id)}
                                >
                                    {location.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Separator />

                <div className="space-y-2">
                    {discussions.data.length === 0 && (
                        <p className="py-12 text-center text-muted-foreground">
                            No discussions yet. Be the first to start one!
                        </p>
                    )}

                    {discussions.data.map((discussion) => (
                        <div
                            key={discussion.id}
                            className="flex items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/50"
                        >
                            <div className="min-w-0 flex-1 space-y-1">
                                <div className="flex items-center gap-2">
                                    {discussion.is_pinned && (
                                        <Pin className="h-3.5 w-3.5 shrink-0 text-amber-500" />
                                    )}
                                    <Link
                                        href={`/topics/${topic.slug}/discussions/${discussion.slug}`}
                                        className="truncate font-medium hover:underline"
                                    >
                                        {discussion.title}
                                    </Link>
                                    {discussion.is_locked && (
                                        <Badge
                                            variant="secondary"
                                            className="shrink-0"
                                        >
                                            <Lock className="mr-1 h-3 w-3" />
                                            Locked
                                        </Badge>
                                    )}
                                </div>
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <span>
                                        {discussion.user?.display_name ??
                                            'Unknown'}
                                    </span>
                                    <span>&middot;</span>
                                    <span>
                                        {formatTimeAgo(discussion.created_at)}
                                    </span>
                                    {discussion.location && (
                                        <>
                                            <span>&middot;</span>
                                            <Badge
                                                variant="outline"
                                                className="text-xs"
                                            >
                                                {discussion.location.name}
                                            </Badge>
                                        </>
                                    )}
                                </div>
                            </div>

                            <div className="flex shrink-0 items-center gap-1 text-sm text-muted-foreground">
                                <MessageSquare className="h-4 w-4" />
                                <span>{discussion.reply_count}</span>
                            </div>
                        </div>
                    ))}
                </div>

                {discussions.links.length > 3 && (
                    <nav className="flex items-center justify-center gap-1">
                        {discussions.links.map((link, index) => (
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
