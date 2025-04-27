import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import ConFirmAlert from '@/components/confirm-alert';
import DataTable from '@/components/data-table';
import EditAreaSheet from '@/components/edit-area-sheet';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import ColumnHeader from '@/components/ui/column-header';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { Area, Data, type BreadcrumbItem } from '@/types';
import { Deferred, Head, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Edit, MoreHorizontal, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast, Toaster } from 'react-hot-toast';
import Loading from '@/components/ui/loading';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'GDTT',
        href: '/gdtt',
    },
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
type AlertType = 'delete';
const name ='gdtt';

export default function GDTT({ data,filters }: Props) {
    const [openAlert, setOpenAlert] = useState(false);
    const [alertType, setAlertType] = useState<AlertType>();
    const [selected, setSelected] = useState<Area>();
    const [openAddSheet, setOpenAddSheet] = useState(false);
    const [openEdit, setOpenEdit] = useState(false);
    const columns = useMemo<ColumnDef<Area>[]>(
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
                header: 'Mã tủ BTS/Node',
                accessorKey: 'ma_tu_btsnodeb',
            },
            {
                header: "Mã trạm chuẩn",
                accessorKey: "ma_nha_tram_chuan",
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
                header: "Thời gian xuất hiện cb",
                accessorKey: "thoi_gian_xuat_hien_canh_bao",  meta: {
                    filterVariant: 'date',
                },
            },
            {
                header: "Thời điểm kết thúc",
                accessorKey: "thoi_diem_ket_thuc",  meta: {
                    filterVariant: 'date',
                },
            },
              {
                header: "Thời gian tồn",
                accessorKey: "thoi_gian_ton",
            },
              {
                header: 'NN mức 1',
                accessorKey: 'nn_muc_1',
                meta: {
                    filterVariant: 'multiSelect',
                    options: [
                        { value: 'chưa rõ nguyên nhân', label: 'Chưa rõ nguyên nhân' },
                        { value: 'truyền dẫn', label: 'Truyền dẫn' },
                        { value: 'thiết bị', label: 'Thiết bị' },
                        { value: 'vhkt', label: 'VHKT' },
                        { value: 'nguồn', label: 'Nguồn' },
                        { value: 'tác động hệ thống', label: 'Tác động hệ thống' },
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
                header: "Cell*h Tgt",
                accessorKey: "cellh_truoc_giam_tru",
            },
             {
                header: "Cell*h gt",
                accessorKey: "cellh_giam_tru",
            },
            {
                header: "Cell*h Sgt",
                accessorKey: "cellh_sau_giam_tru",
            },
              {
                header: "Day",
                accessorKey: "day",
            },
              {
                header: "Week",
                accessorKey: "week",
            },
              {
                header: "Month",
                accessorKey: "month",
            },
              {
                header: "Year",
                accessorKey: "year",
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
                header: ({ column }) => <ColumnHeader column={column} title="Actions" />,
                enableSorting: false,
                cell: ({ row }) => {
                    const area = row.original;
                    return (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" className="h-8 w-8 p-0">
                                    <span className="sr-only">Open Menu</span>
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                <DropdownMenuItem
                                    onClick={() => {
                                        setSelected(area);
                                        setOpenEdit(true);
                                    }}
                                >
                                    <Edit /> Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => presentAlert(area, 'delete')}>
                                    <Trash2 /> Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                },
            },
        ],
        [],
    );
    const presentAlert = (area: Area, type: AlertType) => {
        setSelected(area), setAlertType(type), setOpenAlert(true);
    };

    const handleDelete = () =>
        router.delete(route('areas.destroy', selected?.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('user has been deleted successfully');
                setSelected(undefined);
            },
        });

    const CreateButton = () => (
        <div className="flex">
            <Button className="ml-auto" onClick={() => setOpenAddSheet(true)}>
                Create New
            </Button>
        </div>
    );
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="GDTT" />
              <div className="h-full rounded-xl p-4">
                <DataTable columns={columns} data={data.data} pagination={data} name={name} initialFilters={filters} />
            </div>
        </AppLayout>
    );
}
