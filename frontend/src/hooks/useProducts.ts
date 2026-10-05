import { useEffect, useState } from 'react';
import { errorMessages, isAbortError } from '../services/api';
import { getProducts } from '../services/productService';
import type { Product } from '../types';

// Busca produtos na API sempre que o termo muda (com espera de 300 ms enquanto o operador digita).
export function useProducts(search: string) {
  const [products, setProducts] = useState<Product[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [reloadKey, setReloadKey] = useState(0); // incrementar força nova busca ("Tentar novamente")

  useEffect(() => {
    const controller = new AbortController();
    setLoading(true);
    setError(null);

    // Debounce: só pesquisa 300 ms depois da última digitação (a carga inicial não espera)
    const timer = setTimeout(
      () => {
        getProducts(search.trim(), controller.signal)
          .then(setProducts)
          .catch((err) => {
            if (!isAbortError(err)) setError(errorMessages(err)[0]);
          })
          .finally(() => {
            if (!controller.signal.aborted) setLoading(false);
          });
      },
      search ? 300 : 0,
    );

    // Ao digitar de novo (ou desmontar), cancela a busca anterior: respostas antigas não sobrescrevem as novas
    return () => {
      clearTimeout(timer);
      controller.abort();
    };
  }, [search, reloadKey]);

  return { products, loading, error, retry: () => setReloadKey((key) => key + 1) };
}
