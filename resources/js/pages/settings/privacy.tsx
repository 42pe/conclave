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
import { edit, update } from '@/routes/privacy';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Privacy settings',
        href: edit().url,
    },
];

export default function Privacy() {
    const { auth } = usePage().props;

    const { data, setData, patch, processing, recentlySuccessful } = useForm({
        show_real_name: auth.user.show_real_name,
        show_email: auth.user.show_email,
        show_in_directory: auth.user.show_in_directory,
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        patch(update().url, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Privacy settings" />

            <h1 className="sr-only">Privacy Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Privacy settings"
                        description="Control what information is visible on your profile"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <Label
                                    htmlFor="show_real_name"
                                    className="flex-1 cursor-pointer"
                                >
                                    <div>Show your real name on your profile</div>
                                    <p className="text-sm font-normal text-muted-foreground">
                                        When disabled, only your username will be
                                        visible
                                    </p>
                                </Label>
                                <Switch
                                    id="show_real_name"
                                    checked={data.show_real_name}
                                    onCheckedChange={(checked) =>
                                        setData('show_real_name', checked)
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <Label
                                    htmlFor="show_email"
                                    className="flex-1 cursor-pointer"
                                >
                                    <div>
                                        Show your email address on your profile
                                    </div>
                                    <p className="text-sm font-normal text-muted-foreground">
                                        When disabled, your email will be hidden
                                        from other users
                                    </p>
                                </Label>
                                <Switch
                                    id="show_email"
                                    checked={data.show_email}
                                    onCheckedChange={(checked) =>
                                        setData('show_email', checked)
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <Label
                                    htmlFor="show_in_directory"
                                    className="flex-1 cursor-pointer"
                                >
                                    <div>Appear in the user directory</div>
                                    <p className="text-sm font-normal text-muted-foreground">
                                        When disabled, your profile will not be
                                        listed in the directory
                                    </p>
                                </Label>
                                <Switch
                                    id="show_in_directory"
                                    checked={data.show_in_directory}
                                    onCheckedChange={(checked) =>
                                        setData('show_in_directory', checked)
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
