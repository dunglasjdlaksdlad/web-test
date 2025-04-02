import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { Data, PageProps, type BreadcrumbItem } from '@/types';
import { Deferred, Head, router } from "@inertiajs/react";
import { ColumnDef } from "@tanstack/react-table";
import { useMemo, useState } from "react";
import { AvatarImage, AvatarFallback, Avatar } from "@/components/ui/avatar";
import { cn } from "@/lib/utils";
import {
    DropdownMenu,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
    DropdownMenuContent,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import {
    ArrowUpDown,
    LockKeyhole,
    LockKeyholeOpen,
    MoreHorizontal,
    Trash2,
} from "lucide-react";

import { Edit } from "lucide-react";
import { Toaster, toast } from "react-hot-toast";
import ConFirmAlert from "@/components/confirm-alert";
import AddUserSheet from "@/components/add-user-sheet";
import EditUserSheet from "@/components/edit-user-sheet";

import ColumnHeader from "@/components/ui/column-header";
import { useInitials } from '@/hooks/use-initials';
import { can } from '@/types/helpers';
import DataTable from '@/components/data-table';
import Loading from '@/components/ui/loading';
import { Checkbox } from '@/components/ui/checkbox';
const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];


type Props = { roles: any };

type AlertType = "delete" | "activate" | "block";
const name ='users';
export default function Users({ auth, data, roles }: PageProps & Props & Data) {
    const [openAlert, setOpenAlert] = useState(false);
    const [alertType, setAlertType] = useState<AlertType>();
    const [selectedUser, setSelectedUser] = useState<any>();

    const [openAddUserSheet, setOpenAddUserSheet] = useState(false);
    const [openEditUser, setOpenEditUser] = useState(false);
    const getInitials = useInitials();
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
                id: "avatar",
                header: "Avatar",
                enableSorting: false,
                search: false,
                cell: ({ row }) => {
                    const user = row.original;
                    return (
                        <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                            <AvatarImage src={user.avatar} alt={user.name} />
                            <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                {getInitials(user.name)}
                            </AvatarFallback>
                        </Avatar>
                    );
                },
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
                header: "Full name",
                accessorKey: "name",
                search: true,
            },
            {
                header: "Email",
                accessorKey: "email",
                search: true,
            },
            {
                header: "Role",
                accessorKey: "role",
                search: true,
            },
            {
                id: "status",
                header: "Status",
                accessorKey: "is_active",
                search: false,
                cell: ({ row }) => {
                    const user = row.original;
                    return (
                        <div
                            className={cn(
                                "inline-block px-2 pb-0.5 rounded-md",
                                user.is_active
                                    ? "bg-green-100 text-green-700"
                                    : "bg-orange-100 text-orange-700"
                            )}
                        >
                            {user.is_active ? "Active" : "Inactive"}
                        </div>
                    );
                },

            },
            {
                id: "actions",
                header: ({ column }) => (
                    <ColumnHeader column={column} title="Actions" />
                ),
                enableSorting: false,
                search: false,
                cell: ({ row }) => {
                    const user = row.original;
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
                                {can(auth.user, "edit users") && (
                                    <DropdownMenuItem
                                        onClick={() => {
                                            setSelectedUser(user);
                                            setOpenEditUser(true);
                                        }}
                                    >
                                        <Edit /> Edit
                                    </DropdownMenuItem>
                                )}
                                {can(auth.user, "destroy") && (
                                    <DropdownMenuItem
                                        onClick={() => presentAlert(user, "delete")}
                                    >
                                        <Trash2 /> Delete
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuItem
                                    onClick={() =>
                                        presentAlert(user, user.is_active ? "block" : "activate")
                                    }
                                >
                                    {user.is_active ? <LockKeyhole /> : <LockKeyholeOpen />}
                                    {user.is_active ? "Block" : "Activate"}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    );
                },
            },
        ],
        []
    );

    const presentAlert = (user: any, type: AlertType) => {
        setSelectedUser(user), setAlertType(type), setOpenAlert(true);
    };

    const handleDelete = () =>
        router.delete(route("users.destroy", selectedUser?.id), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success("user has been deleted successfully");
                setSelectedUser(undefined);
            },
        });

    const handleUpdateStatus = () => {
        router.post(
            route("users.status", selectedUser?.id),
            { status: alertType },
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    toast.success("user has been blocked successfully");
                    setSelectedUser(undefined);
                },
            }
        );
    };
    const CreateButton = () => (
        <div className="flex">
            {can(auth.user, "create users") && (
                <Button onClick={() => setOpenAddUserSheet(true)}>
                    Create New
                </Button>
            )}
        </div>
    );
    return (
        <AppLayout breadcrumbs={breadcrumbs}
            create={<CreateButton />}
        >
            <Head title="Users" />
            <div className="h-full rounded-xl p-4">
                <DataTable columns={columns} data={data.data} pagination={data} name={name}/>
                {/* <Deferred data="users" fallback={<Loading />}>
                    <DataTable columns={columns} data={users} />
                </Deferred> */}
                <ConFirmAlert
                    title={`Confirm ${alertType}`}
                    message={`Are you sure want to ${alertType}`}
                    open={openAlert}
                    onOpenChange={setOpenAlert}
                    onConfirm={alertType === "delete" ? handleDelete : handleUpdateStatus}
                />
                <AddUserSheet
                    open={openAddUserSheet}
                    roles={roles}
                    onOpenChange={setOpenAddUserSheet}
                />
                {selectedUser && openEditUser && (
                    <EditUserSheet
                        selected={selectedUser}
                        roles={roles}
                        open={openEditUser}
                        onOpenChange={(openState) => {
                            setSelectedUser(undefined);
                            setOpenEditUser(openState);
                        }}
                    />
                )}
                <Toaster />
            </div>
        </AppLayout>
    );
}
