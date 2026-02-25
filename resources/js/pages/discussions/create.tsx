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
    description: string | null;
};

type Props = {
    topic: Topic;
    locations: Location[];
};

export default function DiscussionCreate({ topic, locations }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Forum', href: '/' },
        { title: topic.title, href: `/topics/${topic.slug}` },
        {
            title: 'New Discussion',
            href: `/topics/${topic.slug}/discussions/create`,
        },
    ];

    const { data, setData, post, processing, errors } = useForm<{
        title: string;
        body: string;
        location_id: string;
    }>({
        title: '',
        body: '',
        location_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/topics/${topic.slug}/discussions`);
    };

    const handleEditorChange = (value: Descendant[]) => {
        setData('body', JSON.stringify(value));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`New Discussion - ${topic.title}`} />

            <div className="mx-auto max-w-3xl space-y-6 p-6">
                <Heading
                    title="New Discussion"
                    description={`Create a new discussion in ${topic.title}`}
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
                            onChange={handleEditorChange}
                            placeholder="Write your discussion content..."
                            enableMentions
                        />
                        <InputError message={errors.body} />
                    </div>

                    {locations.length > 0 && (
                        <div className="grid gap-2">
                            <Label htmlFor="location_id">
                                Location (optional)
                            </Label>
                            <Select
                                value={data.location_id}
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
