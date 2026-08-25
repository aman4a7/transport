import type { Row, RowData } from '@tanstack/react-table';
import type { ReactNode } from 'react';

declare module '@tanstack/react-table' {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  interface ColumnMeta<TData extends RowData, TValue> {
    align?: 'left' | 'center' | 'right';
  }
}

export type SortDir = 'asc' | 'desc';

export interface SortState {
  id: string;
  desc: boolean;
}

export interface ColumnDef<T> {
  id?: string;
  header: string;
  accessorKey?: keyof T & string;
  accessorFn?: (row: T) => unknown;
  cell?: (props: { row: Row<T>; getValue: () => unknown }) => ReactNode;
  enableSorting?: boolean;
  enableHiding?: boolean;
  meta?: {
    align?: 'left' | 'center' | 'right';
  };
  size?: number;
}

export interface ActionDef<T> {
  label: string;
  icon?: ReactNode;
  onClick: (row: T) => void;
  disabled?: (row: T) => boolean;
  show?: (row: T) => boolean;
  variant?: 'primary' | 'danger' | 'neutral';
}

export interface DataTableProps<T> {
  data: T[];
  columns: ColumnDef<T>[];
  actions?: ActionDef<T>[];
  isLoading?: boolean;
  isError?: boolean;
  errorMessage?: string;
  onRetry?: () => void;
  emptyTitle?: string;
  emptyDescription?: string;
  emptyActionLabel?: string;
  emptyAction?: () => void;
  searchPlaceholder?: string;
  onSearch?: (query: string) => void;
  searchQuery?: string;
  page?: number;
  pageCount?: number;
  total?: number;
  onPageChange?: (page: number) => void;
  sortable?: boolean;
  getRowId?: (row: T) => string | number;
  onRowClick?: (row: T) => void;
}
