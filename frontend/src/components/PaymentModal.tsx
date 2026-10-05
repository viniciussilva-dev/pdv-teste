import { Banknote, CreditCard, Loader2, TriangleAlert, Wallet, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { errorMessages } from '../services/api';
import { createSale } from '../services/saleService';
import { PAYMENT_LABELS, type CartItem, type PaymentMethod, type Sale } from '../types';
import { formatCurrency } from '../utils/currency';

// Mesmo teto do backend para o valor recebido (R$ 1.000.000,00, em centavos)
const MAX_RECEIVED_CENTS = 100_000_000;

const METHODS: { id: PaymentMethod; icon: typeof Banknote }[] = [
  { id: 'cash', icon: Banknote },
  { id: 'credit', icon: CreditCard },
  { id: 'debit', icon: Wallet },
];

interface PaymentModalProps {
  items: CartItem[];
  total: number; // exibição; o total oficial é o que o backend devolve no comprovante
  onClose: () => void;
  onSuccess: (sale: Sale) => void;
}

// Escolha da forma de pagamento, valor recebido e troco (dinheiro) e confirmação da venda
export default function PaymentModal({ items, total, onClose, onSuccess }: PaymentModalProps) {
  const [method, setMethod] = useState<PaymentMethod>('cash');
  // O valor recebido é digitado "estilo maquininha": só dígitos, interpretados como centavos
  // (digitar 5000 mostra R$ 50,00). Assim não existe ambiguidade entre vírgula e ponto.
  const [digits, setDigits] = useState('');
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState<string[]>([]);

  const isCash = method === 'cash';
  const received = Number(digits || 0);
  const change = received - total;
  const insufficient = isCash && received < total;
  const canConfirm = !submitting && (!isCash || (digits !== '' && !insufficient));

  // Esc fecha o modal (exceto enquanto a venda está sendo registrada)
  useEffect(() => {
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape' && !submitting) onClose();
    }
    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [submitting, onClose]);

  function handleReceivedChange(value: string) {
    const onlyDigits = value.replace(/\D/g, '').replace(/^0+/, '');
    setDigits(onlyDigits && Number(onlyDigits) > MAX_RECEIVED_CENTS ? String(MAX_RECEIVED_CENTS) : onlyDigits);
  }

  async function handleConfirm() {
    setSubmitting(true); // desabilita o botão: evita venda duplicada por duplo clique
    setErrors([]);
    try {
      // Envia SÓ ids, quantidades, forma de pagamento e valor recebido. Nunca preços ou totais.
      const sale = await createSale({
        items: items.map((item) => ({ product_id: item.product.id, quantity: item.quantity })),
        payment_method: method,
        ...(isCash ? { amount_received: received } : {}),
      });
      onSuccess(sale);
    } catch (error) {
      setErrors(errorMessages(error));
      setSubmitting(false);
    }
  }

  return (
    <div className="fixed inset-0 z-30 flex items-center justify-center overflow-y-auto bg-slate-900/50 p-4">
      <div role="dialog" aria-modal="true" aria-labelledby="payment-title" className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div className="flex items-start justify-between">
          <h2 id="payment-title" className="text-xl font-semibold">
            Finalizar venda
          </h2>
          <button
            onClick={onClose}
            disabled={submitting}
            aria-label="Fechar"
            className="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-indigo-600 disabled:opacity-40"
          >
            <X className="size-5" aria-hidden="true" />
          </button>
        </div>

        <div className="mt-4 flex items-baseline justify-between rounded-xl bg-slate-50 px-4 py-3">
          <span className="text-slate-600">Total a pagar</span>
          <span className="text-2xl font-semibold tabular-nums">{formatCurrency(total)}</span>
        </div>

        <fieldset className="mt-5" disabled={submitting}>
          <legend className="mb-2 text-sm font-medium text-slate-700">Forma de pagamento</legend>
          <div className="grid grid-cols-3 gap-2">
            {METHODS.map(({ id, icon: Icon }) => (
              <button
                key={id}
                type="button"
                aria-pressed={method === id}
                onClick={() => setMethod(id)}
                className={`flex flex-col items-center gap-1.5 rounded-xl border px-2 py-3 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 ${
                  method === id
                    ? 'border-indigo-600 bg-indigo-50 text-indigo-700'
                    : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                }`}
              >
                <Icon className="size-5" aria-hidden="true" />
                {PAYMENT_LABELS[id]}
              </button>
            ))}
          </div>
        </fieldset>

        {isCash && (
          <div className="mt-5">
            <div className="flex items-end justify-between gap-3">
              <label htmlFor="received" className="text-sm font-medium text-slate-700">
                Valor recebido
              </label>
              <button
                type="button"
                onClick={() => setDigits(String(total))}
                disabled={submitting}
                className="text-sm font-medium text-indigo-600 hover:underline focus-visible:outline-2 focus-visible:outline-indigo-600"
              >
                Valor exato
              </button>
            </div>
            <input
              id="received"
              inputMode="numeric"
              autoComplete="off"
              autoFocus
              disabled={submitting}
              value={digits ? formatCurrency(received) : ''}
              onChange={(event) => handleReceivedChange(event.target.value)}
              placeholder="R$ 0,00"
              aria-invalid={insufficient && digits !== ''}
              className={`mt-1.5 w-full rounded-xl border px-4 py-3 text-xl tabular-nums focus:outline-none focus:ring-4 ${
                insufficient && digits !== ''
                  ? 'border-red-400 focus:ring-red-100'
                  : 'border-slate-300 focus:border-indigo-500 focus:ring-indigo-100'
              }`}
            />
            <p className="mt-2 min-h-7 text-sm" aria-live="polite">
              {digits === '' && <span className="text-slate-500">Digite o valor entregue pelo cliente.</span>}
              {digits !== '' && insufficient && (
                <span className="font-medium text-red-600">Valor insuficiente: faltam {formatCurrency(total - received)}.</span>
              )}
              {digits !== '' && !insufficient && (
                <span className="text-emerald-700">
                  Troco: <strong className="text-xl tabular-nums">{formatCurrency(change)}</strong>
                </span>
              )}
            </p>
          </div>
        )}

        {errors.length > 0 && (
          <div role="alert" className="mt-4 flex gap-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            <TriangleAlert className="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <div>
              <p className="font-medium">Não foi possível finalizar a venda.</p>
              <ul className="mt-1 list-disc pl-4">
                {errors.map((message) => (
                  <li key={message}>{message}</li>
                ))}
              </ul>
            </div>
          </div>
        )}

        <button
          onClick={handleConfirm}
          disabled={!canConfirm}
          className="mt-5 flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 py-3.5 text-base font-semibold text-white hover:bg-indigo-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed disabled:bg-slate-300"
        >
          {submitting && <Loader2 className="size-5 animate-spin" aria-hidden="true" />}
          {submitting ? 'Registrando venda...' : 'Confirmar pagamento'}
        </button>
      </div>
    </div>
  );
}
