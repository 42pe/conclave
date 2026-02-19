import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Trash2 } from 'lucide-react';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import AdminLayout from '@/layouts/admin/layout';
import type { BreadcrumbItem, Topic, TopicVisibility } from '@/types';
import {
    index as topicsIndex,
    create as topicsCreate,
    edit as topicsEdit,
    destroy as topicsDestroy,
} from '@/routes/admin/topics';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: topicsIndex().url },
    { title: 'Topics', href: topicsIndex().url },
];

const visibilityBadge: Record<
    TopicVisibility,
    'default' | 'secondary' | 'destructive'
> = {
    public: 'default',
    private: 'secondary',
    restricted: 'destructive',
};

export default function TopicsIndex({ topics }: { topics: Topic[] }) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    function handleDelete(id: number) {
        router.delete(topicsDestroy(id).url, {
            onFinish: () => setDeletingId(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Topics - Admin" />

            <AdminLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Topics"
                            description="Manage forum topics"
                        />
                        <Button asChild size="sm">
                            <Link href={topicsCreate()}>
                                <Plus className="mr-1 h-4 w-4" />
                                Create topic
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Title</TableHead>
                                <TableHead>Slug</TableHead>
                                <TableHead>Visibility</TableHead>
                                <TableHead className="text-right">
                                    Sort Order
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {topics.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={5}
                                        className="text-center text-muted-foreground"
                                    >
                                        No topics yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {topics.map((topic) => (
                                <TableRow key={topic.id}>
                                    <TableCell className="font-medium">
                                        {topic.title}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {topic.slug}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                visibilityBadge[
                                                    topic.visibility
                                                ]
                                            }
                                        >
                                            {topic.visibility}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {topic.sort_order}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex items-center justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={topicsEdit(topic)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    <span className="sr-only">
                                                        Edit
                                                    </span>
                                                </Link>
                                            </Button>

                                            <Dialog
                                                open={
                                                    deletingId === topic.id
                                                }
                                                onOpenChange={(open) =>
                                                    setDeletingId(
                                                        open
                                                            ? topic.id
                                                            : null,
                                                    )
                                                }
                                            >
                                                <DialogTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                        <span className="sr-only">
                                                            Delete
                                                        </span>
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>
                                                            Delete topic
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Are you sure you
                                                            want to delete
                                                            &ldquo;
                                                            {topic.title}
                                                            &rdquo;? This
                                                            action cannot be
                                                            undone.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <DialogFooter>
                                                        <DialogClose asChild>
                                                            <Button variant="outline">
                                                                Cancel
                                                            </Button>
                                                        </DialogClose>
                                                        <Button
                                                            variant="destructive"
                                                            onClick={() =>
                                                                handleDelete(
                                                                    topic.id,
                                                                )
                                                            }
                                                        >
                                                            Delete
                                                        </Button>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
