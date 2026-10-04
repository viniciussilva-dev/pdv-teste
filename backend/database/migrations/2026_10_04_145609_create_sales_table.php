<?php

use Illuminate\Database\Migrations\Migration; // classe base de toda migration
use Illuminate\Database\Schema\Blueprint; // descreve as colunas da tabela
use Illuminate\Support\Facades\Schema; // cria e remove tabelas no banco

return new class extends Migration
{
    // === CRIAR A TABELA ===
    // Executado por "php artisan migrate"
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id(); // chave primária autoincremento (é o identificador da venda no comprovante)

            // === VALORES (todos em CENTAVOS, como inteiros) ===
            // Total calculado pelo BACKEND. unsigned = nunca negativo
            $table->unsignedInteger('total');

            // === PAGAMENTO ===
            // Guarda 'cash', 'credit' ou 'debit'. É string (e não o tipo enum do banco)
            // porque o tipo enum nativo complica migrações futuras; quem garante os
            // valores válidos é o Enum PHP PaymentMethod + a validação da requisição.
            $table->string('payment_method');

            // Valor entregue pelo cliente. Só existe em pagamento em dinheiro,
            // por isso é nullable: em crédito/débito fica NULL (não se aplica)
            $table->unsignedInteger('amount_received')->nullable();

            // Troco devolvido ao cliente. Em cartão é sempre 0
            $table->unsignedInteger('change_amount')->default(0);

            $table->timestamps(); // created_at (data/hora da venda) e updated_at
        });
    }

    // === DESFAZER ===
    // Executado por "php artisan migrate:rollback"
    public function down(): void
    {
        Schema::dropIfExists('sales'); 
    }
};