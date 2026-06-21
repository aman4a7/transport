import { render } from '@testing-library/react';
import { test, expect } from 'vitest';
import { axe } from 'vitest-axe';
import { EmptyState } from '../EmptyState';

test('EmptyState renders title', () => {
  const { getByText } = render(<EmptyState title="No data" />);

  expect(getByText('No data')).toBeTruthy();
});

test('EmptyState renders description when provided', () => {
  const { getByText } = render(
    <EmptyState title="No data" description="There is nothing to show." />,
  );

  expect(getByText('There is nothing to show.')).toBeTruthy();
});

test('EmptyState renders action button when label and handler provided', () => {
  const { getByText } = render(
    <EmptyState title="No data" actionLabel="Add Item" onAction={() => {}} />,
  );

  expect(getByText('Add Item')).toBeTruthy();
});

test('EmptyState has no accessibility violations', async () => {
  const { container } = render(<EmptyState title="No data" />);
  const results = await axe(container);
  expect(results.violations).toHaveLength(0);
});
