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
import { update } from '@/routes/privacy';
import { edit } from '@/routes/privacy';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Privacy settings',
        href: edit().url,
    },
];

export default function Privacy() {
    const { auth } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const [recentlySuccessful, setRecentlySuccessful] = useState(false);
    const [showRealName, setShowRealName] = useState(auth.user.show_real_name);
    const [showEmail, setShowEmail] = useState(auth.user.show_email);
    const [showInDirectory, setShowInDirectory] = useState(
        auth.user.show_in_directory,
    );

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.patch(
            update.url(),
            {
                show_real_name: showRealName,
                show_email: showEmail,
                show_in_directory: showInDirectory,
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
            <Head title="Privacy settings" />

            <h1 className="sr-only">Privacy Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Privacy settings"
                        description="Control what information is visible to other users"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="show_real_name"
                                    checked={showRealName}
                                    onCheckedChange={(checked) =>
                                        setShowRealName(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="show_real_name">
                                        Show real name
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Display your real name on your profile
                                        alongside your username.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="show_email"
                                    checked={showEmail}
                                    onCheckedChange={(checked) =>
                                        setShowEmail(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="show_email">
                                        Show email address
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Allow other users to see your email
                                        address on your profile.
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="show_in_directory"
                                    checked={showInDirectory}
                                    onCheckedChange={(checked) =>
                                        setShowInDirectory(checked === true)
                                    }
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="show_in_directory">
                                        Appear in user directory
                                    </Label>
                                    <p className="text-sm text-muted-foreground">
                                        Show your profile in the public user
                                        directory.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button
                                disabled={processing}
                                data-test="update-privacy-button"
                            >
                                Save
                            </Button>

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
