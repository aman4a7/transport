import { render } from '@testing-library/react';
import { test, expect } from 'vitest';
import { axe } from 'vitest-axe';
import { z } from 'zod';
import { FormBuilder } from '../FormBuilder';

const testSchema = z.object({
  test: z.string().min(1),
});

test('FormBuilder renders without crashing', () => {
  const { container } = render(
    <FormBuilder
      schema={testSchema}
      fields={[
        { name: 'test', label: 'Test Field', type: 'text' },
      ]}
      onSubmit={async () => {}}
      submitLabel="Submit"
    />,
  );

  expect(container).toBeTruthy();
});

test('FormBuilder has no accessibility violations', async () => {
  const { container } = render(
    <FormBuilder
      schema={testSchema}
      fields={[
        { name: 'test', label: 'Test Field', type: 'text' },
      ]}
      onSubmit={async () => {}}
      submitLabel="Submit"
    />,
  );
  const results = await axe(container);
  expect(results.violations).toHaveLength(0);
});
