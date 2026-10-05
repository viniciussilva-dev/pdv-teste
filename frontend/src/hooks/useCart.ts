import { useState } from 'react';
import type { CartItem, Product } from '../types';

// Mesmo limite do backend (999 por item). O backend continua sendo quem decide.
export const MAX_QUANTITY = 999;

// Estado e ações do carrinho. O total aqui serve SÓ para exibição em tempo real:
// o valor oficial da venda é calculado e devolvido pelo backend.
export function useCart() {
  const [items, setItems] = useState<CartItem[]>([]);

  // Produto indisponível nunca entra. Produto repetido soma na mesma linha
  // (o backend recusa o mesmo produto duas vezes na venda).
  function add(product: Product) {
    if (!product.available) return;
    setItems((current) => {
      const found = current.find((item) => item.product.id === product.id);
      if (!found) return [...current, { product, quantity: 1 }];
      return current.map((item) =>
        item.product.id === product.id ? { ...item, quantity: Math.min(item.quantity + 1, MAX_QUANTITY) } : item,
      );
    });
  }

  // Quantidade sempre entre 1 e 999; para tirar o produto use remove()
  function changeQuantity(productId: number, delta: number) {
    setItems((current) =>
      current.map((item) =>
        item.product.id === productId
          ? { ...item, quantity: Math.min(Math.max(item.quantity + delta, 1), MAX_QUANTITY) }
          : item,
      ),
    );
  }

  const increment = (productId: number) => changeQuantity(productId, 1);
  const decrement = (productId: number) => changeQuantity(productId, -1);
  const remove = (productId: number) => setItems((current) => current.filter((item) => item.product.id !== productId));
  const clear = () => setItems([]);

  const total = items.reduce((sum, item) => sum + item.product.price * item.quantity, 0);

  return { items, total, add, increment, decrement, remove, clear };
}
