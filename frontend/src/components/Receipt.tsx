import { PAYMENT_LABELS, type Sale } from '../types';
import { formatCurrency } from '../utils/currency';

function Row({ label, value }: { label: string; value: string }) {
  return (
    <div className="flex justify-between gap-4">
      <dt className="text-slate-500">{label}</dt>
      <dd className="tabular-nums">{value}</dd>
    </div>
  );
}

// Comprovante em formato de cupom. Mostra SOMENTE o que o backend gravou (snapshots),
// e a classe "print-area" é o que aparece ao imprimir.
export default function Receipt({ sale }: { sale: Sale }) {
  const date = new Date(sale.created_at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });

  return (
    <article
      aria-label={`Comprovante da venda ${sale.id}`}
      className="print-area mx-auto w-full max-w-sm rounded-xl border border-slate-200 bg-white px-6 py-7 font-mono text-sm text-slate-800 shadow-sm"
    >
      <header className="text-center">
        <h2 className="text-base font-bold">PDV Frente de Caixa</h2>
        <p className="text-slate-500">Comprovante de venda</p>
      </header>

      <div className="my-4 border-t border-dashed border-slate-300" />
      <dl className="space-y-1">
        <Row label="Venda nº" value={String(sale.id)} />
        <Row label="Data" value={date} />
      </dl>

      <div className="my-4 border-t border-dashed border-slate-300" />
      <ul className="space-y-3">
        {sale.items.map((item) => (
          <li key={item.product_id}>
            <p>{item.product_name}</p>
            <div className="flex justify-between gap-4 text-slate-600">
              <span className="tabular-nums">
                {item.quantity} x {formatCurrency(item.unit_price)}
              </span>
              <span className="tabular-nums">{formatCurrency(item.subtotal)}</span>
            </div>
          </li>
        ))}
      </ul>

      <div className="my-4 border-t border-dashed border-slate-300" />
      <dl className="space-y-1">
        <div className="flex justify-between gap-4 text-base font-bold">
          <dt>Total</dt>
          <dd className="tabular-nums">{formatCurrency(sale.total)}</dd>
        </div>
        <Row label="Pagamento" value={PAYMENT_LABELS[sale.payment_method]} />
        {sale.payment_method === 'cash' && (
          <>
            <Row label="Valor recebido" value={formatCurrency(sale.amount_received ?? 0)} />
            <Row label="Troco" value={formatCurrency(sale.change_amount)} />
          </>
        )}
      </dl>

      <p className="mt-6 text-center text-slate-500">Obrigado pela preferência!</p>
    </article>
  );
}
