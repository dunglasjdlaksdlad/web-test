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
} from "@tanstack/react-table";

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useState } from "react";
import Pagination from "./pagination";
import { router } from "@inertiajs/react";
import { FormTypeDashboard } from "@/types";

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    name?: string;
    filters?: FormTypeDashboard;
}
const excludedHeaders = ['ttkv', 'tổng wo', 'phạt'];
function DataTableBar<TData, TValue>({
    data,
    columns,
    name,
    filters,

}: DataTableProps<TData, TValue>) {
    const table = useReactTable({
        data,
        columns,

        getCoreRowModel: getCoreRowModel(),
    });
    console.log(name, filters, data);
    const handleHeaderClick = (headerName: string | React.ReactNode) => {
        const headerValue = typeof headerName === 'string' ? headerName : '';

        const areas = [...new Set(data.map((item: any) => item.ttkv1).filter(Boolean))];
        const districts = [...new Set(data.map((item: any) => item.quan).filter(Boolean))];
        console.log(areas, districts);

        const timeStatus = headerValue ? [headerValue] : [];
        router.post(route(`${name}.index`), {
            filters: {
                0: { id: 'ttkv', value: areas, variant: 'multiSelect', operator: 'inArray' },
                1: { id: 'quan', value: districts, variant: 'multiSelect', operator: 'inArray' },
                2: { id: 'thoi_diem_ket_thuc', value: filters?.time || { 0: "", 1: "" }, variant: 'date', operator: 'isBetween' },
                3: { id: 'header', value: timeStatus, variant: 'multiSelect', operator: 'inArray' },
            }
        }, {
            preserveState: false,
            preserveScroll: true,
            replace: true,
        });
    };

    return (
        <div className="shadow-2xl rounded p-5 w-full">
            <Table>
                <TableHeader>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <TableRow key={headerGroup.id}>
                            {headerGroup.headers.map((header) => {
                                const headerContent = header.isPlaceholder
                                    ? null
                                    : flexRender(
                                        header.column.columnDef.header,
                                        header.getContext()
                                    );

                                const headerValue = typeof headerContent === 'string'
                                    ? headerContent.toLowerCase()
                                    : header.column.id?.toLowerCase() || '';
                                const isClickable = !excludedHeaders.includes(headerValue);

                                return (
                                    <TableHead
                                        key={header.id}
                                        className={isClickable ? "cursor-pointer hover:underline" : ""}
                                        onClick={isClickable ? () => handleHeaderClick(headerContent) : undefined}
                                    >
                                        {headerContent}
                                    </TableHead>
                                );
                            })}
                        </TableRow>
                    ))}

                </TableHeader>
                <TableBody>
                    {table.getRowModel().rows?.length ? (
                        table.getRowModel().rows.map((row) => (
                            <TableRow
                                key={row.id}
                                data-state={row.getIsSelected() && "selected"}
                            >
                                {row.getVisibleCells().map((cell) => (
                                    <TableCell key={cell.id}>
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </TableCell>
                                ))}
                                <TableHead>
                                </TableHead>
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
    );
}

export default DataTableBar;