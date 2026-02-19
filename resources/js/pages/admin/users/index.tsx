import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Ban,
    Pause,
    Play,
    Plus,
    Search,
    Trash2,
    UserPlus,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import AdminLayout from '@/layouts/admin/layout';
import type { BreadcrumbItem } from '@/types';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface AdminUser {
    id: number;
    username: string;
    name: string;
    email: string;
    role: 'admin' | 'moderator' | 'user';
    is_deleted: boolean;
    is_suspended: boolean;
    display_name: string;
    created_at: string;
}

interface UsersIndexProps {
    users: {
        data: AdminUser[];
        links: PaginationLink[];
    };
    search: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

function roleBadgeVariant(
    role: string,
): 'default' | 'secondary' | 'destructive' {
    if (role === 'admin') return 'destructive';
    if (role === 'moderator') return 'secondary';
    return 'default';
}

export default function UsersIndex({ users, search }: UsersIndexProps) {
    const { auth } = usePage().props;
    const [searchQuery, setSearchQuery] = useState(search || '');
    const [actionUserId, setActionUserId] = useState<number | null>(null);
    const [actionType, setActionType] = useState<
        'ban' | 'delete' | 'suspend' | 'unsuspend' | null
    >(null);

    const banForm = useForm({ reason: '' });

    function handleSearch(e: FormEvent) {
        e.preventDefault();
        router.get(
            '/admin/users',
            searchQuery ? { search: searchQuery } : {},
            { preserveState: true },
        );
    }

    function openAction(
        userId: number,
        type: 'ban' | 'delete' | 'suspend' | 'unsuspend',
    ) {
        setActionUserId(userId);
        setActionType(type);
        banForm.reset();
    }

    function closeAction() {
        setActionUserId(null);
        setActionType(null);
    }

    function handleAction() {
        if (!actionUserId || !actionType) return;

        switch (actionType) {
            case 'ban':
                banForm.post(`/admin/users/${actionUserId}/ban`, {
                    onFinish: closeAction,
                });
                break;
            case 'suspend':
                router.post(`/admin/users/${actionUserId}/suspend`, {}, {
                    onFinish: closeAction,
                });
                break;
            case 'unsuspend':
                router.post(
                    `/admin/users/${actionUserId}/unsuspend`,
                    {},
                    { onFinish: closeAction },
                );
                break;
            case 'delete':
                router.delete(`/admin/users/${actionUserId}`, {
                    onFinish: closeAction,
                });
                break;
        }
    }

    const actionUser = users.data.find((u) => u.id === actionUserId);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users - Admin" />

            <AdminLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Users"
                            description="Manage forum users"
                        />
                        <Button asChild size="sm">
                            <Link href="/admin/users/create">
                                <UserPlus className="mr-1 h-4 w-4" />
                                Create user
                            </Link>
                        </Button>
                    </div>

                    <form onSubmit={handleSearch} className="flex gap-2">
                        <div className="relative flex-1">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                placeholder="Search users..."
                                className="pl-9"
                            />
                        </div>
                        <Button type="submit" variant="outline">
                            Search
                        </Button>
                    </form>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Username</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-center text-muted-foreground"
                                    >
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            )}
                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="font-medium">
                                        <Link
                                            href={`/users/${user.username}`}
                                            className="hover:underline"
                                        >
                                            {user.display_name}
                                        </Link>
                                        <div className="text-xs text-muted-foreground">
                                            @{user.username}
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {user.is_deleted
                                            ? '(anonymized)'
                                            : user.email}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={roleBadgeVariant(
                                                user.role,
                                            )}
                                        >
                                            {user.role}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {user.is_deleted ? (
                                            <Badge variant="outline">
                                                Deleted
                                            </Badge>
                                        ) : user.is_suspended ? (
                                            <Badge variant="secondary">
                                                Suspended
                                            </Badge>
                                        ) : (
                                            <Badge variant="default">
                                                Active
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {!user.is_deleted && user.id !== (auth.user as { id: number }).id && (
                                            <div className="flex items-center justify-end gap-1">
                                                {user.is_suspended ? (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Unsuspend"
                                                        onClick={() =>
                                                            openAction(
                                                                user.id,
                                                                'unsuspend',
                                                            )
                                                        }
                                                    >
                                                        <Play className="h-4 w-4" />
                                                    </Button>
                                                ) : (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Suspend"
                                                        onClick={() =>
                                                            openAction(
                                                                user.id,
                                                                'suspend',
                                                            )
                                                        }
                                                    >
                                                        <Pause className="h-4 w-4" />
                                                    </Button>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Ban"
                                                    onClick={() =>
                                                        openAction(
                                                            user.id,
                                                            'ban',
                                                        )
                                                    }
                                                >
                                                    <Ban className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Delete"
                                                    onClick={() =>
                                                        openAction(
                                                            user.id,
                                                            'delete',
                                                        )
                                                    }
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        )}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {users.links.length > 3 && (
                        <nav className="flex items-center justify-center gap-1">
                            {users.links.map((link, index) => (
                                <Button
                                    key={index}
                                    variant={
                                        link.active ? 'default' : 'outline'
                                    }
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

                {/* Action Dialogs */}
                <Dialog
                    open={actionType !== null}
                    onOpenChange={(open) => !open && closeAction()}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                {actionType === 'ban' && 'Ban user'}
                                {actionType === 'delete' && 'Delete user'}
                                {actionType === 'suspend' && 'Suspend user'}
                                {actionType === 'unsuspend' && 'Unsuspend user'}
                            </DialogTitle>
                            <DialogDescription>
                                {actionType === 'ban' &&
                                    `Ban "${actionUser?.display_name}"? Their email will be blocked from future registration. This action cannot be undone.`}
                                {actionType === 'delete' &&
                                    `Delete "${actionUser?.display_name}"? Their personal info will be anonymized. This action cannot be undone.`}
                                {actionType === 'suspend' &&
                                    `Suspend "${actionUser?.display_name}"? They won't be able to create discussions or replies.`}
                                {actionType === 'unsuspend' &&
                                    `Unsuspend "${actionUser?.display_name}"? They will regain full posting privileges.`}
                            </DialogDescription>
                        </DialogHeader>

                        {actionType === 'ban' && (
                            <div className="grid gap-2 py-2">
                                <Label htmlFor="reason">
                                    Reason (optional)
                                </Label>
                                <Textarea
                                    id="reason"
                                    value={banForm.data.reason}
                                    onChange={(e) =>
                                        banForm.setData(
                                            'reason',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Reason for banning..."
                                />
                            </div>
                        )}

                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="outline">Cancel</Button>
                            </DialogClose>
                            <Button
                                variant={
                                    actionType === 'unsuspend'
                                        ? 'default'
                                        : 'destructive'
                                }
                                onClick={handleAction}
                            >
                                {actionType === 'ban' && 'Ban'}
                                {actionType === 'delete' && 'Delete'}
                                {actionType === 'suspend' && 'Suspend'}
                                {actionType === 'unsuspend' && 'Unsuspend'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </AdminLayout>
        </AppLayout>
    );
}
