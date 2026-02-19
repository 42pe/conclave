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
import type { BreadcrumbItem, Topic } from '@/types';
import {
    index as topicsIndex,
    edit as topicsEdit,
    update as topicsUpdate,
} from '@/routes/admin/topics';
import type { FormEvent } from 'react';

export default function TopicsEdit({ topic }: { topic: Topic }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: topicsIndex().url },
        { title: 'Topics', href: topicsIndex().url },
        { title: topic.title, href: topicsEdit(topic).url },
    ];

    const { data, setData, post, processing, errors } = useForm<{
        _method: string;
        title: string;
        description: string;
        icon: string;
        visibility: string;
        sort_order: string;
        header_image: File | null;
    }>({
        _method: 'PATCH',
        title: topic.title,
        description: topic.description ?? '',
        icon: topic.icon ?? '',
        visibility: topic.visibility,
        sort_order: String(topic.sort_order),
        header_image: null,
    });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(topicsUpdate(topic).url, {
            forceFormData: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${topic.title} - Admin`} />

            <AdminLayout>
                <div className="max-w-xl space-y-6">
                    <Heading
                        variant="small"
                        title="Edit topic"
                        description={`Editing "${topic.title}"`}
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

                        {topic.header_image_path && (
                            <div className="grid gap-2">
                                <Label>Current Header Image</Label>
                                <img
                                    src={`/storage/${topic.header_image_path}`}
                                    alt={`${topic.title} header`}
                                    className="h-32 w-auto rounded-md border object-cover"
                                />
                            </div>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Update topic</Button>
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
