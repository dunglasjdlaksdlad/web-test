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
    { title: 'WOTT', href: '/wott' },
];

type Props = {
    data: {
        data: any[];
        links: { first: string; last: string; next: string | null; prev: string | null };
        meta: { current_page: number; from: number; last_page: number; per_page: number; to: number; total: number };
    };
    filters?: { ttkv?: string[]; quan?: string[]; start_date?: string; end_date?: string; header?: string };
};

const name = 'wott';

export default function WOTT({ data, filters }: Props) {
    console.log('WOTT data:', data);

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
                header: ({ column }) => (
                    <Button variant="link" className="h-0" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                        ID <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                ),
                accessorKey: 'id',
            },
            {
                header: 'Mã CV',
                accessorKey: 'ma_cong_viec',
                enableColumnFilter: true,
                meta: { filterVariant: 'text' },
            },
            {
                header: 'Khu vực',
                accessorKey: 'ttkv',
                enableColumnFilter: true,
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
                search: true,
            },
            {
                header: 'Mã trạm',
                accessorKey: 'ma_tram',
                search: true,
            },
            {
                header: 'End time',
                accessorKey: 'thoi_diem_ket_thuc',
                search: true,
            },
            {
                header: 'Thời điểm CD đóng',
                accessorKey: 'thoi_diem_cd_dong',
                search: true,
            },
            {
                header: 'DG WO TH',
                accessorKey: 'danh_gia_wo_thuc_hien',
                search: true,
            },
            {
                header: 'Packed',
                accessorKey: 'packed',
                search: true,
            },
            {
                header: ({ column }) => (
                    <Button variant="link" className="h-0" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                        Created at <ArrowUpDown className="ml-2 h-4 w-4" />
                    </Button>
                ),
                accessorKey: 'created_at',
                search: true,
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
            <Head title="WOTT" />
            <div className="h-full rounded-xl p-4">
                <DataTable columns={columns} data={data.data} pagination={data} name={name} initialFilters={filters} />
            </div>
        </AppLayout>
    );
}