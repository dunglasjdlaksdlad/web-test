import {
    ColumnDef,
    ColumnFiltersState,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    useReactTable,
    VisibilityState,
    SortingState,
    getFacetedRowModel,
    getFacetedUniqueValues,
    flexRender,
} from '@tanstack/react-table';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import Pagination from './pagination';
import { DataTableFilterList } from './ui/DataTableFilterList';
import TasksTableActionBar from './table-action-bar';
import { Settings2 } from 'lucide-react';
import { ExtendedColumnFilter } from '@/types/data-table';
import { generateId } from '@/lib/id';

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    pagination: {
        data: TData[];
        links: { first: string; last: string; next: string | null; prev: string | null };
        meta: { current_page: number; from: number; last_page: number; per_page: number; to: number; total: number };
    };
    name: string;
    initialFilters?: {
        [key: string]: { id: string; value: any; variant: string; operator: string };
    };
}

function DataTable<TData, TValue>({
    data,
    columns,
    pagination,
    name,
    initialFilters,
}: DataTableProps<TData, TValue>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>(() => {
        if (!initialFilters) return [];
        // return Object.entries(initialFilters)
        //     .filter(([_, value]) => value && (Array.isArray(value) ? value.length > 0 : true))
        //     .map(([id, filter]) => ({
        // id: filter.id,
        // value: filter.value,
        // variant: filter.variant as ExtendedColumnFilter<TData>['variant'],
        // operator: filter.operator as ExtendedColumnFilter<TData>['operator'],
        // filterId: generateId({ length: 8 }),
        // })) as ExtendedColumnFilter<TData>[];
        return Object.values(initialFilters);
    });
    console.log('columnFilters',columnFilters);

    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
        permission: false,
        khu_vuc: false,
        // thoi_gian_xuat_hien_canh_bao: false,
        packed: false,
        created_at: false,
        updated_at: false,
        deleted_at: false,
        day: false,
        week: false,
        month: false,
        year: false,
    });

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        getFacetedRowModel: getFacetedRowModel(),
        getFacetedUniqueValues: getFacetedUniqueValues(),
        manualPagination: true,
        pageCount: pagination.meta.last_page,
        state: {
            sorting,
            columnFilters,
            columnVisibility,
            pagination: {
                pageIndex: pagination.meta.current_page - 1,
                pageSize: pagination.meta.per_page,
            },
        },
        onColumnFiltersChange: setColumnFilters,
        onSortingChange: setSorting,
        onColumnVisibilityChange: setColumnVisibility,
    }); 

    const fetchData = (filters: Record<string, any>, page: number, perPage: number) => {
        // router.visit(
        //     route(`${name}.index`),
        //     {
        //         method: 'post',
        //         data: {
        //             filters,
        //             page,
        //             per_page: perPage,
        //         },
        //         preserveState: true,
        //         preserveScroll: true,
        //         replace: true,
        //         only: ['data', 'pagination', 'filters'],
        //     }
        // );
        // router.get(
        //     route(`${name}.index`),
        //     {
        //         filters,
        //         page,
        //         per_page: perPage,
        //     },
        //     {
        //         preserveState: true,
        //         preserveScroll: true,
        //         replace: true,
        //         only: ['data', 'pagination', 'filters'],
        //     }
        // );

        router.get(
        route(`${name}.index`),
        {
            filters: JSON.stringify(filters),
            page,
            per_page: perPage,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['data', 'pagination', 'filters'],
            onError: (errors) => {
                console.error('Inertia request failed:', errors);
            },
            onSuccess: (page) => {
                console.log('Response received:', page);
            },
        }
    );
    };

    const handleFiltersChange = (filters: ColumnFiltersState) => {
        setColumnFilters(filters);
        fetchData(filters, 1, pagination.meta.per_page);
    };

    const handlePageChange = (page: number) => {
        fetchData(columnFilters, page + 1, pagination.meta.per_page);
    };

        console.log('data',data);

    return (
        <>
            <TasksTableActionBar table={table} name={name} />
            <div className="flex items-center p-0 gap-2 justify-end">
                <DataTableFilterList table={table} name={name} onFiltersChange={handleFiltersChange} initialFilters={initialFilters} />
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" size="sm">
                            <Settings2 />
                            View
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {table.getAllColumns().filter((column) => column.getCanHide()).map((column) => (
                            <DropdownMenuCheckboxItem
                                key={column.id}
                                className="capitalize"
                                checked={column.getIsVisible()}
                                onCheckedChange={(value) => column.toggleVisibility(!!value)}
                            >
                                {column.id}
                            </DropdownMenuCheckboxItem>
                        ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <div className="flex-1 text-sm text-muted-foreground">
                {table.getFilteredSelectedRowModel().rows.length} of {table.getFilteredRowModel().rows.length} row(s) selected.
            </div>
            <div className="rounded border p-5">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder ? null : (
                                            <div
                                                className={header.column.getCanSort() ? 'cursor-pointer select-none' : ''}
                                                onClick={header.column.getToggleSortingHandler()}
                                            >
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                            </div>
                                        )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows?.length ? (
                            table.getRowModel().rows.map((row) => (
                                <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                                    {row.getVisibleCells().map((cell) => (
                                        <TableCell key={cell.id}
                                         className={(cell.row.original as any)?.count < 1 ? "line-through text-gray-300" : ""}
                                        >
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-24 text-center">No Results</TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <Pagination table={table} total={pagination.meta.total} onPageChange={handlePageChange} name={name} />
        </>
    );
}

export default DataTable;