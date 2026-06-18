import { LoadingState } from './LoadingState';

interface LoadingSpinnerProps {
  size?: number;
  message?: string;
}

export function LoadingSpinner({ message }: LoadingSpinnerProps) {
  return <LoadingState variant="spinner" message={message} />;
}
