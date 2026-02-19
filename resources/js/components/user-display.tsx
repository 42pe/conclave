import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import type { User } from '@/types/auth';

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export default function UserDisplay({
    user,
    showAvatar = true,
    size = 'default',
    className,
}: {
    user: Pick<
        User,
        'display_name' | 'username' | 'avatar_path' | 'is_deleted'
    >;
    showAvatar?: boolean;
    size?: 'sm' | 'default' | 'lg';
    className?: string;
}) {
    const sizeClasses = {
        sm: 'size-6 text-xs',
        default: 'size-8 text-sm',
        lg: 'size-10 text-base',
    };

    if (user.is_deleted) {
        return (
            <span
                className={cn(
                    'inline-flex items-center gap-2 text-muted-foreground italic',
                    className,
                )}
            >
                {showAvatar && (
                    <Avatar className={sizeClasses[size]}>
                        <AvatarFallback>?</AvatarFallback>
                    </Avatar>
                )}
                <span>Deleted User</span>
            </span>
        );
    }

    return (
        <span className={cn('inline-flex items-center gap-2', className)}>
            {showAvatar && (
                <Avatar className={sizeClasses[size]}>
                    {user.avatar_path && (
                        <AvatarImage
                            src={`/storage/${user.avatar_path}`}
                            alt={user.display_name}
                        />
                    )}
                    <AvatarFallback>
                        {getInitials(user.display_name)}
                    </AvatarFallback>
                </Avatar>
            )}
            <span>{user.display_name}</span>
        </span>
    );
}
