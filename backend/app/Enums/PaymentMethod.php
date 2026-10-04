<?php

namespace App\Enums;

// === FORMAS DE PAGAMENTO ===
// Enum "backed" (com valor string): cada caso guarda o texto que é gravado no banco
// e trafega na API. Com isso, 'cash' deixa de ser uma string solta espalhada pelo
// código: um erro de digitação (como 'chash') não passa despercebido.
// Os rótulos em português ("Dinheiro", "Crédito", "Débito") ficam no frontend.
enum PaymentMethod: string
{
    case Cash = 'cash';     // dinheiro (exige valor recebido e calcula troco)
    case Credit = 'credit'; // cartão de crédito
    case Debit = 'debit';   // cartão de débito
}