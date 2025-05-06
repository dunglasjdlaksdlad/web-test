import { Head, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { useMemo, useState } from 'react';
import DataTable from '@/components/data-table';
import AppLayout from '@/layouts/app-layout';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { ArrowUpDown, MoreHorizontal } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

const breadcrumbs = [
    { title: 'PAKH', href: '/pakh' },
];


type Props = {
    data: {
        data: any[];
        links: { first: string; last: string; next: string | null; prev: string | null };
        meta: { current_page: number; from: number; last_page: number; per_page: number; to: number; total: number };
    };
    filters?: {
        [key: string]: { id: string; value: any; variant: string; operator: string };
    };
};

const name = 'pakh';

export default function PAKH({ data, filters }: Props) {
    console.log('PAKH data:', data, filters);

    const columns = useMemo<ColumnDef<any>[]>(
        () => [
            {
                id: 'select',
                header: ({ table }) => (
                    <Checkbox
                        checked={table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate')}
                        onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                        aria-label="Select all"
                    />
                ),
                cell: ({ row }) => (
                    <Checkbox checked={row.getIsSelected()} onCheckedChange={(value) => row.toggleSelected(!!value)} aria-label="Select row" />
                ),
                enableSorting: false,
                enableHiding: false,
            },
            {
                header: 'ID',
                accessorKey: 'id',
            },
            {
                header: 'Mã CV',
                accessorKey: 'ma_cong_viec',
            },
            {
                header: 'Khu vực',
                accessorKey: 'ttkv',
                meta: {
                    filterVariant: 'multiSelect',
                    options: [
                        { value: 'GĐH', label: 'GĐH' },
                        { value: 'SGN', label: 'SGN' },
                    ],
                },
                filterFn: (row, columnId, filterValue) => {
                    const rowValue = row.getValue(columnId);
                    return Array.isArray(filterValue) && filterValue.length > 0
                        ? filterValue.includes(rowValue)
                        : true;
                },
            },
            {
                header: 'Quận huyện',
                accessorKey: 'quan',
                meta: {
                    filterVariant: 'multiSelect',
                    options: [
                        { value: 'Quận 1', label: 'Quận 1' },
                        { value: 'Quận 3', label: 'Quận 3' },
                    ],
                },
                filterFn: (row, columnId, filterValue) => {
                    const rowValue = row.getValue(columnId);
                    return Array.isArray(filterValue) && filterValue.length > 0
                        ? filterValue.includes(rowValue)
                        : true;
                },
            },
            {
                header: 'Mã trạm',
                accessorKey: 'ma_tram',
            },
            {
                header: 'NV TH',
                accessorKey: 'nhan_vien_thuc_hien',
            },
            {
                header: 'TG kết thúc',
                accessorKey: 'thoi_diem_ket_thuc',
                meta: {
                    filterVariant: 'date',
                },
            },
            {
                header: 'TĐ CD đóng',
                accessorKey: 'thoi_diem_cd_dong',
                meta: {
                    filterVariant: 'date',
                },
            },
            {
                header: 'DG WO TH',
                accessorKey: 'danh_gia_wo_thuc_hien',
                meta: {
                    filterVariant: 'multiSelect',
                    options: [
                        { value: 'WO TH < 1 ngày', label: 'WO TH < 1 ngày' },
                        { value: 'WO TH < 2 ngày', label: 'WO TH < 2 ngày' },
                        { value: 'WO QH > 1 ngày', label: 'WO QH > 1 ngày' },
                        { value: 'WO QH > 3 ngày', label: 'WO QH > 3 ngày' },
                        { value: 'WO QH > 5 ngày', label: 'WO QH > 5 ngày' },
                        { value: 'WO STH < 1 ngày', label: 'WO STH < 1 ngày' },
                        { value: 'WO STH < 2 ngày', label: 'WO STH < 2 ngày' },
                        { value: 'WO STH > 2 ngày', label: 'WO STH > 2 ngày' },
                    ],
                },
                filterFn: (row, columnId, filterValue) => {
                    const rowValue = row.getValue(columnId);
                    return Array.isArray(filterValue) && filterValue.length > 0
                        ? filterValue.includes(rowValue)
                        : true;
                },
            },
            {
                header: 'TT WO',
                accessorKey: 'time_status',
                meta: {
                    filterVariant: 'multiSelect',
                    options: [
                        { value: 'TH', label: 'TH' },
                        { value: 'QH', label: 'QH' },
                        { value: 'Tồn QH', label: 'Tồn QH' },
                    ],
                },
                filterFn: (row, columnId, filterValue) => {
                    const rowValue = row.getValue(columnId);
                    return Array.isArray(filterValue) && filterValue.length > 0
                        ? filterValue.includes(rowValue)
                        : true;
                },
            },
             {
                header: 'Phạt',
                accessorKey: 'phat',
            },
            {
                header: 'Packed',
                accessorKey: 'packed',
            },
            {
                header: 'Created at',
                accessorKey: 'created_at',
                meta: {
                    filterVariant: 'date',
                },
            },
            {
                header: 'Updated at',
                accessorKey: 'updated_at', meta: {
                    filterVariant: 'date',
                },
            },
            {
                header: 'Deleted at',
                accessorKey: 'deleted_at', meta: {
                    filterVariant: 'date',
                },
            },
            {
                id: 'actions',
                header: 'Actions',
                enableSorting: false,
                cell: ({ row }) => (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Open Menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuLabel>Actions</DropdownMenuLabel>
                        </DropdownMenuContent>
                    </DropdownMenu>
                ),
            },
        ],
        []
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="PAKH" />
            <div className="h-full rounded-xl p-4">
                <DataTable columns={columns} data={data.data} pagination={data} name={name} initialFilters={filters} />
            </div>

        </AppLayout>
    );
}