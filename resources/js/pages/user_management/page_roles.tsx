import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { Data, type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Deferred, Link, router } from "@inertiajs/react";
import { useEffect, useMemo, useState } from "react";

import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { Area, Permission } from "@/types";
import { ColumnDef } from "@tanstack/react-table";
import ColumnHeader from "@/components/ui/column-header";
import DataTable from "@/components/data-table";
import ConFirmAlert from "@/components/confirm-alert";
import toast, { Toaster } from "react-hot-toast";
import AddAreaSheet from "@/components/add-area-sheet";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { ArrowUpDown, Edit, MoreHorizontal, Trash2 } from "lucide-react";
import EditAreaSheet from "@/components/edit-area-sheet";

import AddPermissionSheet from "@/components/add-permission-sheet";
import { DialogCreateRole } from "@/components/dialog-create-role";
import { DialogEditRole } from "@/components/dialog-edit-role";
import Loading from '@/components/ui/loading';
import { Checkbox } from '@/components/ui/checkbox';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Roles',
        href: '/roles',
    },
];
type Props = { data: Data; permissions: any[] };

type AlertType = "delete";
const name = 'roles';
export default function Roles({ data, permissions }: Props) {
    console.log(data, permissions);
    const [openAlert, setOpenAlert] = useState(false);
    const [alertType, setAlertType] = useState<AlertType>();
    // const [selectedArea, setSelectedArea] = useState<Area>();
    const [selectedArea, setSelectedArea] = useState<any>();
    console.log(selectedArea);
    const [openAddAreaSheet, setOpenAddAreaSheet] = useState(false);
    const [openEditArea, setOpenEditArea] = useState(false);
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
                            <ArrowUpDown className="ml-2 h-4 w-4" />
                        </Button>
                    )
                },
                accessorKey: "id",
                search: true,
            },
            {
                header: "Name",
                accessorKey: "name",
                search: true,
            },
            {
                header: "Created by",
                accessorKey: "created_by",
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
            // {
            //     header: "Permissions",
            //     accessorKey: "permission",
            //     search: true,
            // },
            {
                id: "actions",
                header: ({ column }) => (
                    <ColumnHeader column={column} title="Actions" />
                ),
                enableSorting: false,
                cell: ({ row }) => {
                    const role = row.original;
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
                                        setSelectedArea(role);
                                        setOpenEditArea(true);
                                    }}
                                >
                                    <Edit /> Edit
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={() => presentAlert(role, "delete")}>
                                    <Trash2 /> Delete
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                },
            },
        ],
        []
    );


    const presentAlert = (area: Area, type: AlertType) => {
        setSelectedArea(area), setAlertType(type), setOpenAlert(true);
    };

    const handleDelete = () =>
        router.delete(route("roles.destroy", selectedArea?.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success("user has been deleted successfully");
                setSelectedArea(undefined);
            },
            onError: () => {
                toast.success("user not found");
            }
        });

    const CreateButton = () => (
        <div className="flex">
            <Button onClick={() => setOpenAddAreaSheet(true)}>Create New</Button>
        </div>
    );
    return (
        <AppLayout breadcrumbs={breadcrumbs} create={<CreateButton />}>
            <Head title="Roles" />
            <div className="h-full rounded-xl p-4">

                <DataTable columns={columns} data={data.data} pagination={data} name={name}/>

                <ConFirmAlert
                    title={`Confirm ${alertType}`}
                    message={`Are you sure want to ${alertType}`}
                    open={openAlert}
                    onOpenChange={setOpenAlert}
                    onConfirm={alertType === "delete" ? handleDelete : handleDelete}
                />

                <DialogCreateRole
                    permissions={permissions}
                    open={openAddAreaSheet}
                    onOpenChange={setOpenAddAreaSheet}
                />

                {selectedArea && openEditArea && (
                    <DialogEditRole
                        permissions={permissions}
                        selected={selectedArea}
                        open={openEditArea}
                        onOpenChange={(openState) => {
                            setSelectedArea(undefined);
                            setOpenEditArea(openState);
                        }}
                    />
                )}
                <Toaster />
            </div>
        </AppLayout>
    );
}
