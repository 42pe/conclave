import { Head, Link, useForm } from '@inertiajs/react';
import type { Descendant } from 'slate';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { SlateEditor } from '@/components/slate-editor/editor';
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

type Location = {
    id: number;
    name: string;
};

type Topic = {
    id: number;
    title: string;
    slug: string;
};

type Discussion = {
    id: number;
    title: string;
    slug: string;
    body: Descendant[];
    location_id: number | null;
};

type Props = {
    topic: Topic;
    discussion: Discussion;
    locations: Location[];
};

export default function DiscussionEdit({
    topic,
    discussion,
    locations,
}: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Forum', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
        {
            title: discussion.title,
            href: `/topics/${topic.slug}/discussions/${discussion.slug}`,
        },
        {
            title: 'Edit',
            href: `/topics/${topic.slug}/discussions/${discussion.slug}/edit`,
        },
    ];

    const { data, setData, patch, processing, errors } = useForm<{
        title: string;
        body: string;
        location_id: string;
    }>({
        title: discussion.title,
        body: JSON.stringify(discussion.body),
        location_id: discussion.location_id
            ? String(discussion.location_id)
            : '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch(`/topics/${topic.slug}/discussions/${discussion.slug}`);
    };

    const handleEditorChange = (value: Descendant[]) => {
        setData('body', JSON.stringify(value));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Discussion - ${topic.title}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <Heading
                    title="Edit Discussion"
                    description={`Editing "${discussion.title}"`}
                />

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                            placeholder="Discussion title"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label>Body</Label>
                        <SlateEditor
                            initialValue={discussion.body}
                            onChange={handleEditorChange}
                            placeholder="Write your discussion content..."
                        />
                        <InputError message={errors.body} />
                    </div>

                    {locations.length > 0 && (
                        <div className="grid gap-2">
                            <Label htmlFor="location_id">
                                Location (optional)
                            </Label>
                            <Select
                                value={data.location_id || 'none'}
                                onValueChange={(value) =>
                                    setData(
                                        'location_id',
                                        value === 'none' ? '' : value,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a location" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        No location
                                    </SelectItem>
                                    {locations.map((location) => (
                                        <SelectItem
                                            key={location.id}
                                            value={String(location.id)}
                                        >
                                            {location.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.location_id} />
                        </div>
                    )}

                    <div className="flex items-center gap-4">
                        <Button disabled={processing}>
                            Update Discussion
                        </Button>
                        <Button variant="outline" asChild>
                            <Link
                                href={`/topics/${topic.slug}/discussions/${discussion.slug}`}
                            >
                                Cancel
                            </Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
