import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { UserCard } from '@/components/user-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type DirectoryUser = {
    id: number;
    username: string;
    name: string;
    preferred_name: string | null;
    avatar_path: string | null;
    bio: string | null;
    role: 'admin' | 'moderator' | 'user';
    is_deleted: boolean;
    created_at: string;
};

type PaginatedUsers = {
    data: DirectoryUser[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
    prev_page_url: string | null;
    total: number;
};

type Filters = {
    search: string;
    sort: string;
};

type Props = {
    users: PaginatedUsers;
    filters: Filters;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Directory', href: '/directory' },
];

export default function DirectoryIndex({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(
            '/directory',
            { search, sort: filters.sort },
            { preserveState: true },
        );
    };

    const handleSort = (value: string) => {
        router.get(
            '/directory',
            { search: filters.search, sort: value },
            { preserveState: true },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Member Directory" />

            <div className="space-y-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">
                        Member Directory
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Browse and find community members.
                    </p>
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <form
                        onSubmit={handleSearch}
                        className="relative flex-1"
                    >
                        <Search className="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Search by name or username..."
                            className="pl-9"
                        />
                    </form>
                    <Select
                        defaultValue={filters.sort}
                        onValueChange={handleSort}
                    >
                        <SelectTrigger className="w-[160px]">
                            <SelectValue placeholder="Sort by" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="name">Name</SelectItem>
                            <SelectItem value="newest">Newest</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {users.data.length === 0 ? (
                    <div className="rounded-lg border py-12 text-center text-muted-foreground">
                        No members found.
                    </div>
                ) : (
                    <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {users.data.map((user) => (
                            <UserCard key={user.id} user={user} />
                        ))}
                    </div>
                )}

                {users.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {users.prev_page_url && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={users.prev_page_url}>
                                    Previous
                                </Link>
                            </Button>
                        )}
                        <span className="text-sm text-muted-foreground">
                            Page {users.current_page} of {users.last_page}
                        </span>
                        {users.next_page_url && (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={users.next_page_url}>Next</Link>
                            </Button>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
