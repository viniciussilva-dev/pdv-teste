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
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // chave primária autoincremento

            // === IDENTIFICAÇÃO ===
            $table->string('name');
            // unique() cria um índice único: é o BANCO que impede dois produtos
            // com o mesmo código, mesmo que a aplicação falhe em validar
            $table->string('code')->unique();
            $table->text('description')->nullable(); // opcional, por isso nullable

            // === PREÇO E DISPONIBILIDADE ===
            // Preço em CENTAVOS (R$ 10,50 = 1050). unsigned = nunca negativo
            $table->unsignedInteger('price');
            // default(true): produto novo nasce disponível, a menos que digam o contrário
            $table->boolean('available')->default(true);

            $table->timestamps(); // created_at e updated_at
        });
    }

    // === DESFAZER ===
    // Executado por "php artisan migrate:rollback" ou "migrate:fresh"
    public function down(): void
    {
        Schema::dropIfExists('products'); // remove a tabela se ela existir
    }
};