import { Table } from "@tanstack/react-table";
import React from "react";
import {
  ChevronLeft,
  ChevronRight,
  ChevronsLeft,
  ChevronsRight,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { router } from "@inertiajs/react";

interface PaginationProps<TData> {
  table: Table<TData>;
  total: number;
  onPageChange: (page: number) => void;
  from?: number; // Thêm from từ meta
  to?: number;   // Thêm to từ meta
  name?: string;
}

function Pagination<TData>({ table, total, onPageChange, from, to, name }: PaginationProps<TData>) {
  const pageSizeOptions = [10, 20, 30, 40, 50, 100];

  // const handlePageSizeChange = (value: string) => {
  //   table.setPageSize(Number(value));
  //   router.get(
  //     route(`${name}.index`),
  //     { per_page: value, page: 1 }, // Reset về trang 1 khi đổi page size
  //     { preserveState: true, preserveScroll: true }
  //   );
  // };
  const handlePageSizeChange = (value: string) => {
    table.setPageSize(Number(value));
    router.visit(
      route(`${name}.index`),
      {
        method: 'post',
        data: { per_page: value, page: 1 }, // Reset về trang 1 khi đổi page size
        preserveState: true,
        preserveScroll: true,
        replace: true, // Không thay đổi URL
        only: ['data'], // Chỉ cập nhật prop 'data'
      }
    );
  };

  return (
    <div className="flex items-center justify-between mt-6 mb-10">
      <div className="flex-1 text-sm">
        {/* Hiển thị số hàng hiện tại */}
        Showing {from || 1} to {to || table.getState().pagination.pageSize} of {total} rows
      </div>
      <div className="flex items-center space-x-6">
        <div className="flex items-center space-x-2">
          <p className="text-sm font-medium">Rows per page</p>
          <Select
            value={`${table.getState().pagination.pageSize}`}
            onValueChange={handlePageSizeChange}
          >
            <SelectTrigger className="h-8 w-[70px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent side="top">
              {pageSizeOptions.map((size) => (
                <SelectItem key={size} value={`${size}`}>
                  {size}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex w-[100px] items-center justify-center text-sm font-medium">
          Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => onPageChange(0)}
            disabled={!table.getCanPreviousPage()}
          >
            <ChevronsLeft />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => onPageChange(table.getState().pagination.pageIndex - 1)}
            disabled={!table.getCanPreviousPage()}
          >
            <ChevronLeft />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => onPageChange(table.getState().pagination.pageIndex + 1)}
            disabled={!table.getCanNextPage()}
          >
            <ChevronRight />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => onPageChange(table.getPageCount() - 1)}
            disabled={!table.getCanNextPage()}
          >
            <ChevronsRight />
          </Button>
        </div>
      </div>
    </div>
  );
}

export default Pagination;