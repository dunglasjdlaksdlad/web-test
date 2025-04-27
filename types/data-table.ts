// src/types/data-table.ts
import type { ColumnSort, Row, RowData } from '@tanstack/react-table';

import { FileSpreadsheetIcon } from "lucide-react"; // Đảm bảo cài đặt lucide-react

export const filterMenuItems = [
  {
    label: "Advanced filters",
    value: "advancedFilters" as const,
    icon: FileSpreadsheetIcon,
    tooltipTitle: "Advanced filters",
    tooltipDescription: "Airtable like advanced filters for filtering rows.",
  },
];

declare module '@tanstack/react-table' {
  interface ColumnMeta<TData extends RowData, TValue> {
    label?: string;
    placeholder?: string;
    variant?: FilterVariant;
    options?: Option[];
    range?: [number, number];
    unit?: string;
    icon?: React.FC<React.SVGProps<SVGSVGElement>>;
  }
}

export interface Option {
  label: string;
  value: string;
  count?: number;
  icon?: React.FC<React.SVGProps<SVGSVGElement>>;
}

export type FilterOperator = 'contains' | 'equals' | 'isEmpty' | 'isNotEmpty' | 'isBetween';
export type FilterVariant = 'text' | 'number' | 'range' | 'boolean' | 'select' | 'multiSelect' | 'date' | 'dateRange';
export type JoinOperator = 'and' | 'or';

export interface ExtendedColumnSort<TData> extends Omit<ColumnSort, 'id'> {
  id: Extract<keyof TData, string>;
}

export interface ExtendedColumnFilter<TData> {
  id: Extract<keyof TData, string>;
  value: string | string[];
  variant: FilterVariant;
  operator: FilterOperator;
  filterId: string;
}

export interface DataTableRowAction<TData> {
  row: Row<TData>;
  variant: 'update' | 'delete';
}