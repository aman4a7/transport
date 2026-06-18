import { useMemo, useState } from 'react';
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  flexRender,
  type SortingState,
} from '@tanstack/react-table';
import type { CSSProperties } from 'react';
import { ChevronUp, ChevronDown, ChevronsUpDown, ChevronLeft, ChevronRight, Search } from 'lucide-react';
import type { DataTableProps, ColumnDef } from '@/shared/types/table';
import { EmptyState } from './EmptyState';
import { LoadingState } from './LoadingState';

function toTanStackColumns<T>(columns: ColumnDef<T>[]) {
  return columns.map((col) => ({
    id: col.id ?? (col.accessorKey as string | undefined),
    header: col.header,
    accessorKey: col.accessorKey,
    accessorFn: col.accessorFn,
    cell: col.cell
      ? (info: { row: { original: T }; getValue: () => unknown }) =>
          col.cell!({ row: info.row as never, getValue: info.getValue })
      : undefined,
    enableSorting: col.enableSorting ?? true,
    enableHiding: col.enableHiding,
    meta: col.meta,
    size: col.size,
  }));
}

export function DataTable<T extends Record<string, unknown>>({
  data,
  columns,
  actions,
  isLoading,
  isError,
  errorMessage,
  onRetry,
  emptyTitle = 'No data found',
  emptyDescription,
  emptyActionLabel,
  emptyAction,
  searchPlaceholder = 'Search...',
  onSearch,
  searchQuery,
  page,
  pageCount,
  total,
  onPageChange,
  sortable = true,
  getRowId,
  onRowClick,
}: DataTableProps<T>) {
  const [sorting, setSorting] = useState<SortingState>([]);

  const tanStackColumns = useMemo(() => toTanStackColumns(columns), [columns]);

  const table = useReactTable({
    data,
    columns: tanStackColumns,
    state: { sorting },
    onSortingChange: sortable ? setSorting : undefined,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: sortable ? getSortedRowModel() : undefined,
    getRowId: getRowId as ((row: T) => string) | undefined,
  });

  if (isLoading) {
    return <LoadingState variant="skeleton" type="table" rows={5} />;
  }

  if (isError) {
    return (
      <div
        className="card"
        style={{ padding: 'var(--space-10)', textAlign: 'center' }}
      >
        <p style={{ color: 'var(--color-danger)', marginBottom: 'var(--space-4)' }}>
          {errorMessage ?? 'An error occurred while loading data.'}
        </p>
        {onRetry && (
          <button className="btn btn-primary" onClick={onRetry}>
            Retry
          </button>
        )}
      </div>
    );
  }

  if (data.length === 0) {
    return (
      <EmptyState
        title={emptyTitle}
        description={emptyDescription}
        actionLabel={emptyActionLabel}
        onAction={emptyAction}
      />
    );
  }

  return (
    <div>
      {onSearch && (
        <div style={{ marginBottom: 'var(--space-4)', position: 'relative' }}>
          <Search
            size={16}
            style={{
              position: 'absolute',
              left: 12,
              top: '50%',
              transform: 'translateY(-50%)',
              color: 'var(--color-text-muted)',
              pointerEvents: 'none',
            }}
            aria-hidden="true"
          />
          <input
            className="form-input"
            type="text"
            value={searchQuery ?? ''}
            onChange={(e) => onSearch(e.target.value)}
            placeholder={searchPlaceholder}
            style={{ paddingLeft: 36, maxWidth: 320 }}
            aria-label="Search"
          />
        </div>
      )}

      <div className="table-container">
        <table className="data-table">
          <thead>
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  const canSort = header.column.getCanSort();
                  const sortDir = header.column.getIsSorted();
                  return (
                    <th
                      key={header.id}
                      className={canSort ? 'sortable' : undefined}
                      onClick={canSort ? header.column.getToggleSortingHandler() : undefined}
                      style={{
                        textAlign: ((header.column.columnDef.meta as { align?: CSSProperties['textAlign'] } | undefined)?.align) ?? 'left',
                        width: header.getSize() ? header.getSize() : undefined,
                      }}
                      aria-sort={
                        sortDir === 'asc' ? 'ascending' : sortDir === 'desc' ? 'descending' : undefined
                      }
                    >
                      <span style={{ display: 'inline-flex', alignItems: 'center', gap: 4 }}>
                        {flexRender(header.column.columnDef.header, header.getContext())}
                        {canSort && (
                          <span style={{ display: 'inline-flex', color: 'var(--color-text-muted)' }}>
                            {sortDir === 'asc' ? (
                              <ChevronUp size={14} />
                            ) : sortDir === 'desc' ? (
                              <ChevronDown size={14} />
                            ) : (
                              <ChevronsUpDown size={14} />
                            )}
                          </span>
                        )}
                      </span>
                    </th>
                  );
                })}
                {actions && actions.length > 0 && (
                  <th style={{ width: 80, textAlign: 'center' }}>Actions</th>
                )}
              </tr>
            ))}
          </thead>
          <tbody>
            {table.getRowModel().rows.map((row) => (
              <tr
                key={row.id}
                onClick={onRowClick ? () => onRowClick(row.original) : undefined}
                style={onRowClick ? { cursor: 'pointer' } : undefined}
              >
                {row.getVisibleCells().map((cell) => (
                  <td
                    key={cell.id}
                    style={{
                      textAlign: ((cell.column.columnDef.meta as { align?: CSSProperties['textAlign'] } | undefined)?.align) ?? 'left',
                    }}
                  >
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </td>
                ))}
                {actions && actions.length > 0 && (
                  <td className="row-actions" style={{ justifyContent: 'center' }}>
                    {actions.map((action, idx) => {
                      const isDisabled = action.disabled?.(row.original) ?? false;
                      const btnClass =
                        action.variant === 'danger'
                          ? 'btn btn-danger btn-sm'
                          : action.variant === 'primary'
                            ? 'btn btn-primary btn-sm'
                            : 'btn btn-secondary btn-sm';
                      return (
                        <button
                          key={idx}
                          className={btnClass}
                          onClick={(e) => {
                            e.stopPropagation();
                            action.onClick(row.original);
                          }}
                          disabled={isDisabled}
                          title={action.label}
                          aria-label={action.label}
                        >
                          {action.icon}
                          <span className="sr-only">{action.label}</span>
                        </button>
                      );
                    })}
                  </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {pageCount && pageCount > 1 && (
        <div className="pagination-controls">
          <button
            className="btn btn-secondary btn-sm"
            onClick={() => onPageChange?.(Math.max(1, (page ?? 1) - 1))}
            disabled={!page || page <= 1}
            aria-label="Previous page"
          >
            <ChevronLeft size={16} />
          </button>
          <span style={{ fontSize: 'var(--text-sm)', color: 'var(--color-text-secondary)' }}>
            Page {page ?? 1} of {pageCount}
            {total !== undefined && ` (${total} total)`}
          </span>
          <button
            className="btn btn-secondary btn-sm"
            onClick={() => onPageChange?.(Math.min(pageCount, (page ?? 1) + 1))}
            disabled={!page || page >= pageCount}
            aria-label="Next page"
          >
            <ChevronRight size={16} />
          </button>
        </div>
      )}
    </div>
  );
}
