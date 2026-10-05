import type { Product } from '../types';
import { request } from './api';

// GET /api/products?search=...  (a busca por nome ou código é feita pelo backend)
export function getProducts(search: string, signal?: AbortSignal): Promise<Product[]> {
  const query = search ? `?search=${encodeURIComponent(search)}` : '';
  return request<Product[]>(`/products${query}`, { signal });
}
