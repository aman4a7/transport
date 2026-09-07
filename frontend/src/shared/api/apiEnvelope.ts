import axios from 'axios';

export async function fetchCsrfCookie(): Promise<void> {
  await axios.get('/sanctum/csrf-cookie', {
    withCredentials: true,
    headers: {
      Accept: 'application/json',
    },
  });
}

