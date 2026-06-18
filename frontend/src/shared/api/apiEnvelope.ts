import axiosClient from './axiosClient';

export async function fetchCsrfCookie(): Promise<void> {
  await axiosClient.get('/sanctum/csrf-cookie');
}
