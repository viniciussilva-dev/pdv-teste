const formatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

// Converte centavos (inteiro) em texto: 1890 -> "R$ 18,90".
// A divisão por 100 acontece SÓ para exibir; todas as contas continuam em centavos.
export function formatCurrency(cents: number): string {
  return formatter.format(cents / 100);
}
