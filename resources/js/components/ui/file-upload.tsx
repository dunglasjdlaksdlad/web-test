import { Input } from "@/components/ui/input";
import { cn } from "@/lib/utils";
import {
  Dispatch,
  SetStateAction,
  createContext,
  forwardRef,
  useCallback,
  useContext,
  useEffect,
  useState,
} from "react";
import {
  useDropzone,
  DropzoneState,
  FileRejection,
  DropzoneOptions,
} from "react-dropzone";
import { toast } from "sonner";
import { Trash2 } from "lucide-react";
import { buttonVariants } from "@/components/ui/button";
import axios from "axios";

type DirectionOptions = "rtl" | "ltr" | undefined;

type FileUploaderContextType = {
  dropzoneState: DropzoneState;
  isLOF: boolean;
  isFileTooBig: boolean;
  removeFileFromSet: (index: number) => void;
  activeIndex: number;
  setActiveIndex: Dispatch<SetStateAction<number>>;
  orientation: "horizontal" | "vertical";
  direction: DirectionOptions;
};

const FileUploaderContext = createContext<FileUploaderContextType | null>(null);

export const useFileUpload = () => {
  const context = useContext(FileUploaderContext);
  if (!context) throw new Error("useFileUpload must be used within a FileUploaderProvider");
  return context;
};

type FileUploaderProps = {
  value: File[] | null;
  reSelect?: boolean;
  onValueChange: (value: File[] | null) => void;
  dropzoneOptions: DropzoneOptions;
  orientation?: "horizontal" | "vertical";
  setFileProgress: Dispatch<SetStateAction<Record<string, { status: string; progress: number }>>>;
};

export const FileUploader = forwardRef<
  HTMLDivElement,
  FileUploaderProps & React.HTMLAttributes<HTMLDivElement>
>(
  (
    {
      className,
      dropzoneOptions,
      value,
      onValueChange,
      reSelect,
      orientation = "vertical",
      setFileProgress,
      children,
      dir,
      ...props
    },
    ref
  ) => {
    const [isFileTooBig, setIsFileTooBig] = useState(false);
    const [isLOF, setIsLOF] = useState(false);
    const [activeIndex, setActiveIndex] = useState(-1);
    const { accept = { "image/*": [".jpg", ".jpeg", ".png", ".gif"] }, maxFiles = 1, maxSize = 1000 * 1024 * 1024, multiple = true } = dropzoneOptions;
    const reSelectAll = maxFiles === 1 ? true : reSelect;
    const direction: DirectionOptions = dir === "rtl" ? "rtl" : "ltr";

    const removeFileFromSet = useCallback(
      (i: number) => {
        if (!value) return;
        const fileNameToRemove = value[i].name;
        const newFiles = value.filter((_, index) => index !== i);
        onValueChange(newFiles);
        setFileProgress((prev) => {
          const updatedProgress = { ...prev };
          delete updatedProgress[fileNameToRemove];
          return updatedProgress;
        });
      },
      [value, onValueChange, setFileProgress]
    );

    const handleKeyDown = useCallback(
      (e: React.KeyboardEvent<HTMLDivElement>) => {
        e.preventDefault();
        e.stopPropagation();
        if (!value) return;

        const moveNext = () => setActiveIndex((prev) => (prev + 1 > value.length - 1 ? 0 : prev + 1));
        const movePrev = () => setActiveIndex((prev) => (prev - 1 < 0 ? value.length - 1 : prev - 1));
        const prevKey = orientation === "horizontal" ? (direction === "ltr" ? "ArrowLeft" : "ArrowRight") : "ArrowUp";
        const nextKey = orientation === "horizontal" ? (direction === "ltr" ? "ArrowRight" : "ArrowLeft") : "ArrowDown";

        if (e.key === nextKey) moveNext();
        else if (e.key === prevKey) movePrev();
        else if (e.key === "Enter" || e.key === "Space") {
          if (activeIndex === -1) dropzoneState.inputRef.current?.click();
        } else if (e.key === "Delete" || e.key === "Backspace") {
          if (activeIndex !== -1) {
            removeFileFromSet(activeIndex);
            if (value.length - 1 === 0) setActiveIndex(-1);
            else movePrev();
          }
        } else if (e.key === "Escape") setActiveIndex(-1);
      },
      [value, activeIndex, removeFileFromSet]
    );

    const CHUNK_SIZE = 10 * 1024 * 1024;

    const uploadChunk = async (file: File, chunk: Blob, chunkIndex: number, totalChunks: number) => {
      const formData = new FormData();
      formData.append("files[]", chunk, `${file.name}.part${chunkIndex}`);
      formData.append("fileName", file.name);
      formData.append("chunkIndex", chunkIndex.toString());
      formData.append("totalChunks", totalChunks.toString());

      try {
        const response = await axios.post(route('filemanager.store'), formData, {
          headers: { "Content-Type": "multipart/form-data" },
          onUploadProgress: (progressEvent) => {
            const percent = Math.round((progressEvent.loaded * 100) / (progressEvent.total || 1));
            setFileProgress((prev) => ({
              ...prev,
              [file.name]: { status: "processing", progress: Math.round((chunkIndex / totalChunks) * 100 + (percent / totalChunks)) },
            }));
          },
        });
        return response.data;
      } catch (error) {
        setFileProgress((prev) => ({ ...prev, [file.name]: { status: "error", progress: prev[file.name]?.progress || 0 } }));
        throw error;
      }
    };

    const handleUpload = async (files: File[]) => {
      for (const file of files) {
        setFileProgress((prev) => ({ ...prev, [file.name]: { status: "processing", progress: 0 } }));
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

        try {
          for (let i = 0; i < totalChunks; i++) {
            const start = i * CHUNK_SIZE;
            const end = Math.min(start + CHUNK_SIZE, file.size);
            const chunk = file.slice(start, end);
            await uploadChunk(file, chunk, i, totalChunks);
          }
          toast.success(`Uploaded ${file.name} successfully!`);
        } catch (error) {
          toast.error(`Error uploading ${file.name}`);
          console.error(error);
        }
      }
    };

    const onDrop = useCallback(
      (acceptedFiles: File[], rejectedFiles: FileRejection[]) => {
        if (!acceptedFiles || acceptedFiles.length === 0) {
          toast.error("File error, probably too big");
          return;
        }

        let newValues: File[] = value ? [...value] : [];
        if (reSelectAll) newValues = [];

        const renamedFiles = acceptedFiles.map((file) => {
          let fileName = file.name;
          let baseName = fileName.replace(/\(\d+\)$/, "").trim();
          let ext = fileName.includes(".") ? fileName.substring(fileName.lastIndexOf(".")) : "";
          if (ext) baseName = baseName.slice(0, -ext.length);

          let counter = 1;
          let newFileName = fileName;
          while (newValues.some((f) => f.name === newFileName)) {
            newFileName = `${baseName} (${counter})${ext}`;
            counter++;
          }
          return new File([file], newFileName, { type: file.type });
        });

        newValues = [...newValues, ...renamedFiles.slice(0, maxFiles - newValues.length)];
        onValueChange(newValues);
        handleUpload(renamedFiles);

        if (rejectedFiles.length > 0) {
          rejectedFiles.forEach((rejected) => {
            if (rejected.errors[0]?.code === "file-too-large") {
              toast.error(`File is too large. Max size is ${maxSize / 1024 / 1024}MB`);
            } else if (rejected.errors[0]?.message) {
              toast.error(rejected.errors[0].message);
            }
          });
        }
      },
      [value, reSelectAll, maxFiles, maxSize, onValueChange]
    );

    useEffect(() => {
      if (!value) return;
      setIsLOF(value.length >= maxFiles);
    }, [value, maxFiles]);

    const dropzoneState = useDropzone({
      ...dropzoneOptions,
      onDrop,
      onDropRejected: () => setIsFileTooBig(true),
      onDropAccepted: () => setIsFileTooBig(false),
    });

    return (
      <FileUploaderContext.Provider
        value={{ dropzoneState, isLOF, isFileTooBig, removeFileFromSet, activeIndex, setActiveIndex, orientation, direction }}
      >
        <div
          ref={ref}
          tabIndex={0}
          onKeyDownCapture={handleKeyDown}
          className={cn("grid w-full focus:outline-none overflow-hidden", className, { "gap-2": value && value.length > 0 })}
          dir={dir}
          {...props}
        >
          {children}
        </div>
      </FileUploaderContext.Provider>
    );
  }
);

FileUploader.displayName = "FileUploader";

export const FileUploaderContent = forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ children, className, ...props }, ref) => {
    const { orientation } = useFileUpload();
    return (
      <div className={cn("w-full px-1")} ref={ref} aria-description="content file holder">
        <div
          {...props}
          className={cn(
            "flex rounded-xl gap-1",
            orientation === "horizontal" ? "flex-row flex-wrap" : "flex-col",
            className
          )}
        >
          {children}
        </div>
      </div>
    );
  }
);

FileUploaderContent.displayName = "FileUploaderContent";

// export const FileUploaderItem = forwardRef<HTMLDivElement, { index: number } & React.HTMLAttributes<HTMLDivElement>>(
//   ({ className, index, children, ...props }, ref) => {
//     const { removeFileFromSet, activeIndex, direction } = useFileUpload();
//     const isSelected = index === activeIndex;
//     return (
//       <div
//         ref={ref}
//         className={cn(
//           buttonVariants({ variant: "ghost" }),
//           "h-8 p-1 justify-between cursor-pointer relative",
//           className,
//           isSelected ? "bg-muted" : ""
//         )}
//         {...props}
//       >
//         <div className="font-medium leading-none tracking-tight flex items-center gap-1.5 h-full w-full">{children}</div>
//         <button
//           type="button"
//           className={cn("absolute", direction === "rtl" ? "top-1 left-1" : "top-1 right-1")}
//           onClick={() => removeFileFromSet(index)}
//         >
//           <span className="sr-only">remove item {index}</span>
//           <RemoveIcon className="w-4 h-4 hover:stroke-destructive duration-200 ease-in-out" />
//         </button>
//       </div>
//     );
//   }
// );

export const FileUploaderItem = forwardRef<
  HTMLDivElement,
  { index: number; fileStatus?: string } & React.HTMLAttributes<HTMLDivElement>
>(
  ({ className, index, fileStatus, children, ...props }, ref) => {
    const { removeFileFromSet, activeIndex, direction } = useFileUpload();
    const isSelected = index === activeIndex;

    return (
      <div
        ref={ref}
        className={cn(
          buttonVariants({ variant: "ghost" }),
          "h-8 p-1 justify-between relative",
          className,
          isSelected ? "bg-muted" : ""
        )}
        {...props}
      >
        <div className="font-medium leading-none tracking-tight flex items-center gap-1.5 h-full w-full">
          {children}
        </div>
        <button onClick={() => removeFileFromSet(index)}>
          <div className="p-1 h-8 w-8 flex items-center justify-center cursor-pointer relative">
            {fileStatus === "success" && (
              <svg className="absolute w-full h-full" viewBox="0 0 10 10">
                <circle
                  cx="50%"
                  cy="50%"
                  r="45%"
                  fill="none"
                  stroke="currentColor"
                  strokeWidth="1"
                  className="progress-circle"
                />
              </svg>
            )}
            <small className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
              X
            </small>
          </div>
        </button>
      </div>
    );
  }
);


FileUploaderItem.displayName = "FileUploaderItem";

export const FileInput = forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, children, ...props }, ref) => {
    const { dropzoneState, isFileTooBig, isLOF } = useFileUpload();
    const rootProps = isLOF ? {} : dropzoneState.getRootProps();
    return (
      <div ref={ref} {...props} className={`relative w-full ${isLOF ? "opacity-50 cursor-not-allowed" : "cursor-pointer"}`}>
        <div
          className={cn(
            `w-full rounded-lg duration-300 ease-in-out ${dropzoneState.isDragAccept
              ? "border-green-500"
              : dropzoneState.isDragReject || isFileTooBig
                ? "border-red-500"
                : "border-gray-300"
            }`,
            className
          )}
          {...rootProps}
        >
          {children}
        </div>
        <Input
          ref={dropzoneState.inputRef}
          disabled={isLOF}
          {...dropzoneState.getInputProps()}
          className={`${isLOF ? "cursor-not-allowed" : ""}`}
        />
      </div>
    );
  }
);

FileInput.displayName = "FileInput";