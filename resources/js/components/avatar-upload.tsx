import { router, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { store, destroy } from '@/routes/avatar';

export default function AvatarUpload({
    avatarPath,
    userName,
}: {
    avatarPath: string | null;
    userName: string;
}) {
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { setData, post, processing, errors, reset } = useForm<{
        avatar: File | null;
    }>({
        avatar: null,
    });

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0];
        if (file) {
            setData('avatar', file);
        }
    }

    function handleUpload(e: React.FormEvent) {
        e.preventDefault();
        post(store().url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                reset();
                if (fileInputRef.current) {
                    fileInputRef.current.value = '';
                }
            },
        });
    }

    function handleRemove() {
        router.delete(destroy().url, {
            preserveScroll: true,
        });
    }

    const initials = userName
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

    return (
        <div className="flex items-center gap-6">
            <div
                className={cn(
                    'flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full bg-muted text-xl font-semibold text-muted-foreground',
                )}
            >
                {avatarPath ? (
                    <img
                        src={`/storage/${avatarPath}`}
                        alt={userName}
                        className="h-full w-full object-cover"
                    />
                ) : (
                    <span>{initials}</span>
                )}
            </div>

            <div className="space-y-2">
                <form onSubmit={handleUpload} className="flex items-center gap-2">
                    <input
                        ref={fileInputRef}
                        type="file"
                        accept="image/jpg,image/jpeg,image/png,image/webp"
                        onChange={handleFileChange}
                        className="text-sm text-muted-foreground file:mr-2 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-primary-foreground hover:file:bg-primary/90"
                    />
                    <Button
                        type="submit"
                        size="sm"
                        disabled={processing}
                    >
                        Upload
                    </Button>
                </form>

                <InputError message={errors.avatar} />

                {avatarPath && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={handleRemove}
                        className="text-destructive hover:text-destructive"
                    >
                        Remove avatar
                    </Button>
                )}

                <p className="text-xs text-muted-foreground">
                    JPG, JPEG, PNG or WebP. Max 2MB.
                </p>
            </div>
        </div>
    );
}
