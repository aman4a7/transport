import { render, screen, fireEvent } from '@testing-library/react';
import { describe, test, expect, vi } from 'vitest';
import { axe } from 'vitest-axe';
import { DataTable } from '../DataTable';
import type { ColumnDef } from '@/shared/types/table';

interface MockItem {
  id: number;
  name: string;
  value: string;
  [key: string]: unknown;
}

const mockData: MockItem[] = [
  { id: 1, name: 'Alpha', value: '100' },
  { id: 2, name: 'Beta', value: '200' },
  { id: 3, name: 'Gamma', value: '300' },
];

const mockColumns: ColumnDef<MockItem>[] = [
  { id: 'id', header: 'ID', accessorKey: 'id', cell: ({ getValue }) => String(getValue()) as never },
  { id: 'name', header: 'Name', accessorKey: 'name', cell: ({ getValue }) => getValue() as never },
  { id: 'value', header: 'Value', accessorKey: 'value', cell: ({ getValue }) => getValue() as never },
];

describe('DataTable', () => {
  test('renders rows correctly given data and columns', () => {
    render(<DataTable data={mockData} columns={mockColumns} />);

    expect(screen.getByText('Alpha')).toBeTruthy();
    expect(screen.getByText('Beta')).toBeTruthy();
    expect(screen.getByText('Gamma')).toBeTruthy();
    expect(screen.getByText('ID')).toBeTruthy();
    expect(screen.getByText('Name')).toBeTruthy();
    expect(screen.getByText('Value')).toBeTruthy();
  });

  test('shows LoadingState when isLoading is true', () => {
    const { container } = render(<DataTable data={[]} columns={mockColumns} isLoading />);

    expect(container.querySelector('[aria-busy="true"]')).toBeTruthy();
  });

  test('shows EmptyState when data is empty array', () => {
    render(<DataTable data={[]} columns={mockColumns} emptyTitle="Nothing here" />);

    expect(screen.getByText('Nothing here')).toBeTruthy();
  });

  test('shows error state with retry button when isError is true', () => {
    const onRetry = vi.fn();
    render(
      <DataTable data={[]} columns={mockColumns} isError errorMessage="Something broke" onRetry={onRetry} />,
    );

    expect(screen.getByText('Something broke')).toBeTruthy();
    const retryBtn = screen.getByText('Retry');
    expect(retryBtn).toBeTruthy();
    fireEvent.click(retryBtn);
    expect(onRetry).toHaveBeenCalledTimes(1);
  });

  test('error state shows default message when errorMessage is not provided', () => {
    render(<DataTable data={[]} columns={mockColumns} isError />);

    expect(screen.getByText('An error occurred while loading data.')).toBeTruthy();
  });

  test('error state does not show retry button when onRetry is not provided', () => {
    render(<DataTable data={[]} columns={mockColumns} isError />);

    expect(screen.queryByText('Retry')).toBeNull();
  });

  test('search input calls onSearch with typed value', () => {
    const onSearch = vi.fn();
    render(
      <DataTable data={mockData} columns={mockColumns} onSearch={onSearch} />,
    );

    const searchInput = screen.getByLabelText('Search');
    fireEvent.change(searchInput, { target: { value: 'Alpha' } });
    expect(onSearch).toHaveBeenCalledWith('Alpha');
  });

  test('pagination next button calls onPageChange with correct page', () => {
    const onPageChange = vi.fn();
    render(
      <DataTable data={mockData} columns={mockColumns} page={1} pageCount={3} onPageChange={onPageChange} />,
    );

    const nextBtn = screen.getByLabelText('Next page');
    fireEvent.click(nextBtn);
    expect(onPageChange).toHaveBeenCalledWith(2);
  });

  test('pagination previous button calls onPageChange with correct page', () => {
    const onPageChange = vi.fn();
    render(
      <DataTable data={mockData} columns={mockColumns} page={2} pageCount={3} onPageChange={onPageChange} />,
    );

    const prevBtn = screen.getByLabelText('Previous page');
    fireEvent.click(prevBtn);
    expect(onPageChange).toHaveBeenCalledWith(1);
  });

  test('previous button is disabled on page 1', () => {
    render(
      <DataTable data={mockData} columns={mockColumns} page={1} pageCount={3} />,
    );

    expect(screen.getByLabelText('Previous page')).toBeDisabled();
  });

  test('next button is disabled on last page', () => {
    render(
      <DataTable data={mockData} columns={mockColumns} page={3} pageCount={3} />,
    );

    expect(screen.getByLabelText('Next page')).toBeDisabled();
  });

  test('row action button calls onClick with row data and does not trigger onRowClick', () => {
    const actionClick = vi.fn();
    const rowClick = vi.fn();

    render(
      <DataTable
        data={mockData}
        columns={mockColumns}
        actions={[{ label: 'Edit', onClick: actionClick }]}
        onRowClick={rowClick}
      />,
    );

    const editBtn = screen.getAllByLabelText('Edit')[0];
    fireEvent.click(editBtn);
    expect(actionClick).toHaveBeenCalledWith(mockData[0]);
    expect(rowClick).not.toHaveBeenCalled();
  });

  test('renders page count text when pageCount is provided', () => {
    render(
      <DataTable data={mockData} columns={mockColumns} page={1} pageCount={3} total={30} />,
    );

    expect(screen.getByText(/Page 1 of 3/)).toBeTruthy();
    expect(screen.getByText(/30 total/)).toBeTruthy();
  });

  test('hides pagination when pageCount is 1 or less', () => {
    const { container } = render(
      <DataTable data={mockData} columns={mockColumns} page={1} pageCount={1} />,
    );

    expect(container.querySelector('.pagination-controls')).toBeNull();
  });

  test('has no accessibility violations', async () => {
    const { container } = render(<DataTable data={mockData} columns={mockColumns} />);
    const results = await axe(container);
    expect(results.violations).toHaveLength(0);
  });
});
