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

interface DataTableProps<TData, TValue> {
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
}

function DataTableBar<TData, TValue>({
    data,
    columns,
}: DataTableProps<TData, TValue>) {
    const table = useReactTable({
        data,
        columns,
        getCoreRowModel: getCoreRowModel(),
    });
console.log(data);
const handleHeaderClick = (headerName: string | React.ReactNode) => {
        const headerValue = typeof headerName === 'string' ? headerName : '';

        const areas = [...new Set(data.map((item: any) => item.ttkv1).filter(Boolean))];
        const districts = [...new Set(data.map((item: any) => item.quan).filter(Boolean))];
        console.log(areas,districts);

        router.get(route('wott.index'), {
        //    data: { areas,
        //     districts, 
        //     header: headerValue,}
         areas,
            districts, 
            header: headerValue,
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
                            {headerGroup.headers.map((header) => (
                                <TableHead key={header.id}
                                className="cursor-pointer  hover:underline"
                                    onClick={() => handleHeaderClick(header.column.columnDef.header) }>
                                    {header.isPlaceholder
                                        ? null
                                        : flexRender(
                                            header.column.columnDef.header,
                                            header.getContext()
                                        )}
                                </TableHead>
                            ))}
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