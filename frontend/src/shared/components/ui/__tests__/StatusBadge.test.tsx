import { render } from '@testing-library/react';
import { test, expect } from 'vitest';
import { axe } from 'vitest-axe';
import { StatusBadge } from '../StatusBadge';

test('StatusBadge renders with variant prop', () => {
  const { container } = render(<StatusBadge variant="success" />);

  expect(container.textContent).toContain('success');
});

test('StatusBadge renders with status prop', () => {
  const { getByText } = render(<StatusBadge status="active" />);

  expect(getByText('active')).toBeTruthy();
});

test('StatusBadge renders with label prop', () => {
  const { getByText } = render(<StatusBadge label="Active Vehicles" />);

  expect(getByText('Active Vehicles')).toBeTruthy();
});

test('StatusBadge has no accessibility violations', async () => {
  const { container } = render(<StatusBadge status="active" />);
  const results = await axe(container);
  expect(results.violations).toHaveLength(0);
});
