import { RowData } from '@tanstack/react-table';

declare module '@tanstack/react-table' {
    interface ColumnMeta<TData extends RowData, TValue> {
        filterVariant?: 'text' | 'number' | 'range' | 'select' | 'multiSelect' | 'date' | 'dateRange';
    }
}

export { };
