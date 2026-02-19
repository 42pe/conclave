import { Head, Link, useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import IconPicker from '@/components/icon-picker';
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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import AdminLayout from '@/layouts/admin/layout';
import type { BreadcrumbItem } from '@/types';
import {
    index as topicsIndex,
    create as topicsCreate,
    store as topicsStore,
} from '@/routes/admin/topics';
import type { FormEvent } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: topicsIndex().url },
    { title: 'Topics', href: topicsIndex().url },
    { title: 'Create', href: topicsCreate().url },
];

export default function TopicsCreate({
    nextSortOrder,
}: {
    nextSortOrder: number;
}) {
    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        description: string;
        icon: string;
        visibility: string;
        sort_order: string;
        header_image: File | null;
    }>({
        title: '',
        description: '',
        icon: '',
        visibility: 'public',
        sort_order: String(nextSortOrder),
        header_image: null,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(topicsStore().url, {
            forceFormData: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Topic - Admin" />

            <AdminLayout>
                <div className="max-w-xl space-y-6">
                    <Heading
                        variant="small"
                        title="Create topic"
                        description="Add a new forum topic"
                    />

                    <form
                        onSubmit={handleSubmit}
                        className="space-y-6"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="title">Title</Label>
                            <Input
                                id="title"
                                value={data.title}
                                onChange={(e) =>
                                    setData('title', e.target.value)
                                }
                                required
                                placeholder="Topic title"
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={data.description}
                                onChange={(e) =>
                                    setData('description', e.target.value)
                                }
                                required
                                placeholder="Brief description of this topic"
                                rows={3}
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div className="grid gap-2">
                                <Label>Icon</Label>
                                <IconPicker
                                    value={data.icon}
                                    onChange={(val) => setData('icon', val)}
                                />
                                <InputError message={errors.icon} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="visibility">Visibility</Label>
                                <Select
                                    value={data.visibility}
                                    onValueChange={(value) =>
                                        setData('visibility', value)
                                    }
                                >
                                    <SelectTrigger id="visibility">
                                        <SelectValue placeholder="Select visibility" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="public">
                                            Public
                                        </SelectItem>
                                        <SelectItem value="private">
                                            Private
                                        </SelectItem>
                                        <SelectItem value="restricted">
                                            Restricted
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.visibility} />
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
                                <Label htmlFor="header_image">
                                    Header Image
                                </Label>
                                <Input
                                    id="header_image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(e) =>
                                        setData(
                                            'header_image',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                />
                                <InputError message={errors.header_image} />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Create topic</Button>
                            <Button variant="outline" asChild>
                                <Link href={topicsIndex()}>Cancel</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </AdminLayout>
        </AppLayout>
    );
}
