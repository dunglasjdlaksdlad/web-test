import type { ColumnSort, Row, RowData } from "@tanstack/react-table";

// Tăng cường ColumnMeta trong @tanstack/react-table
declare module "@tanstack/react-table" {
    interface ColumnMeta<TData extends RowData, TValue> {
        label?: string; // Nhãn hiển thị của cột
        placeholder?: string; // Placeholder cho input filter
        variant?: FilterVariant; // Kiểu filter (text, number, v.v.)
        options?: Option[]; // Danh sách tùy chọn cho select/multiSelect
        range?: [number, number]; // Khoảng giá trị cho filter range
        unit?: string; // Đơn vị (nếu có, e.g., "kg", "m")
        icon?: React.FC<React.SVGProps<SVGSVGElement>>; // Icon tùy chỉnh
    }
}

// Định nghĩa Option cho select/multiSelect
export interface Option {
    label: string; // Nhãn hiển thị
    value: string; // Giá trị thực tế
    count?: number; // Số lượng (tùy chọn, dùng cho thống kê)
    icon?: React.FC<React.SVGProps<SVGSVGElement>>; // Icon tùy chỉnh
}

// Các kiểu filter operator
export type FilterOperator =
    | "equals" // Bằng
    | "notEquals" // Không bằng
    | "contains" // Chứa
    | "notContains" // Không chứa
    | "startsWith" // Bắt đầu bằng
    | "endsWith" // Kết thúc bằng
    | "isEmpty" // Trống
    | "isNotEmpty" // Không trống
    | "greaterThan" // Lớn hơn
    | "lessThan" // Nhỏ hơn
    | "isBetween"; // Trong khoảng

// Các kiểu variant cho filter
export type FilterVariant =
    | "text" // Văn bản
    | "number" // Số
    | "range" // Khoảng giá trị
    | "boolean" // True/False
    | "select" // Lựa chọn đơn
    | "multiSelect" // Lựa chọn nhiều
    | "date" // Ngày
    | "dateRange"; // Khoảng ngày

// Các kiểu join operator (nếu cần kết hợp nhiều filter)
export type JoinOperator = "and" | "or";

// ExtendedColumnSort: Mở rộng ColumnSort với id cụ thể
export interface ExtendedColumnSort<TData> extends Omit<ColumnSort, "id"> {
    id: Extract<keyof TData, string>; // ID là một key của TData
}

// ExtendedColumnFilter: Mở rộng filter với id cụ thể
export interface ExtendedColumnFilter<TData> {
    id: Extract<keyof TData, string>; // ID là một key của TData
    value: string | string[]; // Giá trị filter (có thể là chuỗi hoặc mảng)
    variant: FilterVariant; // Kiểu filter
    operator: FilterOperator; // Toán tử filter
    filterId: string; // ID duy nhất của filter
}

// DataTableRowAction: Hành động trên hàng
export interface DataTableRowAction<TData> {
    row: Row<TData>; // Hàng dữ liệu
    variant: "update" | "delete"; // Loại hành động
}

export default {};