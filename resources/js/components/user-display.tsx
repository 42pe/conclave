import { cn } from '@/lib/utils';

interface UserDisplayProps {
    user: {
        display_name: string;
        is_deleted: boolean;
    };
    className?: string;
}

export default function UserDisplay({ user, className }: UserDisplayProps) {
    return (
        <span
            className={cn(
                user.is_deleted && 'italic text-muted-foreground',
                className,
            )}
        >
            {user.display_name}
        </span>
    );
}
