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
        title: 'CDBR',
        href: '/cdbr',
    },
];

type Props = { data: any[] };
type AlertType = 'delete';
const name = 'cdbr';

export default function CDBR({ data }: Data) {
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
                header: ({ column }) => {
                    return (
                        <Button className='h-0'
                            variant='link'
                            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                        >
                            ID
                            <ArrowUpDown className="ml-2 h-4 w-4" />
                        </Button>
                    )
                },
                accessorKey: "id",
                search: true,
            },
            {
                header: "Mã SC",
                accessorKey: "ma_su_co",
                search: true,
            },
            {
                header: "Định danh SC",
                accessorKey: "dinh_danh_su_co",
                search: true,
            },
            {
                header: "Khu vực",
                accessorKey: "khu_vuc",
                search: true,
            },
            {
                header: "Quận huyện",
                accessorKey: "quan",
                search: true,
            },
            {
                header: "Mã trạm",
                accessorKey: "ma_tram",
                search: true,
            },
            // {
            //     header: "Start time",
            //     accessorKey: "thoi_gian_bat_dau",
            //     search: true,
            // },
            // {
            //     header: "End time",
            //     accessorKey: "thoi_gian_ket_thuc",
            //     search: true,
            // },
            {
                header: "Total time",
                accessorKey: "tong_thoi_gian",
                search: true,
            },
            {
                header: "Ngày PSSC",
                accessorKey: "ngay_ps_sc",
                search: true,
            },
            {
                header: "NN mức 1",
                accessorKey: "nn_muc_1",
                search: true,
            },
            {
                header: "Packed",
                accessorKey: "thoi_gian_ket_thuc",
                search: true,
            },
            // {
            //     header: 'Created by',
            //     accessorKey: 'created_by',
            //     search: true,
            // },
            {
                header: ({ column }) => {
                    return (
                        <Button className='h-0'
                            variant='link'
                            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                        >
                            Created at
                            <ArrowUpDown className="ml-2 h-4 w-4" />
                        </Button>
                    )
                },
                accessorKey: "created_at",
                search: true,
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
            <Head title="CDBR" />
            <div className="h-full rounded-xl p-4">

                <DataTable columns={columns} data={data.data} pagination={data} name={name}/>
                {/* <Deferred data="data" fallback={<Loading />}>
                </Deferred> */}

            </div>
        </AppLayout>
    );
}
