import AddAreaSheet from '@/components/add-area-sheet';
import ConFirmAlert from '@/components/confirm-alert';
import DataTable from '@/components/data-table';
import EditAreaSheet from '@/components/edit-area-sheet';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import ColumnHeader from '@/components/ui/column-header';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import { Area, Data, type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Edit, MoreHorizontal, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast, Toaster } from 'react-hot-toast';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'TTKV',
        href: '/area',
    },
];

type Props = { areas: Area[] };
type AlertType = 'delete';
const name = 'areas';

export default function Areas({ data }: Data) {
    // console.log(areas);
    const user = usePage().props.auth.user;
    // console.log(user);

    const [openAlert, setOpenAlert] = useState(false);
    const [alertType, setAlertType] = useState<AlertType>();
    const [selected, setSelected] = useState<any>();
    const [openAddSheet, setOpenAddSheet] = useState(false);
    const [openEdit, setOpenEdit] = useState(false);
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
                header: ({ column }) => {
                    return (
                        <Button className='h-0'
                            variant='link'
                            onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
                        >
                            ID
                            <ArrowUpDown />
                        </Button>
                    )
                },
                accessorKey: "id",
                search: true,
            },
            {
                header: 'Name',
                accessorKey: 'name',
                search: true,
            },
            {
                header: 'Districts',
                accessorKey: 'districts',
                search: true,
            },
            {
                header: 'Created by',
                accessorKey: 'created_by',
                search: true,
            },
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
        <AppLayout breadcrumbs={breadcrumbs} create={<CreateButton />}>
            <Head title="Area" />

            <div className="h-full rounded-xl p-4">
                <DataTable columns={columns}  data={data.data} pagination={data} name={name}/>

                <ConFirmAlert
                    title={`Confirm ${alertType}`}
                    message={`Are you sure want to ${alertType}`}
                    open={openAlert}
                    onOpenChange={setOpenAlert}
                    onConfirm={alertType === 'delete' ? handleDelete : handleDelete}
                />
                <AddAreaSheet open={openAddSheet} onOpenChange={setOpenAddSheet} />

                {selected && openEdit && (
                    <EditAreaSheet
                        selected={selected}
                        open={openEdit}
                        onOpenChange={(openState) => {
                            setSelected(undefined);
                            setOpenEdit(openState);
                        }}
                    />
                )}
                <Toaster />
            </div>
        </AppLayout>
    );
}
