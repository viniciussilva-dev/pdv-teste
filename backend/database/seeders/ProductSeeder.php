<?php

namespace Database\Seeders;

use App\Models\Product; // model usado para gravar na tabela products
use Illuminate\Database\Seeder; // classe base de todo seeder

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // === DADOS DE EXEMPLO ===
        // Preços em CENTAVOS. Sem 'available' = disponível (ver o "+ default" abaixo).
        $products = [
            ['name' => 'Café Torrado e Moído 500g', 'code' => '7891000100103', 'price' => 1890],
            ['name' => 'Açúcar Refinado 1kg', 'code' => '7891000100110', 'price' => 459],
            ['name' => 'Arroz Branco Tipo 1 5kg', 'code' => '7891000100127', 'price' => 2790],
            ['name' => 'Feijão Carioca 1kg', 'code' => '7891000100134', 'price' => 849],
            ['name' => 'Leite Integral 1L', 'code' => '7891000100141', 'price' => 579],
            ['name' => 'Pão de Forma Tradicional 500g', 'code' => '7891000100158', 'price' => 899],
            ['name' => 'Óleo de Soja 900ml', 'code' => '7891000100165', 'price' => 749],
            ['name' => 'Macarrão Espaguete 500g', 'code' => '7891000100172', 'price' => 429],
            ['name' => 'Refrigerante Cola 2L', 'code' => '7891000100189', 'price' => 999],
            ['name' => 'Água Mineral sem Gás 500ml', 'code' => '7891000100196', 'price' => 249],
            ['name' => 'Suco de Laranja Integral 1L', 'code' => '7891000100202', 'price' => 1290],
            ['name' => 'Biscoito Recheado Chocolate 130g', 'code' => '7891000100219', 'price' => 349],
            ['name' => 'Chocolate ao Leite 90g', 'code' => '7891000100226', 'price' => 599],
            ['name' => 'Sabão em Pó 1kg', 'code' => '7891000100233', 'price' => 1590],
            ['name' => 'Detergente Líquido 500ml', 'code' => '7891000100240', 'price' => 289],
            ['name' => 'Papel Higiênico 12 rolos', 'code' => '7891000100257', 'price' => 2490],
            // Indisponíveis de propósito, para testar o bloqueio na tela e na API
            ['name' => 'Queijo Mussarela Fatiado 200g', 'code' => '7891000100264', 'price' => 1690, 'available' => false],
            ['name' => 'Iogurte Natural 170g', 'code' => '7891000100271', 'price' => 259, 'available' => false],
        ];

        // === GRAVAÇÃO ===
        foreach ($products as $data) {
            // updateOrCreate: se já existe um produto com esse código, ATUALIZA; senão, cria.
            // Assim o seeder pode rodar várias vezes sem erro de código duplicado.
            // "+ ['available' => true]" só preenche o padrão quando a chave não foi informada.
            Product::updateOrCreate(['code' => $data['code']], $data + ['available' => true]);
        }
    }
}