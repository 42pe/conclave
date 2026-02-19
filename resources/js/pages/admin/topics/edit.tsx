import { Head, Link } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
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
};

export default function TopicEdit({ topic }: { topic: Topic }) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Admin',
            href: index().url,
        },
        {
            title: 'Topics',
            href: index().url,
        },
        {
            title: `Edit: ${topic.title}`,
            href: TopicController.edit(topic.id).url,
        },
    ];

    const { data, setData, put, processing, errors } = useForm({
        title: topic.title,
        description: topic.description ?? '',
        icon: topic.icon ?? '',
        visibility: topic.visibility,
        sort_order: topic.sort_order,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(TopicController.update(topic.id).url);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${topic.title} - Admin`} />

            <div className="mx-auto max-w-2xl space-y-6 p-6">
                <Heading
                    title="Edit Topic"
                    description={`Editing "${topic.title}"`}
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                            placeholder="Topic title"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea
                            id="description"
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring block w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            rows={3}
                            placeholder="Brief description of this topic"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="icon">Icon</Label>
                        <Input
                            id="icon"
                            value={data.icon}
                            onChange={(e) => setData('icon', e.target.value)}
                            placeholder="Icon name (e.g., MessageCircle)"
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
                            <SelectTrigger>
                                <SelectValue placeholder="Select visibility" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="public">Public</SelectItem>
                                <SelectItem value="private">Private</SelectItem>
                                <SelectItem value="restricted">
                                    Restricted
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.visibility} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Sort Order</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            min={0}
                            value={data.sort_order}
                            onChange={(e) =>
                                setData('sort_order', parseInt(e.target.value) || 0)
                            }
                            placeholder="0"
                        />
                        <InputError message={errors.sort_order} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button disabled={processing}>Update Topic</Button>
                        <Button variant="outline" asChild>
                            <Link href={index().url}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
