import { CircleAlert, Loader2 } from 'lucide-react';
import type { ReactNode } from 'react';

// Estado de carregamento
export function Spinner({ label = 'Carregando...' }: { label?: string }) {
  return (
    <div role="status" className="flex items-center justify-center gap-3 py-16 text-slate-500">
      <Loader2 className="size-5 animate-spin" aria-hidden="true" />
      <span>{label}</span>
    </div>
  );
}

// Estado de erro, com ação para tentar de novo
export function ErrorMessage({ message, onRetry }: { message: string; onRetry?: () => void }) {
  return (
    <div role="alert" className="flex flex-col items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-6 py-10 text-center">
      <CircleAlert className="size-8 text-red-500" aria-hidden="true" />
      <p className="max-w-md text-red-800">{message}</p>
      {onRetry && (
        <button
          onClick={onRetry}
          className="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600"
        >
          Tentar novamente
        </button>
      )}
    </div>
  );
}

// Estado vazio: explica o que fazer em seguida
export function EmptyState({ icon, title, text }: { icon: ReactNode; title: string; text?: string }) {
  return (
    <div className="flex flex-col items-center gap-2 px-6 py-12 text-center text-slate-500">
      <div className="text-slate-300" aria-hidden="true">
        {icon}
      </div>
      <p className="font-medium text-slate-700">{title}</p>
      {text && <p className="max-w-xs text-sm">{text}</p>}
    </div>
  );
}
