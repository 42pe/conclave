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
import type { AdminLocation, BreadcrumbItem, LocationType } from '@/types';
import {
    index as locationsIndex,
    create as locationsCreate,
    edit as locationsEdit,
    destroy as locationsDestroy,
} from '@/routes/admin/locations';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: locationsIndex().url },
    { title: 'Locations', href: locationsIndex().url },
];

const typeLabels: Record<LocationType, string> = {
    any: 'Any',
    us_state: 'US State',
    country: 'Country',
};

const typeBadgeVariant: Record<
    LocationType,
    'default' | 'secondary' | 'destructive'
> = {
    any: 'secondary',
    us_state: 'default',
    country: 'default',
};

export default function LocationsIndex({
    locations,
}: {
    locations: AdminLocation[];
}) {
    const [deletingId, setDeletingId] = useState<number | null>(null);

    function handleDelete(id: number) {
        router.delete(locationsDestroy(id).url, {
            onFinish: () => setDeletingId(null),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Locations - Admin" />

            <AdminLayout>
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <Heading
                            variant="small"
                            title="Locations"
                            description="Manage forum locations"
                        />
                        <Button asChild size="sm">
                            <Link href={locationsCreate()}>
                                <Plus className="mr-1 h-4 w-4" />
                                Create location
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>ISO Code</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead className="text-right">
                                    Sort Order
                                </TableHead>
                                <TableHead className="text-right">
                                    Actions
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {locations.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={6}
                                        className="text-center text-muted-foreground"
                                    >
                                        No locations yet.
                                    </TableCell>
                                </TableRow>
                            )}
                            {locations.map((location) => (
                                <TableRow key={location.id}>
                                    <TableCell className="font-medium">
                                        {location.name}
                                    </TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {location.iso_code}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                typeBadgeVariant[location.type]
                                            }
                                        >
                                            {typeLabels[location.type]}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                location.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {location.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {location.sort_order}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <div className="flex items-center justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                asChild
                                            >
                                                <Link
                                                    href={locationsEdit(
                                                        location,
                                                    )}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                    <span className="sr-only">
                                                        Edit
                                                    </span>
                                                </Link>
                                            </Button>

                                            <Dialog
                                                open={
                                                    deletingId === location.id
                                                }
                                                onOpenChange={(open) =>
                                                    setDeletingId(
                                                        open
                                                            ? location.id
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
                                                            Delete location
                                                        </DialogTitle>
                                                        <DialogDescription>
                                                            Are you sure you
                                                            want to delete
                                                            &ldquo;
                                                            {location.name}
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
                                                                    location.id,
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
