import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import type { Descendant } from 'slate';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { SlateEditor, EMPTY_DOCUMENT } from '@/components/slate-editor';
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
import type { BreadcrumbItem, LocationItem, Topic } from '@/types';

interface DiscussionCreateProps {
    topic: Topic;
    locations: LocationItem[];
}

type SlateNode = Record<string, string | boolean | number | null | SlateNode[]>;

type DiscussionFormData = {
    topic_id: number;
    location_id: string;
    title: string;
    body: SlateNode[];
};

export default function DiscussionCreate({
    topic,
    locations,
}: DiscussionCreateProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Home', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
        {
            title: 'Create Discussion',
            href: `/topics/${topic.slug}/discussions/create`,
        },
    ];

    const { data, setData, post, processing, errors } =
        useForm<DiscussionFormData>({
            topic_id: topic.id,
            location_id: '',
            title: '',
            body: EMPTY_DOCUMENT as unknown as SlateNode[],
        });

    function handleSubmit(e: FormEvent) {
        e.preventDefault();
        post('/discussions');
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`New Discussion - ${topic.title}`} />

            <div className="mx-auto w-full max-w-2xl space-y-6 p-4 lg:p-6">
                <Heading
                    title="Create Discussion"
                    description={`Start a new discussion in ${topic.title}`}
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
                            Create Discussion
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/topics/${topic.slug}`}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
