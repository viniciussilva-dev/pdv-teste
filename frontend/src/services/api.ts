// === CLIENTE HTTP ÚNICO ===
// Toda chamada à API passa por aqui: URL base, JSON e tratamento padronizado de erros.

const MSG_OFFLINE = 'Não foi possível conectar ao servidor. Verifique se o backend está em execução.';
const MSG_SERVER = 'O servidor não respondeu corretamente. Verifique se o backend está em execução e tente novamente.';

// Erro da API. "errors" traz as mensagens de validação por campo (HTTP 422 do Laravel).
export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(message: string, status: number, errors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

export function isAbortError(error: unknown): boolean {
  return error instanceof DOMException && error.name === 'AbortError';
}

// Devolve as mensagens para exibir ao operador (todas as de validação, ou a geral)
export function errorMessages(error: unknown): string[] {
  if (error instanceof ApiError) {
    const fieldMessages = Object.values(error.errors).flat();
    return fieldMessages.length > 0 ? [...new Set(fieldMessages)] : [error.message];
  }
  return ['Ocorreu um erro inesperado.'];
}

// A API responde { data: ... }; esta função devolve só o conteúdo de "data"
export async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  let response: Response;
  try {
    response = await fetch(`/api${path}`, {
      ...init,
      headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...init.headers },
    });
  } catch (error) {
    if (isAbortError(error)) throw error; // requisição cancelada de propósito: não é falha de rede
    throw new ApiError(MSG_OFFLINE, 0);
  }

  if (!response.ok) {
    // 5xx também cobre o proxy do Vite quando o Laravel está desligado
    if (response.status >= 500) throw new ApiError(MSG_SERVER, response.status);
    const body = await response.json().catch(() => null);
    throw new ApiError(body?.message ?? 'Não foi possível concluir a operação.', response.status, body?.errors ?? {});
  }

  const body = await response.json();
  return body.data as T;
}
