import {
    ColumnDef,
    ColumnFiltersState,
    flexRender,
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    SortingState,
    useReactTable,
    VisibilityState,
} from '@tanstack/react-table';

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

import { DropdownMenu, DropdownMenuCheckboxItem, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useEffect, useState } from 'react';
import Pagination from './pagination';
import { router } from '@inertiajs/react';


interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    pagination: {
        data: TData[];
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
    name: string;
    initialFilters?: {
        area_ids: string[];
        district_ids: string[];
        start_date?: string;
        end_date?: string;
        header?: string;
    };
}

function DataTable<TData, TValue>({ data, columns, pagination, name,initialFilters }: DataTableProps<TData, TValue>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({
        permission: false,
        khu_vuc: false,
        thoi_gian_xuat_hien_canh_bao: false,
        packed: false,
        created_at: false,
    });
    console.log(initialFilters)

    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
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
        router.visit(route(`${name}.index`), {
            method: 'post',
            data: {
                page,
                per_page: perPage,
                // areas: initialFilters?.area_ids || [],
                //     districts: initialFilters?.district_ids || [],
                //     header: initialFilters?.header || '',
                //     startDate: initialFilters?.start_date || '',
                //     endDate: initialFilters?.end_date || '',
                ...filters,
            },
            preserveState: true,
            preserveScroll: true,
            replace: true,
            // only: ['data'],
            only: ['data', 'filters'],
        });
    };


    useEffect(() => {
        const filters = columnFilters.reduce((acc, filter) => {
            acc[filter.id] = filter.value;
            return acc;
        }, {} as Record<string, any>);

        fetchData(filters, pagination.meta.current_page, pagination.meta.per_page);
    }, [columnFilters]);

    const handlePageChange = (page: number) => {
        const filters = columnFilters.reduce((acc, filter) => {
            acc[filter.id] = filter.value;
            return acc;
        }, {} as Record<string, any>);

        fetchData(filters, page + 1, pagination.meta.per_page);
    };

    return (
        <div className="">
            <div className="item-center flex p-0">
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="outline" className="ml-auto">
                            Columns
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {table
                            .getAllColumns()
                            .filter((column) => column.getCanHide())
                            .map((column) => (
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
                {table.getFilteredSelectedRowModel().rows.length} of{" "}
                {table.getFilteredRowModel().rows.length} row(s) selected.
            </div>

            <div className="rounded border p-5">
                {/* <div className="rounded shadow-2xl p-5"> */}
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder ? null : (
                                            <div className="pb-2">
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                            </div>
                                        )}
                                        {(header.column.columnDef as any).search ? (
                                            <div className="h-14">
                                                <Input
                                                    value={(header.column.getFilterValue() as string) ?? ''}
                                                    onChange={(event) =>
                                                        header.column.setFilterValue(event.target.value)
                                                    }
                                                // placeholder={`Filter ${header.column.id}`}
                                                />
                                            </div>
                                        ) : (
                                            <div className="h-14"></div>
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
                                        <TableCell
                                            key={cell.id}
                                            className={(cell.row.original as any)?.count < 1 ? "line-through text-gray-300" : ""}
                                        // className={cell.column.id !== "created_at" && cell.row.original.count === 0 ? "line-through text-gray-500" : ""}
                                        >
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={columns.length} className="h-24 text-center">
                                    No Results
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>
            <Pagination table={table} total={pagination.meta.total} onPageChange={handlePageChange} name={name} />
        </div>
    );
}

export default DataTable;
