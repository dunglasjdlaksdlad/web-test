
import { useTransition } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Download, Trash2 } from 'lucide-react';

import { Separator } from '@/components/ui/separator';
import { DataTableActionBar, DataTableActionBarAction, DataTableActionBarSelection } from '@/components/data-table-action-bar';
import { toast } from 'sonner';

const statusOptions: string[] = ['todo', 'in-progress', 'done', 'canceled'];
const priorityOptions: string[] = ['low', 'medium', 'high'];

export default function TasksTableActionBar({ table, name }: { table: any, name: any }) {
    const rows = table.getFilteredSelectedRowModel().rows;
    const [isPending, startTransition] = useTransition();

    const handleExport = () => {
        startTransition(() => {
            router.post(
                route(`${name}.export`),
                {
                    ids: rows.map((row: any) => row.original.id),
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => toast.success('Export started'),
                    onError: (errors) => toast.error(errors.message || 'Failed to export'),
                }
            );
        });
    };

    const handleDelete = () => {
        startTransition(() => {
            router.delete(
                route(`${name}.destroy`),
                {
                    data: { ids: rows.map((row: any) => row.original.id) },
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        table.toggleAllRowsSelected(false);
                        toast.success('Items deleted successfully');
                    },
                    onError: (errors) => toast.error(errors.message || 'Failed to delete items'),
                }
            );
        });
    };

    if (rows.length === 0) return null;

    return (
        <DataTableActionBar table={table}>
            <DataTableActionBarSelection table={table} />
            <Separator orientation="vertical" className="hidden data-[orientation=vertical]:h-5 sm:block" />
            <div className="flex items-center gap-1.5">

                <DataTableActionBarAction
                    size="icon"
                    tooltip="Export selected items"
                    isPending={isPending}
                    onClick={handleExport}
                >
                    <Download className="h-4 w-4" />
                </DataTableActionBarAction>

                <DataTableActionBarAction
                    size="icon"
                    tooltip="Delete selected items"
                    isPending={isPending}
                    onClick={handleDelete}
                >
                    <Trash2 className="h-4 w-4" />
                </DataTableActionBarAction>
            </div>
        </DataTableActionBar>
    );
}