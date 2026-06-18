import { useState } from 'react';

interface UsePaginationOptions {
  initialPage?: number;
  initialPerPage?: number;
}

export function usePagination(options: UsePaginationOptions = {}) {
  const { initialPage = 1, initialPerPage = 15 } = options;
  const [page, setPage] = useState(initialPage);
  const [perPage] = useState(initialPerPage);

  return {
    page,
    perPage,
    nextPage: () => setPage((p) => p + 1),
    prevPage: () => setPage((p) => Math.max(1, p - 1)),
    goToPage: setPage,
    resetPage: () => setPage(1),
  };
}
