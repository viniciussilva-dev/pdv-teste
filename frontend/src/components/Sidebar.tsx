import { Receipt, ShoppingCart, Store } from 'lucide-react';

export type Page = 'pos' | 'lookup';

const NAV_ITEMS = [
  { id: 'pos', label: 'Frente de caixa', icon: ShoppingCart },
  { id: 'lookup', label: 'Consultar venda', icon: Receipt },
] as const;

// Navegação lateral escura; em telas pequenas mostra só os ícones
export default function Sidebar({ current, onChange }: { current: Page; onChange: (page: Page) => void }) {
  return (
    <aside className="fixed inset-y-0 left-0 z-20 flex w-16 flex-col bg-slate-900 lg:w-56">
      <div className="flex h-16 items-center justify-center gap-3 text-white lg:justify-start lg:px-5">
        <Store className="size-6 shrink-0 text-indigo-400" aria-hidden="true" />
        <span className="hidden text-lg font-semibold lg:inline">PDV</span>
      </div>
      <nav className="mt-2 flex flex-col gap-1 px-2" aria-label="Navegação principal">
        {NAV_ITEMS.map(({ id, label, icon: Icon }) => {
          const active = current === id;
          return (
            <button
              key={id}
              onClick={() => onChange(id)}
              title={label}
              aria-current={active ? 'page' : undefined}
              className={`flex items-center justify-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors focus-visible:outline-2 focus-visible:outline-indigo-400 lg:justify-start ${
                active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <Icon className="size-5 shrink-0" aria-hidden="true" />
              <span className="hidden lg:inline">{label}</span>
              <span className="sr-only lg:hidden">{label}</span>
            </button>
          );
        })}
      </nav>
    </aside>
  );
}
