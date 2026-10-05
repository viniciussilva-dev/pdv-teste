import { User } from 'lucide-react';

// Cabeçalho: sistema, tela atual, operador e data. Sem login no escopo: operador fixo.
export default function Header({ title }: { title: string }) {
  const today = new Date().toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

  return (
    <header className="sticky top-0 z-10 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 lg:px-6">
      <div>
        <p className="text-xs text-slate-500">PDV Frente de Caixa</p>
        <h1 className="text-lg font-semibold leading-tight">{title}</h1>
      </div>
      <div className="text-right text-sm">
        <p className="flex items-center justify-end gap-1.5 font-medium text-slate-700">
          <User className="size-4 text-slate-400" aria-hidden="true" />
          Operador: Caixa 01
        </p>
        <p className="text-slate-500 first-letter:uppercase">{today}</p>
      </div>
    </header>
  );
}
