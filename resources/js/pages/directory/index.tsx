import { Head, Link, router } from '@inertiajs/react';
import { Search, Users } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface DirectoryUser {
    id: number;
    username: string;
    name: string;
    preferred_name: string | null;
    bio: string | null;
    avatar_path: string | null;
    role: 'admin' | 'moderator' | 'user';
    display_name: string;
    created_at: string;
}

interface DirectoryProps {
    users: {
        data: DirectoryUser[];
        links: PaginationLink[];
    };
    search: string;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export default function DirectoryIndex({ users, search }: DirectoryProps) {
    const [searchQuery, setSearchQuery] = useState(search || '');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
        { title: 'Directory', href: '/directory' },
    ];

    function handleSearch(e: React.FormEvent) {
        e.preventDefault();
        router.get(
            '/directory',
            searchQuery ? { search: searchQuery } : {},
            { preserveState: true },
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Member Directory" />

            <div className="mx-auto w-full max-w-6xl space-y-6 p-4 lg:p-6">
                <Heading
                    title="Member Directory"
                    description="Browse community members"
                />

                <form onSubmit={handleSearch} className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={searchQuery}
                            onChange={(e) => setSearchQuery(e.target.value)}
                            placeholder="Search by username or name..."
                            className="pl-9"
                        />
                    </div>
                    <Button type="submit" variant="outline">
                        Search
                    </Button>
                </form>

                {users.data.length === 0 && (
                    <div className="flex flex-col items-center gap-2 py-16 text-muted-foreground">
                        <Users className="h-10 w-10" />
                        <p>No members found.</p>
                    </div>
                )}

                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {users.data.map((user) => (
                        <Link
                            key={user.id}
                            href={`/users/${user.username}`}
                            className="block"
                        >
                            <Card className="transition-colors hover:bg-muted/50">
                                <CardContent className="flex items-center gap-4 p-4">
                                    <Avatar className="h-12 w-12">
                                        {user.avatar_path && (
                                            <AvatarImage
                                                src={`/storage/${user.avatar_path}`}
                                                alt={user.display_name}
                                            />
                                        )}
                                        <AvatarFallback>
                                            {getInitials(user.display_name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            <p className="truncate text-sm font-medium">
                                                {user.display_name}
                                            </p>
                                            {user.role !== 'user' && (
                                                <Badge
                                                    variant={
                                                        user.role === 'admin'
                                                            ? 'destructive'
                                                            : 'secondary'
                                                    }
                                                    className="shrink-0 text-xs"
                                                >
                                                    {user.role}
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="truncate text-xs text-muted-foreground">
                                            @{user.username}
                                        </p>
                                        {user.bio && (
                                            <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                                                {user.bio}
                                            </p>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>

                {users.links.length > 3 && (
                    <nav className="flex items-center justify-center gap-1">
                        {users.links.map((link, index) => (
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
