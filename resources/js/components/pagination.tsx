// import { Table } from "@tanstack/react-table";
// import React from "react";
// import {
//     ChevronLeft,
//     ChevronRight,
//     ChevronsLeft,
//     ChevronsRight,
// } from "lucide-react";
// import { Button } from "@/components/ui/button";
// import {
//     Select,
//     SelectContent,
//     SelectItem,
//     SelectTrigger,
//     SelectValue,
// } from "@/components/ui/select";
// import { router } from "@inertiajs/react";

// interface PaginationProps<TData> {
//     table: Table<TData>;
//     total: number;
//     onPageChange: (page: number) => void;
//     name: string;
//      initialFilters?: {
//         [key: string]: { id: string; value: any; variant: string; operator: string };
//     };
// }

// function Pagination<TData>({ table, total, onPageChange, name,    initialFilters }: PaginationProps<TData>) {
//     const pageSizeOptions = [10, 20, 30, 40, 50, 100];

//     console.log(table);

//     const handlePageSizeChange = (value: string) => {
//         table.setPageSize(Number(value));
//         // router.post(
//         //     route(`${name}.index`),
//         //     {
//         //         per_page: value,
//         //         page: 1,
//         //         filters: table.getState().columnFilters.reduce((acc, filter) => {
//         //             acc[filter.id] = filter.value;
//         //             return acc;
//         //         }, {} as Record<string, any>),
//         //     },
//         //     {
//         //         preserveState: true,
//         //         preserveScroll: true,
//         //         replace: true,
//         //         only: ["data", "pagination", "filters"],
//         //     }
//         // );
//         // router.get(
//         //     route(`${name}.index`),
//         //     {
//         //         per_page: value,
//         //         page: 1,
//         //         filters: table.getState().columnFilters.reduce((acc, filter) => {
//         //             acc[filter.id] = filter.value;
//         //             return acc;
//         //         }, {} as Record<string, any>),
//         //     },
//         //     {
//         //         preserveState: true,
//         //         preserveScroll: true,
//         //         replace: true,
//         //         only: ['data', 'pagination', 'filters'],
//         //     }
//         // );
//         router.get(
//             route(`${name}.index`),
//             {
//                 per_page: value,
//                 page: 1,
//                 filters: JSON.stringify(initialFilters),
//             },
//             {
//                 preserveState: true,
//                 preserveScroll: true,
//                 replace: true,
//                 only: ['data', 'pagination', 'filters'],
//                 onError: (errors) => console.error('Inertia request failed:', errors),
//                 onSuccess: (page) => console.log('Response received:', page),
//             }
//         );
//     };

//     return (
//         <div className="flex items-center justify-between mt-6 mb-10">
//             <div className="flex-1 text-sm">
//                 Showing {table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1} to{" "}
//                 {Math.min((table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize, total)} of {total} rows
//             </div>
//             <div className="flex items-center space-x-6">
//                 <div className="flex items-center space-x-2">
//                     <p className="text-sm font-medium">Rows per page</p>
//                     <Select
//                         value={`${table.getState().pagination.pageSize}`}
//                         onValueChange={handlePageSizeChange}
//                     >
//                         <SelectTrigger className="h-8 w-[70px]">
//                             <SelectValue />
//                         </SelectTrigger>
//                         <SelectContent side="top">
//                             {pageSizeOptions.map((size) => (
//                                 <SelectItem key={size} value={`${size}`}>
//                                     {size}
//                                 </SelectItem>
//                             ))}
//                         </SelectContent>
//                     </Select>
//                 </div>
//                 <div className="flex w-[100px] items-center justify-center text-sm font-medium">
//                     Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
//                 </div>
//                 <div className="flex items-center space-x-2">
//                     <Button
//                         variant="outline"
//                         className="h-8 w-8 p-0"
//                         onClick={() => onPageChange(0)}
//                         disabled={!table.getCanPreviousPage()}
//                     >
//                         <ChevronsLeft />
//                     </Button>
//                     <Button
//                         variant="outline"
//                         className="h-8 w-8 p-0"
//                         onClick={() => onPageChange(table.getState().pagination.pageIndex - 1)}
//                         disabled={!table.getCanPreviousPage()}
//                     >
//                         <ChevronLeft />
//                     </Button>
//                     <Button
//                         variant="outline"
//                         className="h-8 w-8 p-0"
//                         onClick={() => onPageChange(table.getState().pagination.pageIndex + 1)}
//                         disabled={!table.getCanNextPage()}
//                     >
//                         <ChevronRight />
//                     </Button>
//                     <Button
//                         variant="outline"
//                         className="h-8 w-8 p-0"
//                         onClick={() => onPageChange(table.getPageCount() - 1)}
//                         disabled={!table.getCanNextPage()}
//                     >
//                         <ChevronsRight />
//                     </Button>
//                 </div>
//             </div>
//         </div>
//     );
// }

// export default Pagination;


// // interface PaginationProps<TData> {
// //   table: Table<TData>;
// //   total: number;
// //   onPageChange: (page: number) => void;
// //   from?: number; // Thêm from từ meta
// //   to?: number;   // Thêm to từ meta
// //   name?: string;
// // }

// // function Pagination<TData>({ table, total, onPageChange, from, to, name }: PaginationProps<TData>) {
// //   const pageSizeOptions = [10, 20, 30, 40, 50, 100];

// //   // const handlePageSizeChange = (value: string) => {
// //   //   table.setPageSize(Number(value));
// //   //   router.get(
// //   //     route(`${name}.index`),
// //   //     { per_page: value, page: 1 }, // Reset về trang 1 khi đổi page size
// //   //     { preserveState: true, preserveScroll: true }
// //   //   );
// //   // };
// //   const handlePageSizeChange = (value: string) => {
// //     table.setPageSize(Number(value));
// //     router.visit(
// //       route(`${name}.index`),
// //       {
// //         method: 'post',
// //         data: { per_page: value, page: 1 },
// //         preserveState: true,
// //         preserveScroll: true,
// //         replace: true,
// //         only: ['data'],
// //       }
// //     );
// //   };

// //   return (
// //     <div className="flex items-center justify-between mt-6 mb-10">
// //       <div className="flex-1 text-sm">
// //         Showing {from || 1} to {to || table.getState().pagination.pageSize} of {total} rows
// //       </div>
// //       <div className="flex items-center space-x-6">
// //         <div className="flex items-center space-x-2">
// //           <p className="text-sm font-medium">Rows per page</p>
// //           <Select
// //             value={`${table.getState().pagination.pageSize}`}
// //             onValueChange={handlePageSizeChange}
// //           >
// //             <SelectTrigger className="h-8 w-[70px]">
// //               <SelectValue />
// //             </SelectTrigger>
// //             <SelectContent side="top">
// //               {pageSizeOptions.map((size) => (
// //                 <SelectItem key={size} value={`${size}`}>
// //                   {size}
// //                 </SelectItem>
// //               ))}
// //             </SelectContent>
// //           </Select>
// //         </div>
// //         <div className="flex w-[100px] items-center justify-center text-sm font-medium">
// //           Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
// //         </div>
// //         <div className="flex items-center space-x-2">
// //           <Button
// //             variant="outline"
// //             className="h-8 w-8 p-0"
// //             onClick={() => onPageChange(0)}
// //             disabled={!table.getCanPreviousPage()}
// //           >
// //             <ChevronsLeft />
// //           </Button>
// //           <Button
// //             variant="outline"
// //             className="h-8 w-8 p-0"
// //             onClick={() => onPageChange(table.getState().pagination.pageIndex - 1)}
// //             disabled={!table.getCanPreviousPage()}
// //           >
// //             <ChevronLeft />
// //           </Button>
// //           <Button
// //             variant="outline"
// //             className="h-8 w-8 p-0"
// //             onClick={() => onPageChange(table.getState().pagination.pageIndex + 1)}
// //             disabled={!table.getCanNextPage()}
// //           >
// //             <ChevronRight />
// //           </Button>
// //           <Button
// //             variant="outline"
// //             className="h-8 w-8 p-0"
// //             onClick={() => onPageChange(table.getPageCount() - 1)}
// //             disabled={!table.getCanNextPage()}
// //           >
// //             <ChevronsRight />
// //           </Button>
// //         </div>
// //       </div>
// //     </div>
// //   );
// // }

// // export default Pagination;



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

interface PaginationProps<TData> {
    table: Table<TData>;
    total: number;
    onPageChange: (page: number) => void;
    onPageSizeChange: (value: string) => void;
    name: string;
    pagination: {
        data: TData[];
        links: { first: string; last: string; next: string | null; prev: string | null };
        meta: { current_page: number; from: number; last_page: number; per_page: number; to: number; total: number };
    };
}

function Pagination<TData>({ table, total, onPageChange, onPageSizeChange, name, pagination }: PaginationProps<TData>) {
    const pageSizeOptions = [10, 20, 30, 40, 50, 100];

    const handlePageSizeChange = (value: string) => {
        onPageSizeChange(value);
    };

    return (
        <div className="flex items-center justify-between mt-6 mb-10">
            <div className="flex-1 text-sm">
                Showing {pagination.meta.from || 1} to {pagination.meta.to || table.getState().pagination.pageSize} of {total} rows
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
                    Page {pagination.meta.current_page} of {pagination.meta.last_page}
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