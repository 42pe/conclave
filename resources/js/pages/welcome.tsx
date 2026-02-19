import { Head, Link, usePage } from '@inertiajs/react';
import { MessageSquare } from 'lucide-react';
import { getIconComponent } from '@/components/icon-picker';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';
import type { Topic, TopicVisibility } from '@/types';

const visibilityVariant: Record<
    TopicVisibility,
    'default' | 'secondary' | 'destructive'
> = {
    public: 'default',
    private: 'secondary',
    restricted: 'destructive',
};

export default function Welcome({
    canRegister = true,
    topics = [],
}: {
    canRegister?: boolean;
    topics?: Topic[];
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
                <header className="w-full px-6 py-4 lg:px-8">
                    <nav className="mx-auto flex max-w-5xl items-center justify-end gap-4">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                                >
                                    Log in
                                </Link>
                                {canRegister && (
                                    <Link
                                        href={register()}
                                        className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                                    >
                                        Register
                                    </Link>
                                )}
                            </>
                        )}
                    </nav>
                </header>

                <main className="mx-auto w-full max-w-5xl flex-1 px-6 py-8 lg:px-8">
                    <h1 className="mb-8 text-2xl font-semibold tracking-tight">
                        Topics
                    </h1>

                    {topics.length === 0 ? (
                        <p className="py-12 text-center text-muted-foreground">
                            No topics yet.
                        </p>
                    ) : (
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {topics.map((topic) => {
                                const TopicIcon = topic.icon
                                    ? getIconComponent(topic.icon)
                                    : null;

                                return (
                                    <Link
                                        key={topic.id}
                                        href={`/topics/${topic.slug}`}
                                        className="block"
                                    >
                                        <Card className="h-full transition-colors hover:bg-muted/50">
                                            <CardHeader>
                                                <div className="flex items-start justify-between gap-2">
                                                    <div className="flex items-center gap-3">
                                                        {TopicIcon && (
                                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-muted">
                                                                <TopicIcon className="h-4 w-4 text-muted-foreground" />
                                                            </div>
                                                        )}
                                                        <CardTitle className="text-base">
                                                            {topic.title}
                                                        </CardTitle>
                                                    </div>
                                                    <Badge
                                                        variant={
                                                            visibilityVariant[
                                                                topic.visibility
                                                            ]
                                                        }
                                                        className="shrink-0"
                                                    >
                                                        {topic.visibility}
                                                    </Badge>
                                                </div>
                                                {topic.description && (
                                                    <CardDescription className="line-clamp-2">
                                                        {topic.description}
                                                    </CardDescription>
                                                )}
                                            </CardHeader>
                                            <CardContent>
                                                <div className="flex items-center gap-1 text-sm text-muted-foreground">
                                                    <MessageSquare className="h-4 w-4" />
                                                    <span>
                                                        {topic.discussions_count ?? 0}{' '}
                                                        discussions
                                                    </span>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </Link>
                                );
                            })}
                        </div>
                    )}
                </main>
            </div>
        </>
    );
}
