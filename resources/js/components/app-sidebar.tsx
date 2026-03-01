import { Link, usePage } from '@inertiajs/react';
import { Bookmark, BookOpen, Folder, LayoutGrid, Mail, MessageCircle, MessageSquare, Shield, Users } from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { NotificationPanel } from '@/components/notification-panel';
import { Badge } from '@/components/ui/badge';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuBadge,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { GuestAuth, NavItem } from '@/types';
import AppLogo from './app-logo';
import { dashboard } from '@/routes';
import { index as adminTopicsIndex } from '@/routes/admin/topics';
import { index as adminUsersIndex } from '@/routes/admin/users';
import { index as directoryIndex } from '@/routes/directory';

const guestNavItems: NavItem[] = [
    {
        title: 'Forum',
        href: '/',
        icon: MessageCircle,
    },
    {
        title: 'Directory',
        href: directoryIndex().url,
        icon: Users,
    },
];

const authNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Forum',
        href: '/',
        icon: MessageCircle,
    },
    {
        title: 'Directory',
        href: directoryIndex().url,
        icon: Users,
    },
    {
        title: 'Bookmarks',
        href: '/bookmarks',
        icon: Bookmark,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Topics',
        href: adminTopicsIndex(),
        icon: MessageSquare,
    },
    {
        title: 'Users',
        href: adminUsersIndex(),
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth, unread_messages_count, unread_notifications_count } = usePage<{
        auth: GuestAuth;
        unread_messages_count: number;
        unread_notifications_count: number;
    }>().props;
    const { isCurrentUrl } = useCurrentUrl();
    const user = auth.user;
    const isAdmin = user?.role === 'admin';

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={user ? authNavItems : guestNavItems} />

                {user && (
                    <SidebarGroup className="px-2 py-0">
                        <SidebarMenu>
                            <SidebarMenuItem>
                                <SidebarMenuButton
                                    asChild
                                    isActive={isCurrentUrl('/messages')}
                                    tooltip={{ children: 'Messages' }}
                                >
                                    <Link href="/messages" prefetch>
                                        <Mail />
                                        <span>Messages</span>
                                    </Link>
                                </SidebarMenuButton>
                                {unread_messages_count > 0 && (
                                    <SidebarMenuBadge>{unread_messages_count}</SidebarMenuBadge>
                                )}
                            </SidebarMenuItem>
                            <NotificationPanel unreadCount={unread_notifications_count} />
                        </SidebarMenu>
                    </SidebarGroup>
                )}

                {isAdmin && (
                    <SidebarGroup className="px-2 py-0">
                        <SidebarGroupLabel>
                            <Shield className="mr-1 size-3" />
                            Admin
                        </SidebarGroupLabel>
                        <SidebarMenu>
                            {adminNavItems.map((item) => (
                                <SidebarMenuItem key={item.title}>
                                    <SidebarMenuButton
                                        asChild
                                        isActive={isCurrentUrl(item.href)}
                                        tooltip={{ children: item.title }}
                                    >
                                        <Link href={item.href} prefetch>
                                            {item.icon && <item.icon />}
                                            <span>{item.title}</span>
                                        </Link>
                                    </SidebarMenuButton>
                                </SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                {user && <NavUser />}
            </SidebarFooter>
        </Sidebar>
    );
}
