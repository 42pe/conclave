import { useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import InputError from '@/components/input-error';
import { store, destroy } from '@/routes/avatar';

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export default function AvatarUpload() {
    const { auth } = usePage().props;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setError(null);

        // Client-side validation
        const validTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/webp',
        ];
        if (!validTypes.includes(file.type)) {
            setError('Please select a JPG, PNG, or WebP image.');
            return;
        }
        if (file.size > 2 * 1024 * 1024) {
            setError('Image must be less than 2MB.');
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => setPreview(e.target?.result as string);
        reader.readAsDataURL(file);

        // Upload
        const formData = new FormData();
        formData.append('avatar', file);

        router.post(store.url(), formData, {
            forceFormData: true,
            preserveScroll: true,
            onError: (errors) => {
                setError(errors.avatar ?? 'Failed to upload avatar.');
                setPreview(null);
            },
            onSuccess: () => setPreview(null),
        });
    };

    const handleRemove = () => {
        setError(null);
        setPreview(null);
        router.delete(destroy.url(), {
            preserveScroll: true,
        });
    };

    const avatarSrc =
        preview ??
        (auth.user.avatar_path
            ? `/storage/${auth.user.avatar_path}`
            : undefined);

    return (
        <div className="flex items-center gap-4">
            <Avatar className="size-16 text-lg">
                {avatarSrc && (
                    <AvatarImage
                        src={avatarSrc}
                        alt={auth.user.display_name}
                    />
                )}
                <AvatarFallback>
                    {getInitials(auth.user.display_name)}
                </AvatarFallback>
            </Avatar>

            <div className="flex flex-col gap-1">
                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => fileInputRef.current?.click()}
                    >
                        Change avatar
                    </Button>
                    {auth.user.avatar_path && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={handleRemove}
                        >
                            Remove
                        </Button>
                    )}
                </div>
                <p className="text-xs text-muted-foreground">
                    JPG, PNG or WebP. Max 2MB.
                </p>
                <InputError message={error ?? undefined} />
            </div>

            <input
                ref={fileInputRef}
                type="file"
                accept="image/jpeg,image/png,image/webp"
                className="hidden"
                onChange={handleFileChange}
            />
        </div>
    );
}
