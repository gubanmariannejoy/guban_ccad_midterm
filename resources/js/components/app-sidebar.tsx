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
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Boxes, LayoutGrid, ReceiptText, Sprout, UsersRound } from 'lucide-react';
import AppLogo from './app-logo';

const dashboardNavItem: NavItem = {
    title: 'Dashboard',
    url: '/dashboard',
    icon: LayoutGrid,
};

const adminNavItems: NavItem[] = [
    {
        title: 'Plant Species',
        url: '/plant-species',
        icon: Sprout,
    },
    {
        title: 'Nursery Sales',
        url: '/nursery-sales',
        icon: ReceiptText,
    },
    {
        title: 'Inventory',
        url: '/plant-inventory',
        icon: Boxes,
    },
    {
        title: 'Buyers',
        url: '/buyers',
        icon: UsersRound,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const mainNavItems = auth.user.role === 'admin'
        ? [dashboardNavItem, ...adminNavItems]
        : [dashboardNavItem, { title: 'My Sales', url: '/nursery-sales', icon: ReceiptText }];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}