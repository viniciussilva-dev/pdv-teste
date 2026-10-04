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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id(); // chave primária autoincremento

            // === REFERÊNCIAS (chaves estrangeiras) ===
            // constrained() cria a FK apontando para a tabela e a coluna id
            // (deduzidas do nome: sale_id -> sales.id, product_id -> products.id).
            // restrictOnDelete(): o banco IMPEDE apagar uma venda ou um produto que
            // tenha itens ligados. É o oposto de cascade, que apagaria os itens junto.
            // Assim o histórico de vendas não some por acidente.
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();

            // === SNAPSHOT HISTÓRICO ===
            // Cópias do nome e do preço do produto NO MOMENTO DA VENDA.
            // O comprovante lê estes campos, nunca a tabela products,
            // então alterar o produto depois não altera vendas já feitas.
            $table->string('product_name');
            $table->unsignedInteger('unit_price'); // centavos

            // === QUANTIDADE E SUBTOTAL ===
            $table->unsignedInteger('quantity');
            // unit_price x quantity, calculado pelo BACKEND. Guardamos o valor
            // efetivamente cobrado para o comprovante ler direto da tabela.
            $table->unsignedInteger('subtotal'); // centavos

            $table->timestamps();

            // === UM PRODUTO POR VENDA ===
            // Índice único composto: o mesmo produto não pode aparecer duas vezes
            // na mesma venda. Garantia do BANCO para a regra que a validação também aplica.
            $table->unique(['sale_id', 'product_id']);
        });
    }

    // === DESFAZER ===
    // Executado por "php artisan migrate:rollback"
    public function down(): void
    {
        Schema::dropIfExists('sale_items'); // remove a tabela se ela existir
    }
};