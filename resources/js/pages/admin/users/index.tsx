import { Head, Link, router, useForm } from '@inertiajs/react';
import { Ban, MoreHorizontal, Plus, ShieldOff, Trash2, UserCheck, UserX } from 'lucide-react';
import { useState } from 'react';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, User } from '@/types';
import {
    index,
    create,
    suspend,
    unsuspend,
    ban,
    deleteMethod,
} from '@/routes/admin/users';

type PaginatedUsers = {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: index().url,
    },
    {
        title: 'Users',
        href: index().url,
    },
];

function statusBadge(user: User) {
    if (user.is_deleted) {
        return <Badge variant="destructive">Deleted</Badge>;
    }
    if (user.is_suspended) {
        return <Badge variant="secondary">Suspended</Badge>;
    }
    return <Badge variant="default">Active</Badge>;
}

function roleBadge(role: string) {
    switch (role) {
        case 'admin':
            return <Badge variant="outline">Admin</Badge>;
        case 'moderator':
            return <Badge variant="outline">Moderator</Badge>;
        default:
            return <Badge variant="outline">User</Badge>;
    }
}

export default function UsersIndex({ users }: { users: PaginatedUsers }) {
    const [suspendTarget, setSuspendTarget] = useState<User | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<User | null>(null);
    const [banTarget, setBanTarget] = useState<User | null>(null);

    const banForm = useForm({ reason: '' });

    const handleSuspend = () => {
        if (!suspendTarget) return;
        router.post(suspend(suspendTarget.id).url, {}, {
            preserveScroll: true,
            onFinish: () => setSuspendTarget(null),
        });
    };

    const handleUnsuspend = (user: User) => {
        router.post(unsuspend(user.id).url, {}, {
            preserveScroll: true,
        });
    };

    const handleBan = () => {
        if (!banTarget) return;
        banForm.post(ban(banTarget.id).url, {
            preserveScroll: true,
            onFinish: () => {
                setBanTarget(null);
                banForm.reset();
            },
        });
    };

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.post(deleteMethod(deleteTarget.id).url, {}, {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users - Admin" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">
                            Users
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Manage forum users ({users.total} total)
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={create().url}>
                            <Plus className="mr-2 size-4" />
                            New User
                        </Link>
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Username</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="w-[70px]">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No users found.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                users.data.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell className="font-medium">
                                            {user.display_name}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {user.username}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {user.email}
                                        </TableCell>
                                        <TableCell>
                                            {roleBadge(user.role)}
                                        </TableCell>
                                        <TableCell>
                                            {statusBadge(user)}
                                        </TableCell>
                                        <TableCell>
                                            {user.role !== 'admin' && !user.is_deleted && (
                                                <DropdownMenu>
                                                    <DropdownMenuTrigger asChild>
                                                        <Button variant="ghost" size="icon">
                                                            <MoreHorizontal className="size-4" />
                                                        </Button>
                                                    </DropdownMenuTrigger>
                                                    <DropdownMenuContent align="end">
                                                        {user.is_suspended ? (
                                                            <DropdownMenuItem onClick={() => handleUnsuspend(user)}>
                                                                <UserCheck className="mr-2 size-4" />
                                                                Unsuspend
                                                            </DropdownMenuItem>
                                                        ) : (
                                                            <DropdownMenuItem onClick={() => setSuspendTarget(user)}>
                                                                <UserX className="mr-2 size-4" />
                                                                Suspend
                                                            </DropdownMenuItem>
                                                        )}
                                                        <DropdownMenuSeparator />
                                                        <DropdownMenuItem
                                                            onClick={() => setBanTarget(user)}
                                                            className="text-destructive"
                                                        >
                                                            <Ban className="mr-2 size-4" />
                                                            Ban
                                                        </DropdownMenuItem>
                                                        <DropdownMenuItem
                                                            onClick={() => setDeleteTarget(user)}
                                                            className="text-destructive"
                                                        >
                                                            <Trash2 className="mr-2 size-4" />
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuContent>
                                                </DropdownMenu>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>

                {users.last_page > 1 && (
                    <div className="flex items-center justify-center gap-2">
                        {users.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                asChild={!!link.url}
                            >
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>

            {/* Suspend Confirmation */}
            <AlertDialog
                open={!!suspendTarget}
                onOpenChange={() => setSuspendTarget(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Suspend User</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to suspend &ldquo;
                            {suspendTarget?.display_name}&rdquo;? They will not be
                            able to create discussions or replies.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleSuspend}>
                            Suspend
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Ban Confirmation */}
            <AlertDialog
                open={!!banTarget}
                onOpenChange={() => {
                    setBanTarget(null);
                    banForm.reset();
                }}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Ban User</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to ban &ldquo;
                            {banTarget?.display_name}&rdquo;? Their account will be
                            anonymized and their email will be blocked from
                            registering again. This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <div className="grid gap-2 py-2">
                        <Label htmlFor="ban-reason">Reason (optional)</Label>
                        <textarea
                            id="ban-reason"
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring block w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                            value={banForm.data.reason}
                            onChange={(e) => banForm.setData('reason', e.target.value)}
                            rows={3}
                            placeholder="Reason for banning this user..."
                        />
                    </div>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={handleBan}
                            disabled={banForm.processing}
                        >
                            Ban User
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

            {/* Delete Confirmation */}
            <AlertDialog
                open={!!deleteTarget}
                onOpenChange={() => setDeleteTarget(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete User</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete &ldquo;
                            {deleteTarget?.display_name}&rdquo;? Their account will
                            be anonymized but their email will not be banned. This
                            action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={handleDelete}>
                            Delete
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </AppLayout>
    );
}
