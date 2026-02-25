import { CircleHelp } from 'lucide-react';
import { getIconComponent } from '@/lib/lucide-icons';
import { cn } from '@/lib/utils';

export function DynamicIcon({
    name,
    className,
    fallback = true,
}: {
    name: string | null | undefined;
    className?: string;
    fallback?: boolean;
}) {
    const IconComponent = getIconComponent(name);

    if (IconComponent) {
        return <IconComponent className={cn('size-4', className)} />;
    }

    if (fallback) {
        return <CircleHelp className={cn('size-4', className)} />;
    }

    return null;
}
