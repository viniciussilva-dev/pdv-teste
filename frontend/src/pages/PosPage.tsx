import { CircleCheck, PackageSearch, Plus, Printer, Search } from 'lucide-react';
import { useState } from 'react';
import CartPanel from '../components/CartPanel';
import PaymentModal from '../components/PaymentModal';
import ProductGrid from '../components/ProductGrid';
import Receipt from '../components/Receipt';
import { EmptyState, ErrorMessage, Spinner } from '../components/ui';
import { useCart } from '../hooks/useCart';
import { useProducts } from '../hooks/useProducts';
import type { Sale } from '../types';

// Tela principal do caixa: busca e produtos à esquerda, carrinho à direita
export default function PosPage() {
  const [search, setSearch] = useState('');
  const { products, loading, error, retry } = useProducts(search);
  const cart = useCart();
  const [paying, setPaying] = useState(false);
  const [completedSale, setCompletedSale] = useState<Sale | null>(null);

  // Venda registrada com sucesso: limpa o carrinho e mostra o comprovante
  function handleSaleCompleted(sale: Sale) {
    cart.clear();
    setPaying(false);
    setCompletedSale(sale);
  }

  // === VENDA CONCLUÍDA: comprovante + ações ===
  if (completedSale) {
    return (
      <div className="mx-auto max-w-lg space-y-6 p-4 lg:p-6">
        <div role="status" className="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900 print:hidden">
          <CircleCheck className="size-6 shrink-0 text-emerald-600" aria-hidden="true" />
          <p className="font-medium">Venda nº {completedSale.id} concluída com sucesso.</p>
        </div>

        <Receipt sale={completedSale} />

        <div className="flex flex-wrap justify-center gap-3 print:hidden">
          <button
            onClick={() => window.print()}
            className="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 font-medium hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            <Printer className="size-5" aria-hidden="true" />
            Imprimir comprovante
          </button>
          <button
            onClick={() => setCompletedSale(null)}
            className="flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            <Plus className="size-5" aria-hidden="true" />
            Nova venda
          </button>
        </div>
      </div>
    );
  }

  // === ÁREA DE PRODUTOS: carregando, erro, vazio ou lista ===
  let productArea;
  if (error) {
    productArea = <ErrorMessage message={error} onRetry={retry} />;
  } else if (loading && products.length === 0) {
    productArea = <Spinner label="Buscando produtos..." />;
  } else if (products.length === 0) {
    productArea = (
      <EmptyState
        icon={<PackageSearch className="size-10" />}
        title={search ? `Nenhum produto encontrado para "${search}"` : 'Nenhum produto cadastrado'}
        text={search ? 'Confira o nome ou o código e tente novamente.' : undefined}
      />
    );
  } else {
    productArea = (
      <div className={loading ? 'opacity-60 transition-opacity' : ''}>
        <ProductGrid products={products} onAdd={cart.add} />
      </div>
    );
  }

  return (
    <div className="grid gap-6 p-4 lg:grid-cols-[minmax(0,1fr)_24rem] lg:p-6">
      <section aria-label="Produtos" className="space-y-4">
        <label className="relative block">
          <span className="sr-only">Buscar produto por nome ou código</span>
          <Search className="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            autoFocus
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Buscar por nome ou código do produto"
            className="w-full rounded-xl border border-slate-300 bg-white py-3.5 pl-12 pr-4 text-base shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100"
          />
        </label>
        {productArea}
      </section>

      <CartPanel
        items={cart.items}
        total={cart.total}
        onIncrement={cart.increment}
        onDecrement={cart.decrement}
        onRemove={cart.remove}
        onCheckout={() => setPaying(true)}
      />

      {paying && (
        <PaymentModal items={cart.items} total={cart.total} onClose={() => setPaying(false)} onSuccess={handleSaleCompleted} />
      )}
    </div>
  );
}
