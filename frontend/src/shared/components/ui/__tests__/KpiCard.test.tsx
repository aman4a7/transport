import { render } from '@testing-library/react';
import { test, expect } from 'vitest';
import { axe } from 'vitest-axe';
import { KpiCard } from '../KpiCard';

test('KpiCard renders label and value', () => {
  const { getByText } = render(<KpiCard label="Total Vehicles" value="42" />);

  expect(getByText('Total Vehicles')).toBeTruthy();
  expect(getByText('42')).toBeTruthy();
});

test('KpiCard shows skeleton when loading', () => {
  const { container } = render(<KpiCard label="Total Vehicles" value="42" isLoading />);

  expect(container.querySelector('.skeleton')).toBeTruthy();
  expect(container.querySelector('[aria-busy="true"]')).toBeTruthy();
});

test('KpiCard has no accessibility violations', async () => {
  const { container } = render(<KpiCard label="Total Vehicles" value="42" />);
  const results = await axe(container);
  expect(results.violations).toHaveLength(0);
});
