import type { ReactNode } from 'react';
import type { FieldValues, Path, FieldError } from 'react-hook-form';
import type { ZodSchema } from 'zod';
import type { LucideIcon } from 'lucide-react';

export type FieldType =
  | 'text'
  | 'email'
  | 'number'
  | 'password'
  | 'textarea'
  | 'select'
  | 'date'
  | 'file'
  | 'checkbox';

export interface SelectOption {
  label: string;
  value: string | number;
}

export interface FieldConfig<T extends FieldValues> {
  name: Path<T>;
  label: string;
  type: FieldType;
  placeholder?: string;
  required?: boolean;
  helpText?: string;
  disabled?: boolean;
  options?: SelectOption[];
  icon?: LucideIcon;
  cols?: 1 | 2 | 3;
  accept?: string;
  multiple?: boolean;
}

export interface FormBuilderProps<T extends FieldValues> {
  fields: FieldConfig<T>[];
  schema: ZodSchema<T>;
  onSubmit: (data: T) => void;
  defaultValues?: Partial<T>;
  submitLabel?: string;
  cancelLabel?: string;
  onCancel?: () => void;
  isLoading?: boolean;
  layout?: 'single' | 'grid';
}

export interface FormFieldWrapperProps {
  label: string;
  required?: boolean;
  error?: FieldError;
  helpText?: string;
  icon?: LucideIcon;
  children: ReactNode;
  name: string;
}
