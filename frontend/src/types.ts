// === TIPOS DA API ===
// Todos os valores monetários são inteiros em CENTAVOS (R$ 18,90 = 1890).

export interface Product {
  id: number;
  name: string;
  code: string;
  description: string | null;
  price: number;
  available: boolean;
}

export type PaymentMethod = 'cash' | 'credit' | 'debit';

// Rótulos em português: o backend só conhece os valores internos
export const PAYMENT_LABELS: Record<PaymentMethod, string> = {
  cash: 'Dinheiro',
  credit: 'Crédito',
  debit: 'Débito',
};

// Item de um comprovante: usa os snapshots gravados no momento da venda
export interface SaleItem {
  product_id: number;
  product_name: string;
  unit_price: number;
  quantity: number;
  subtotal: number;
}

export interface Sale {
  id: number;
  total: number;
  payment_method: PaymentMethod;
  amount_received: number | null;
  change_amount: number;
  created_at: string;
  items: SaleItem[];
}

// O que o frontend ENVIA: só ids e quantidades. Preço, subtotal e total nunca são enviados.
export interface CreateSaleInput {
  items: { product_id: number; quantity: number }[];
  payment_method: PaymentMethod;
  amount_received?: number;
}

// Item do carrinho (estado local do frontend)
export interface CartItem {
  product: Product;
  quantity: number;
}
