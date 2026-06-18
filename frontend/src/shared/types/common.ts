export interface PaginationMeta {
  current_page: number;
  total: number;
  per_page: number;
  last_page: number;
}

export interface PaginationParams {
  page?: number;
  per_page?: number;
  search?: string;
  sort_by?: string;
  sort_dir?: 'asc' | 'desc';
}
