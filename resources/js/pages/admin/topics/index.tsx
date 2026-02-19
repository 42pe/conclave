import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
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
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import TopicController from '@/actions/App/Http/Controllers/Admin/TopicController';
import { index } from '@/routes/admin/topics';

type Topic = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    icon: string | null;
    visibility: 'public' | 'private' | 'restricted';
    sort_order: number;
    created_by: number;
    creator?: {
        id: number;
        name: string;
        username: string;
    };
    created_at: string;
    updated_at: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: index().url,
    },
    {
        title: 'Topics',
        href: index().url,
    },
];

const visibilityVariant = (visibility: string) => {
    switch (visibility) {
        case 'public':
            return 'default';
        case 'private':
            return 'destructive';
        case 'restricted':
            return 'secondary';
        default:
            return 'outline';
    }
};

export default function TopicsIndex({ topics }: { topics: Topic[] }) {
    const [deleteTarget, setDeleteTarget] = useState<Topic | null>(null);

    const handleDelete = () => {
        if (!deleteTarget) return;
        router.delete(TopicController.destroy(deleteTarget.id).url, {
            preserveScroll: true,
            onFinish: () => setDeleteTarget(null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Topics - Admin" />

            <div className="space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold tracking-tight">
                            Topics
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Manage forum discussion topics
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={TopicController.create().url}>
                            <Plus className="mr-2 size-4" />
                            New Topic
                        </Link>
                    </Button>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Visibility</TableHead>
                                <TableHead className="text-right">
                                    Sort Order
                                </TableHead>
                                <TableHead className="w-[100px]">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {topics.length === 0 ? (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="py-8 text-center text-muted-foreground"
                                    >
                                        No topics found. Create your first
                                        topic.
                                    </TableCell>
                                </TableRow>
                            ) : (
                                topics.map((topic) => (
                                    <TableRow key={topic.id}>
                                        <TableCell className="font-medium">
                                            {topic.icon && (
                                                <span className="mr-2">
                                                    {topic.icon}
                                                </span>
                                            )}
                                            {topic.title}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {topic.slug}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={visibilityVariant(
                                                    topic.visibility,
                                                )}
                                            >
                                                {topic.visibility}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {topic.sort_order}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    asChild
                                                >
                                                    <Link
                                                        href={
                                                            TopicController.edit(
                                                                topic.id,
                                                            ).url
                                                        }
                                                    >
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        setDeleteTarget(topic)
                                                    }
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                            )}
                        </TableBody>
                    </Table>
                </div>
            </div>

            <AlertDialog
                open={!!deleteTarget}
                onOpenChange={() => setDeleteTarget(null)}
            >
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete Topic</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to delete &ldquo;
                            {deleteTarget?.title}&rdquo;? This action cannot be
                            undone.
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
