import { Link } from '@inertiajs/react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

type UserCardUser = {
    id: number;
    username: string;
    name: string;
    preferred_name: string | null;
    avatar_path: string | null;
    bio: string | null;
    role: 'admin' | 'moderator' | 'user';
    is_deleted: boolean;
};

function getDisplayName(user: UserCardUser): string {
    if (user.is_deleted) return 'Deleted User';
    return user.preferred_name ?? user.name;
}

function getInitials(name: string): string {
    return name
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
}

export function UserCard({ user }: { user: UserCardUser }) {
    const displayName = getDisplayName(user);
    const initials = getInitials(displayName);

    return (
        <Link href={`/users/${user.username}`} className="block">
            <Card className="transition-colors hover:bg-accent/50">
                <CardContent className="flex items-center gap-4">
                    <Avatar className="size-12">
                        {user.avatar_path && (
                            <AvatarImage
                                src={`/storage/${user.avatar_path}`}
                                alt={displayName}
                            />
                        )}
                        <AvatarFallback>{initials}</AvatarFallback>
                    </Avatar>
                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-2">
                            <span className="truncate font-medium">
                                {displayName}
                            </span>
                            {user.role !== 'user' && (
                                <Badge
                                    variant={
                                        user.role === 'admin'
                                            ? 'destructive'
                                            : 'secondary'
                                    }
                                    className="text-xs"
                                >
                                    {user.role}
                                </Badge>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            @{user.username}
                        </p>
                        {user.bio && (
                            <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                {user.bio}
                            </p>
                        )}
                    </div>
                </CardContent>
            </Card>
        </Link>
    );
}
