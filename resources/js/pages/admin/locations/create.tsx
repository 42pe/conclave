import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import AdminLayout from '@/layouts/admin/layout';
import type { BreadcrumbItem } from '@/types';
import {
    index as locationsIndex,
    create as locationsCreate,
    store as locationsStore,
} from '@/routes/admin/locations';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: locationsIndex().url },
    { title: 'Locations', href: locationsIndex().url },
    { title: 'Create', href: locationsCreate().url },
];

export default function LocationsCreate({
    nextSortOrder,
}: {
    nextSortOrder: number;
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        iso_code: '',
        type: 'us_state',
        is_active: true,
        sort_order: String(nextSortOrder),
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(locationsStore().url);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Location - Admin" />

            <AdminLayout>
                <div className="max-w-xl space-y-6">
                    <Heading
                        variant="small"
                        title="Create location"
                        description="Add a new forum location"
                    />

                    <form
                        onSubmit={handleSubmit}
                        className="space-y-6"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) =>
                                    setData('name', e.target.value)
                                }
                                required
                                placeholder="Location name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="iso_code">ISO Code</Label>
                                <Input
                                    id="iso_code"
                                    value={data.iso_code}
                                    onChange={(e) =>
                                        setData('iso_code', e.target.value)
                                    }
                                    required
                                    placeholder="US-CA"
                                    maxLength={10}
                                />
                                <InputError message={errors.iso_code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) =>
                                        setData('type', value)
                                    }
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="any">
                                            Any
                                        </SelectItem>
                                        <SelectItem value="us_state">
                                            US State
                                        </SelectItem>
                                        <SelectItem value="country">
                                            Country
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="sort_order">Sort Order</Label>
                                <Input
                                    id="sort_order"
                                    type="number"
                                    min="0"
                                    required
                                    value={data.sort_order}
                                    onChange={(e) =>
                                        setData('sort_order', e.target.value)
                                    }
                                />
                                <InputError message={errors.sort_order} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="is_active">Status</Label>
                                <Select
                                    value={data.is_active ? '1' : '0'}
                                    onValueChange={(value) =>
                                        setData('is_active', value === '1')
                                    }
                                >
                                    <SelectTrigger id="is_active">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">
                                            Active
                                        </SelectItem>
                                        <SelectItem value="0">
                                            Inactive
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.is_active} />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                Create location
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={locationsIndex()}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
