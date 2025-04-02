import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Area {
    id: number;
    name: string;
    name1: string;
     name2: string;
    guard_name: string;
    created_by: string;
    created_at: string;
    updated_at: string;
}

export interface Permission {
    id: number;
    name: string;
    name1: string;
    framework: string;
    guard_name: string;
    created_by: string;
    created_at: string;
    updated_at: string;
    // id: number;
    // name: string;
    // districts: string;
    // created_by: string;
    // created_at: string;
}

export interface Data {
    data: {
        data: any[];
        links: {
            first: string;
            last: string;
            next: string | null;
            prev: string | null;
        };
        meta: {
            current_page: number;
            from: number;
            last_page: number;
            per_page: number;
            to: number;
            total: number;
        };
    };
}


export type FormTypeDashboard = {
    msc: string[];
    areas: string[];
    districts: string[];
    startDate: string;
    endDate: string;
};

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    auth: {
        user: User;
    };
};
