import type { CreateSaleInput, Sale } from '../types';
import { request } from './api';

// POST /api/sales: o backend recalcula tudo e devolve o comprovante oficial
export function createSale(input: CreateSaleInput): Promise<Sale> {
  return request<Sale>('/sales', { method: 'POST', body: JSON.stringify(input) });
}

// GET /api/sales/{id}
export function getSale(id: number): Promise<Sale> {
  return request<Sale>(`/sales/${id}`);
}
