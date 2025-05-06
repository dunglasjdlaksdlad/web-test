import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { AlertTriangle, BookOpen, Briefcase, ClipboardList, Cpu, Files, Folder, Home, Key, LayoutGrid, Lock, Map, MapPin, Shield, Users, Wrench } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'File Manager',
        url: '/filemanager',
        icon: Files,
    },
    {
        title: 'TTKV',
        url: '/areas',
        icon: MapPin,
    },
];

const contentNavItems: NavItem[] = [
    {
        title: 'SỰ CỐ GĐTT',
        url: '/gdtt',
        icon: AlertTriangle,
    },
    {
        title: 'SỰ CỐ TD',
        url: '/sctd',
        icon: Cpu,
    },
    {
        title: 'SỰ CỐ CDBR',
        url: '/cdbr',
        icon: Wrench,
    },
    {
        title: 'WO_TT',
        url: '/wott',
        icon: ClipboardList,
    },
    {
        title: 'WO_PAKH',
        url: '/pakh',
        icon: Briefcase,
    },
];

const userManagementNavItems: NavItem[] = [
    {
        title: 'Role',
        url: '/roles',
        icon: Shield,
    },
    {
        title: 'Permissions',
        url: '/permissions',
        icon: Lock,
    },
    {
        title: 'Users',
        url: '/users',
        icon: Users,
    },
];

export function AppSidebar() {
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
                <NavMain items={mainNavItems} label={'Dashboard & Reports'} />
                <br />
                <NavMain items={contentNavItems} label={'Content'} />
                <br />
                <NavMain items={userManagementNavItems} label={'User Management'} />
            </SidebarContent>

            <SidebarFooter>
                {/* <NavFooter items={footerNavItems} className="mt-auto" /> */}
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
