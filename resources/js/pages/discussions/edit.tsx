import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { Descendant } from 'slate';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { SlateEditor } from '@/components/slate-editor';
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
import type { BreadcrumbItem, Discussion, LocationItem, Topic } from '@/types';

interface DiscussionEditProps {
    topic: Topic;
    discussion: Discussion;
    locations: LocationItem[];
}

type SlateNode = Record<string, string | boolean | number | null | SlateNode[]>;

type DiscussionEditFormData = {
    _method: string;
    topic_id: number;
    location_id: string;
    title: string;
    body: SlateNode[];
};

export default function DiscussionEdit({
    topic,
    discussion,
    locations,
}: DiscussionEditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
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

    const { data, setData, post, processing, errors } =
        useForm<DiscussionEditFormData>({
            _method: 'PATCH',
            topic_id: discussion.topic_id,
            location_id: discussion.location_id
                ? String(discussion.location_id)
                : '',
            title: discussion.title,
            body: discussion.body as unknown as SlateNode[],
        });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post(`/topics/${topic.slug}/discussions/${discussion.slug}`);
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Discussion - ${discussion.title}`} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4 lg:p-6">
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
                        <Label htmlFor="location">Location (optional)</Label>
                        <Select
                            value={data.location_id}
                            onValueChange={(value) =>
                                setData(
                                    'location_id',
                                    value === 'none' ? '' : value,
                                )
                            }
                        >
                            <SelectTrigger id="location">
                                <SelectValue placeholder="Select a location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
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

                    <div className="grid gap-2">
                        <Label>Body</Label>
                        <SlateEditor
                            value={data.body as unknown as Descendant[]}
                            onChange={(value) =>
                                setData(
                                    'body',
                                    value as unknown as SlateNode[],
                                )
                            }
                            placeholder="Write your discussion..."
                        />
                        <InputError message={errors.body} />
                    </div>

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
