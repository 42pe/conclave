import { Transition } from '@headlessui/react';
import { Head, router, usePage } from '@inertiajs/react';
import { type FormEvent, useState } from 'react';
import Heading from '@/components/heading';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
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
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [notifyReplies, setNotifyReplies] = useState(auth.user.notify_replies);
    const [notifyMessages, setNotifyMessages] = useState(
        auth.user.notify_messages,
    );

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.patch(
            update.url(),
            {
                notify_replies: notifyReplies,
                notify_messages: notifyMessages,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setRecentlySuccessful(true);
                    setTimeout(() => setRecentlySuccessful(false), 2000);
                },
                onFinish: () => setProcessing(false),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Notification settings"
                        description="Choose which email notifications you'd like to receive"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="notify_replies"
                                    checked={notifyReplies}
                                    onCheckedChange={(checked) =>
                                        setNotifyReplies(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="notify_replies">
                                        Reply notifications
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Receive an email when someone replies to
                                        your discussions.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="notify_messages"
                                    checked={notifyMessages}
                                    onCheckedChange={(checked) =>
                                        setNotifyMessages(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="notify_messages">
                                        Message notifications
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Receive an email when someone sends you
                                        a private message.
                                    </p>
                                </div>
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
