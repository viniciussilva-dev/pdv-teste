import { Minus, Plus, ShoppingCart, Trash2 } from 'lucide-react';
import { MAX_QUANTITY } from '../hooks/useCart';
import type { CartItem } from '../types';
import { formatCurrency } from '../utils/currency';
import { EmptyState } from './ui';

interface CartPanelProps {
  items: CartItem[];
  total: number;
  onIncrement: (productId: number) => void;
  onDecrement: (productId: number) => void;
  onRemove: (productId: number) => void;
  onCheckout: () => void;
}

const stepButton =
  'flex size-8 items-center justify-center text-slate-600 hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:text-slate-300 disabled:hover:bg-transparent';

// Carrinho: itens, controles de quantidade e o total da venda em destaque
export default function CartPanel({ items, total, onIncrement, onDecrement, onRemove, onCheckout }: CartPanelProps) {
  const count = items.reduce((sum, item) => sum + item.quantity, 0);

  return (
    <aside
      aria-label="Carrinho"
      className="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)]"
    >
      <div className="flex items-center justify-between border-b border-slate-200 px-5 py-4">
        <h2 className="text-lg font-semibold">Carrinho</h2>
        <span className="text-sm text-slate-500">
          {count} {count === 1 ? 'item' : 'itens'}
        </span>
      </div>

      {items.length === 0 ? (
        <EmptyState
          icon={<ShoppingCart className="size-8" />}
          title="Carrinho vazio"
          text="Busque um produto e toque em Adicionar para iniciar a venda."
        />
      ) : (
        <ul className="flex-1 divide-y divide-slate-100 overflow-y-auto">
          {items.map(({ product, quantity }) => (
            <li key={product.id} className="px-5 py-3">
              <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                  <p className="truncate font-medium">{product.name}</p>
                  <p className="text-sm tabular-nums text-slate-500">{formatCurrency(product.price)} cada</p>
                </div>
                <button
                  onClick={() => onRemove(product.id)}
                  aria-label={`Remover ${product.name} do carrinho`}
                  className="rounded-md p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 focus-visible:outline-2 focus-visible:outline-red-600"
                >
                  <Trash2 className="size-4" aria-hidden="true" />
                </button>
              </div>
              <div className="mt-2 flex items-center justify-between">
                <div className="flex items-center overflow-hidden rounded-lg border border-slate-200">
                  <button
                    onClick={() => onDecrement(product.id)}
                    disabled={quantity <= 1}
                    aria-label={`Diminuir quantidade de ${product.name}`}
                    className={stepButton}
                  >
                    <Minus className="size-4" aria-hidden="true" />
                  </button>
                  <span className="w-10 text-center tabular-nums" aria-live="polite">
                    {quantity}
                  </span>
                  <button
                    onClick={() => onIncrement(product.id)}
                    disabled={quantity >= MAX_QUANTITY}
                    aria-label={`Aumentar quantidade de ${product.name}`}
                    className={stepButton}
                  >
                    <Plus className="size-4" aria-hidden="true" />
                  </button>
                </div>
                <p className="font-semibold tabular-nums">{formatCurrency(product.price * quantity)}</p>
              </div>
            </li>
          ))}
        </ul>
      )}

      <div className="border-t border-slate-200 bg-slate-50 px-5 py-4">
        <div className="flex items-baseline justify-between gap-3">
          <span className="text-slate-600">Total da venda</span>
          <span className="text-3xl font-semibold tabular-nums tracking-tight">{formatCurrency(total)}</span>
        </div>
        <button
          onClick={onCheckout}
          disabled={items.length === 0}
          className="mt-4 w-full rounded-xl bg-indigo-600 py-3.5 text-base font-semibold text-white hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:bg-slate-300"
        >
          Finalizar venda
        </button>
      </div>
    </aside>
  );
}
