import { useState } from 'react';
import Header from './components/Header';
import Sidebar, { type Page } from './components/Sidebar';
import LookupPage from './pages/LookupPage';
import PosPage from './pages/PosPage';

const TITLES: Record<Page, string> = { pos: 'Frente de caixa', lookup: 'Consultar venda' };

export default function App() {
  const [page, setPage] = useState<Page>('pos');

  return (
    <div className="min-h-screen bg-slate-100 pl-16 text-slate-900 lg:pl-56">
      <Sidebar current={page} onChange={setPage} />
      <Header title={TITLES[page]} />
      <main>
        {/* As duas telas ficam montadas (só escondidas): trocar de tela não perde o carrinho */}
        <div hidden={page !== 'pos'}>
          <PosPage />
        </div>
        <div hidden={page !== 'lookup'}>
          <LookupPage />
        </div>
      </main>
    </div>
  );
}
