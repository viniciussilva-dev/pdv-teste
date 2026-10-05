import { Plus } from 'lucide-react';
import type { Product } from '../types';
import { formatCurrency } from '../utils/currency';

// Lista de produtos. Produto indisponível aparece, mas não pode ser adicionado.
export default function ProductGrid({ products, onAdd }: { products: Product[]; onAdd: (product: Product) => void }) {
  // Colunas automáticas: cada card tem no mínimo 16rem, então o botão nunca vaza do card.
  // Em área estreita vira 1 coluna; em tela larga, 3 ou 4.
  return (
    <ul className="grid grid-cols-[repeat(auto-fill,minmax(16rem,1fr))] gap-3">
      {products.map((product) => (
        <li
          key={product.id}
          className={`flex flex-col justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm ${
            product.available ? '' : 'opacity-60'
          }`}
        >
          <div>
            <h3 className="font-medium leading-snug">{product.name}</h3>
            <p className="mt-0.5 text-sm text-slate-500">Código {product.code}</p>
          </div>
          <div className="flex flex-wrap items-end justify-between gap-x-3 gap-y-2">
            <div>
              <p className="text-lg font-semibold tabular-nums">{formatCurrency(product.price)}</p>
              <p className={`text-xs ${product.available ? 'text-emerald-700' : 'text-slate-500'}`}>
                {product.available ? 'Disponível' : 'Indisponível'}
              </p>
            </div>
            <button
              onClick={() => onAdd(product)}
              disabled={!product.available}
              aria-label={`Adicionar ${product.name} ao carrinho`}
              className="flex shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
            >
              <Plus className="size-4" aria-hidden="true" />
              Adicionar
            </button>
          </div>
        </li>
      ))}
    </ul>
  );
}
