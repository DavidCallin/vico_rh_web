import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    BookOpen,
    CalendarOff,
    FileText,
    FolderGit2,
    LayoutGrid,
    Palmtree,
    ScrollText,
    Wallet,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

function navigation(unreadNotifications: number, isHr: boolean): NavItem[] {
    const items: NavItem[] = [
        {
            title: 'Tableau de bord',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Congés',
            href: '/conges',
            icon: Palmtree,
        },
        {
            title: 'Absences',
            href: '/absences',
            icon: CalendarOff,
        },
        {
            title: 'Paie',
            href: '/paie',
            icon: Wallet,
        },
        {
            title: 'Bulletins',
            href: '/bulletins',
            icon: FileText,
        },
        {
            title: 'Contrats',
            href: '/contrats',
            icon: ScrollText,
        },
        {
            title:
                unreadNotifications > 0
                    ? `Notifications (${unreadNotifications})`
                    : 'Notifications',
            href: '/notifications',
            icon: Bell,
        },
    ];

    return isHr ? items : items.filter((item) => item.href !== '/paie');
}

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth, unreadNotifications } = usePage().props;
    const isHr = auth.user?.is_hr === true;

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
                <NavMain items={navigation(unreadNotifications, isHr)} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
