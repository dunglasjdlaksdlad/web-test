import DataTable from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import ColumnHeader from '@/components/ui/column-header';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  FileInput,
  FileUploader,
  FileUploaderContent,
  FileUploaderItem,
} from '@/components/ui/file-upload';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, CloudUpload, MoreHorizontal, Paperclip } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'File Manager', href: '/filemanager' },
];

const FileSvgDraw = () => (
  <>
    <svg
      className="mb-3 h-8 w-8 text-gray-500 dark:text-gray-400"
      aria-hidden="true"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 20 16"
    >
      <path
        stroke="currentColor"
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeWidth="2"
        d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"
      />
    </svg>
    <p className="mb-1 text-sm text-gray-500 dark:text-gray-400">
      <span className="font-semibold">Click to upload</span> or drag and drop
    </p>
    <p className="text-xs text-gray-500 dark:text-gray-400">SVG, PNG, JPG or GIF</p>
  </>
);

const name = 'filemanager';

export default function FileManager({ data }: any) {
  const user = usePage().props.auth.user;
  const [files, setFiles] = useState<File[] | null>(null);
  const [fileProgress, setFileProgress] = useState<Record<string, { status: string; progress: number }>>({});

  const dropZoneConfig = {
    maxFiles: 5,
    maxSize: 1024 * 1024 * 1000, // 1GB
    multiple: true,
  };

  useEffect(() => {
    const channel = window.Echo.private(`uploadFile.${user.id}`);
    const listener = (data: any) => {
      const { fileName, status, file } = data.data;
      setFileProgress((prev) => ({
        ...prev,
        [fileName]: { status, progress: status === 'success' ? 100 : prev[fileName]?.progress || 0 },
      }));
      if (status === 'success') {

        setTimeout(() => {
          setFiles((prevFiles) => {
            if (!prevFiles) return null;
            return prevFiles.filter((f) => f.name !== fileName);
          });
          setFileProgress((prev) => {
            const updatedProgress = { ...prev };
            delete updatedProgress[fileName];
            return updatedProgress;
          });
          router.reload();
        }, 5000);
      }
    };

    channel.listen('UploadFileRequestReceived', listener);
    return () => channel.stopListening('UploadFileRequestReceived', listener);
  }, [user.id, data]);

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
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label="Select row"
          />
        ),
        enableSorting: false,
        enableHiding: false,
      },
      { header: 'UUID', accessorKey: 'uuid', search: true },
      { header: 'Name', accessorKey: 'name', search: true },
      { header: 'Created by', accessorKey: 'created_by', search: true },
      // { header: 'Count', accessorKey: 'count', search: true },
      {
        header: ({ column }) => (
          <Button
            variant="link"
            onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
          >
            Created at
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        ),
        accessorKey: 'created_at',
        search: true,
      },
      {
        id: 'actions',
        header: ({ column }) => <ColumnHeader column={column} title="Actions" />,
        enableSorting: false,
        cell: ({ row }) => (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open Menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
            </DropdownMenuContent>
          </DropdownMenu>
        ),
      },
    ],
    []
  );

  console.log(data);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="File Manager" />
      <div className="h-full rounded-xl p-4">
        <FileUploader
          value={files}
          onValueChange={setFiles}
          dropzoneOptions={dropZoneConfig}
          setFileProgress={setFileProgress}
          className="relative mb-4 rounded-lg p-0.5"
        >
          <FileInput id="fileInput" className="outline-1 btn-outline-light outline-dashed">
            <div className="flex w-full flex-col items-center justify-center p-8 ">
              <CloudUpload className="h-10 w-10 text-white" />
              <p className="mb-1 text-sm text-white">
                <span className="font-semibold">Click to upload</span> or drag and drop
              </p>
              <p className="text-xs text-white">CSV</p>
            </div>
          </FileInput>
          <FileUploaderContent>
            {files?.map((file, i) => (
              // <FileUploaderItem key={i} index={i}>
              <FileUploaderItem key={i} index={i} fileStatus={fileProgress[file.name]?.status}>
                <Paperclip className="h-4 w-4 stroke-current" />
                <span>{file.name}</span>
                {fileProgress[file.name]?.status === 'success' ? (
                  <span className="text-green-500">✔ Xong</span>
                ) : fileProgress[file.name]?.status === 'error' ? (
                  <span className="text-red-500">❌ Lỗi</span>
                ) : (
                  <span className="text-yellow-500">⏳ Đang xử lý...</span>
                )}
                <span
                  className={
                    fileProgress[file.name]?.status === 'success'
                      ? 'text-green-500'
                      : fileProgress[file.name]?.status === 'error'
                        ? 'text-red-500'
                        : 'text-yellow-500'
                  }
                >
                  {fileProgress[file.name]?.progress || 0}%
                </span>
              </FileUploaderItem>
            ))}
          </FileUploaderContent>
        </FileUploader>
        <DataTable columns={columns} data={data.data} pagination={data} name={name} />
      </div>
    </AppLayout>
  );
}