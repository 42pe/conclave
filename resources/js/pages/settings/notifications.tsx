import { Transition } from '@headlessui/react';
import { Head, usePage } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import { edit, update } from '@/routes/notifications';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notification settings',
        href: edit().url,
    },
];

export default function Notifications() {
    const { auth } = usePage().props;

    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        notify_replies: auth.user.notify_replies,
        notify_messages: auth.user.notify_messages,
        notify_mentions: auth.user.notify_mentions,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch(update().url, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Notification settings"
                        description="Choose what email notifications you receive"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <Label
                                    htmlFor="notify_replies"
                                    className="flex-1 cursor-pointer"
                                >
                                    <div>Email on new replies</div>
                                    <p className="text-sm font-normal text-muted-foreground">
                                        Receive an email when someone replies to
                                        your discussions or replies
                                    </p>
                                </Label>
                                <Switch
                                    id="notify_replies"
                                    checked={data.notify_replies}
                                    onCheckedChange={(checked) =>
                                        setData('notify_replies', checked)
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <Label
                                    htmlFor="notify_messages"
                                    className="flex-1 cursor-pointer"
                                >
                                    <div>Email on new messages</div>
                                    <p className="text-sm font-normal text-muted-foreground">
                                        Receive an email when someone sends you
                                        a private message
                                    </p>
                                </Label>
                                <Switch
                                    id="notify_messages"
                                    checked={data.notify_messages}
                                    onCheckedChange={(checked) =>
                                        setData('notify_messages', checked)
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <Label
                                    htmlFor="notify_mentions"
                                    className="flex-1 cursor-pointer"
                                >
                                    <div>Email on mentions</div>
                                    <p className="text-sm font-normal text-muted-foreground">
                                        Receive an email when someone mentions
                                        you in a discussion or reply
                                    </p>
                                </Label>
                                <Switch
                                    id="notify_mentions"
                                    checked={data.notify_mentions}
                                    onCheckedChange={(checked) =>
                                        setData('notify_mentions', checked)
                                    }
                                />
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>Save</Button>

                            <Transition
                                show={recentlySuccessful}
                                enter="transition ease-in-out"
                                enterFrom="opacity-0"
                                leave="transition ease-in-out"
                                leaveTo="opacity-0"
                            >
                                <p className="text-sm text-neutral-600">
                                    Saved
                                </p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
