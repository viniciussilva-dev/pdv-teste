import { Printer, Receipt as ReceiptIcon, Search } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import Receipt from '../components/Receipt';
import { ErrorMessage, Spinner } from '../components/ui';
import { ApiError, errorMessages } from '../services/api';
import { getSale } from '../services/saleService';
import type { Sale } from '../types';

// Consulta de uma venda já finalizada pelo número (identificador)
export default function LookupPage() {
  const [input, setInput] = useState('');
  const [sale, setSale] = useState<Sale | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    const id = Number(input);
    setSale(null);

    if (!Number.isSafeInteger(id) || id < 1) {
      setError('Informe um número de venda válido.');
      return;
    }

    setLoading(true);
    setError(null);
    try {
      setSale(await getSale(id));
    } catch (err) {
      setError(err instanceof ApiError && err.status === 404 ? `Não encontramos a venda nº ${id}.` : errorMessages(err)[0]);
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto max-w-lg space-y-6 p-4 lg:p-6">
      <form onSubmit={handleSubmit} className="flex gap-3 print:hidden">
        <label className="relative block flex-1">
          <span className="sr-only">Número da venda</span>
          <Search className="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-400" aria-hidden="true" />
          <input
            inputMode="numeric"
            value={input}
            // Só dígitos: o número da venda é inteiro. Ao digitar, a mensagem de erro anterior some.
            onChange={(event) => {
              setInput(event.target.value.replace(/\D/g, ''));
              setError(null);
            }}
            placeholder="Número da venda"
            className="w-full rounded-xl border border-slate-300 bg-white py-3.5 pl-12 pr-4 text-base shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100"
          />
        </label>
        <button
          type="submit"
          disabled={loading || input === ''}
          className="rounded-xl bg-indigo-600 px-5 font-semibold text-white hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:bg-slate-300"
        >
          Consultar
        </button>
      </form>

      {loading && <Spinner label="Consultando venda..." />}
      {error && <ErrorMessage message={error} />}

      {!loading && !error && !sale && (
        <div className="flex flex-col items-center gap-2 px-6 py-12 text-center text-slate-500 print:hidden">
          <ReceiptIcon className="size-10 text-slate-300" aria-hidden="true" />
          <p className="font-medium text-slate-700">Consulte um comprovante</p>
          <p className="max-w-xs text-sm">Digite o número da venda, que aparece no comprovante, para ver os detalhes.</p>
        </div>
      )}

      {sale && (
        <>
          <Receipt sale={sale} />
          <div className="flex justify-center print:hidden">
            <button
              onClick={() => window.print()}
              className="flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-3 font-medium hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              <Printer className="size-5" aria-hidden="true" />
              Imprimir comprovante
            </button>
          </div>
        </>
      )}
    </div>
  );
}
