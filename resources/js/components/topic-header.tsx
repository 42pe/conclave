import { Badge } from '@/components/ui/badge';

type Topic = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    icon: string | null;
    header_image_path: string | null;
    visibility: 'public' | 'private' | 'restricted';
};

export function TopicHeader({ topic }: { topic: Topic }) {
    return (
        <div className="space-y-2">
            <div className="flex items-center gap-3">
                {topic.icon && (
                    <span className="text-2xl text-muted-foreground">
                        {topic.icon}
                    </span>
                )}
                <div>
                    <div className="flex items-center gap-2">
                        <h1 className="text-2xl font-bold tracking-tight">
                            {topic.title}
                        </h1>
                        {topic.visibility !== 'public' && (
                            <Badge
                                variant={
                                    topic.visibility === 'restricted'
                                        ? 'secondary'
                                        : 'destructive'
                                }
                            >
                                {topic.visibility}
                            </Badge>
                        )}
                    </div>
                    {topic.description && (
                        <p className="text-sm text-muted-foreground">
                            {topic.description}
                        </p>
                    )}
                </div>
            </div>
        </div>
    );
}
