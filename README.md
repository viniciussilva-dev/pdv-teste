# PDV: Frente de Caixa

Frente de caixa (PDV) com **backend Laravel 12** e **frontend React + TypeScript**. O operador busca produtos, monta o carrinho, finaliza a venda em dinheiro, crédito ou débito, calculando o troco quando o pagamento é em dinheiro e consulta o comprovante de qualquer venda já finalizada.

A regra central do projeto: **o backend nunca confia em valores vindos do cliente.** O frontend envia apenas ids de produtos, quantidades e a forma de pagamento. Preços, subtotais, total e troco são sempre recalculados no servidor com os dados do banco.

## Funcionalidades

- **Produtos:** listagem e busca por nome ou código. Produtos indisponíveis aparecem, mas não podem ser adicionados ao carrinho nem vendidos.
- **Carrinho:** adicionar, aumentar/diminuir (1 a 999) e remover itens, com subtotais e total atualizados na hora.
- **Venda:** dinheiro (informa o valor recebido, mostra o troco e bloqueia valor insuficiente), crédito e débito.
- **Comprovante:** layout de cupom, com ações **Imprimir comprovante** e **Nova venda**.
- **Consulta:** busca de uma venda finalizada pelo número, com o mesmo comprovante.
- **Estados de interface:** carregando, erro de comunicação (com "Tentar novamente"), nenhum produto encontrado, carrinho vazio, venda concluída e falha ao finalizar.

## Tecnologias

| Camada | Tecnologias |
|---|---|
| Backend | PHP 8.2+, Laravel 12, Eloquent, Form Requests, API Resources, SQLite |
| Frontend | React 19, TypeScript, Vite 8, Tailwind CSS 4, lucide-react |
| Testes | PHPUnit (via `php artisan test`) |

## Requisitos

- **PHP 8.2 ou superior**, com as extensões `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `curl` e `zip`
- **Composer 2**
- **Node.js 20.19+ ou 22.12+** (exigência do Vite 8) e npm
- Git

## Como executar

O projeto tem duas partes independentes, em `backend/` e `frontend/`. Rode as duas, cada uma em um terminal.

### 1. Backend (Laravel)

```powershell
cd backend
composer install
Copy-Item .env.example .env          # Linux/macOS: cp .env.example .env
php artisan key:generate
New-Item -ItemType File database\database.sqlite   # Linux/macOS: touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

A API fica em `http://127.0.0.1:8000`.

**Sobre o `.env`:** o `.env.example` do Laravel 12 já vem com `DB_CONNECTION=sqlite`, e nenhuma outra variável precisa ser alterada. O arquivo do banco é `backend/database/database.sqlite`, que você criou no passo acima. O `php artisan migrate --seed` cria as tabelas e carrega **18 produtos de exemplo** (2 deles indisponíveis, para testar o bloqueio).

> O projeto foi desenvolvido e testado somente com SQLite. As migrations usam apenas recursos portáveis, então a troca para MySQL deve ser feita pelas variáveis `DB_*` do `.env`, mas isso não foi testado.

### 2. Frontend (React)

```powershell
cd frontend
npm ci
npm run dev
```

Abra **http://localhost:5173**.

O Vite repassa as chamadas `/api/...` para `http://127.0.0.1:8000` (proxy configurado em `frontend/vite.config.ts`). Por isso o backend precisa estar rodando, e não há problema de CORS. Se o Laravel rodar em outra porta, ajuste o `target` do proxy.

Para gerar o build de produção (que também verifica os tipos do TypeScript): `npm run build`.

## Testes

```powershell
cd backend
php artisan test
```

São **30 testes** (28 do projeto + 2 de exemplo do Laravel). Eles rodam em um **SQLite em memória** (definido em `backend/phpunit.xml`), então nunca tocam no banco de desenvolvimento.

| Arquivo | O que protege |
|---|---|
| `tests/Feature/SaleServiceTest.php` | Total e subtotais com preço do banco; preço enviado pelo cliente ignorado; produto indisponível ou inexistente recusado sem registro parcial; venda sem itens; dinheiro insuficiente, ausente, exato e com troco; cartão com `amount_received = null` e troco 0; preço histórico preservado; rollback da transação |
| `tests/Feature/SaleApiTest.php` | Criação de venda via HTTP (201) e comprovante; consulta e 404; 10 formatos de payload inválido (422); regras de negócio em 422; cartão pela API; venda finalizada sem rotas de edição ou exclusão (405) |
| `tests/Feature/ProductApiTest.php` | Listagem com indisponíveis; busca por nome e código; escape do curinga `%`; detalhe e 404 |

O frontend não tem testes automatizados (veja "Limitações").

## API

Base: `http://127.0.0.1:8000/api`. Todas as respostas de sucesso vêm no formato `{ "data": ... }`. **Valores monetários são inteiros em centavos** (R$ 18,90 = `1890`).

| Método | Rota | Descrição |
|---|---|---|
| GET | `/products?search=` | Lista produtos (inclusive indisponíveis). `search` filtra por nome ou código |
| GET | `/products/{id}` | Detalhe de um produto |
| POST | `/sales` | Registra uma venda finalizada |
| GET | `/sales/{id}` | Consulta uma venda e seus itens |

Não existem rotas para editar ou excluir vendas.

### Exemplo: listar produtos

```
GET /api/products?search=caf
```
```json
{
  "data": [
    {
      "id": 1,
      "name": "Café Torrado e Moído 500g",
      "code": "7891000100103",
      "description": null,
      "price": 1890,
      "available": true
    }
  ]
}
```

### Exemplo: registrar uma venda em dinheiro

O cliente envia **apenas** ids, quantidades, forma de pagamento e valor recebido:

```powershell
$body = @{
  items = @(@{ product_id = 1; quantity = 2 }, @{ product_id = 2; quantity = 1 })
  payment_method = 'cash'
  amount_received = 5000
} | ConvertTo-Json -Depth 5

Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/sales -ContentType 'application/json' -Body $body
```

Equivalente em `curl`:

```bash
curl -X POST http://127.0.0.1:8000/api/sales \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"items":[{"product_id":1,"quantity":2},{"product_id":2,"quantity":1}],"payment_method":"cash","amount_received":5000}'
```

Resposta `201 Created` (valores ilustrativos, calculados pelo servidor: 2 × 1890 + 1 × 459 = 4239):

```json
{
  "data": {
    "id": 1,
    "total": 4239,
    "payment_method": "cash",
    "amount_received": 5000,
    "change_amount": 761,
    "created_at": "2026-10-04T21:34:52+00:00",
    "items": [
      { "product_id": 1, "product_name": "Café Torrado e Moído 500g", "unit_price": 1890, "quantity": 2, "subtotal": 3780 },
      { "product_id": 2, "product_name": "Açúcar Refinado 1kg", "unit_price": 459, "quantity": 1, "subtotal": 459 }
    ]
  }
}
```

Para crédito ou débito, use `"payment_method": "credit"` ou `"debit"`. O `amount_received` é **ignorado** nesses casos: a venda é gravada com `amount_received = null` e `change_amount = 0`.

### Validações do `POST /sales`

| Campo | Regra |
|---|---|
| `items` | Obrigatório, de 1 a 100 itens |
| `items.*.product_id` | Inteiro, sem repetição na mesma venda |
| `items.*.quantity` | Inteiro de 1 a 999 |
| `payment_method` | `cash`, `credit` ou `debit` |
| `amount_received` | Só para `cash`: obrigatório, inteiro de 0 a 100000000 (R$ 1.000.000,00), e **não pode ser menor que o total** |

Qualquer outro campo enviado (`price`, `subtotal`, `total`...) é descartado e nunca é lido.

### Erros

Erros de validação e de regra de negócio retornam **HTTP 422**, com mensagens em português e a chave do campo afetado. Isso permite destacar a linha certa do carrinho:

```json
{
  "message": "O produto \"Queijo Mussarela Fatiado 200g\" está indisponível.",
  "errors": {
    "items.0.product_id": ["O produto \"Queijo Mussarela Fatiado 200g\" está indisponível."]
  }
}
```

| Status | Quando |
|---|---|
| 422 | Payload inválido, produto inexistente ou indisponível, valor recebido insuficiente |
| 404 | Produto ou venda não encontrados: `{ "message": "Recurso não encontrado." }` |
| 405 | Tentativa de editar ou excluir uma venda (as rotas não existem) |

## Regras de negócio e onde são garantidas

| Regra | Onde |
|---|---|
| Preços, subtotais, total e troco nunca vêm do cliente | `SaleService` recalcula com os preços do banco; `validated()` descarta campos extras |
| Preço histórico preservado | `sale_items.unit_price` e `product_name` (snapshots) |
| Venda tem 1 ou mais itens | `StoreSaleRequest` (`min:1`) e defesa no `SaleService` |
| Quantidade inválida | `StoreSaleRequest` (inteiro de 1 a 999) |
| Produto indisponível ou inexistente | `SaleService`, dentro da transação |
| Dinheiro: recebido ≥ total; troco = diferença | `SaleService` |
| Sem vendas parciais | `DB::transaction` no `SaleService` |
| Venda finalizada não é alterada | Não existem rotas de edição ou exclusão; chaves estrangeiras restritivas |
| Produto não repetido na venda | `distinct` no Form Request e índice único `(sale_id, product_id)` |

O frontend também mostra o troco e bloqueia o botão quando o valor é insuficiente, mas isso é só **conforto de uso**: a regra valendo é a do backend.

## Decisões técnicas

- **Centavos em inteiros** para todo valor monetário, evitando erros de ponto flutuante. A formatação em reais é feita só na exibição.
- **`SaleService`:** controllers só recebem, delegam e respondem. A lógica de venda fica em um único lugar, testável isoladamente.
- **Form Request valida a forma; o service valida o que depende do banco.** Assim nada é validado em dois lugares, e o service ainda se protege contra venda sem itens se for chamado diretamente.
- **Erros com `ValidationException` nativa do Laravel** (HTTP 422 no formato padrão), sem uma exceção própria: o benefício de uma classe nova não compensaria o código extra.
- **Sem campo `status` em `sales`:** toda venda nasce finalizada, em uma única transação. Não há rascunho, cancelamento nem estorno. Se esses fluxos existirem no futuro, o campo é uma migration simples.
- **Chaves estrangeiras restritivas** (`restrictOnDelete`): o banco impede apagar um produto já vendido ou uma venda com itens. Para retirar um produto de circulação, usa-se `available = false`.
- **Enum `PaymentMethod`** com valores internos em inglês (`cash`, `credit`, `debit`); os rótulos em português ficam no frontend.
- **Cartão e `amount_received`:** o campo é ignorado, tanto no Form Request (`exclude_unless`) quanto no service, e gravado como `null`.
- **Listagem de produtos inclui os indisponíveis**, marcados com `available = false`, e sem paginação (o catálogo do teste é pequeno).
- **Valor recebido digitado "estilo maquininha":** só dígitos, lidos como centavos (digitar `5000` mostra R$ 50,00). Elimina a ambiguidade entre vírgula e ponto.
- **Sem React Router:** são duas telas, alternadas por estado, e as duas ficam montadas, então trocar de tela não perde o carrinho.
- **Camada de serviços no frontend** (`src/services`): é a única que faz chamadas HTTP, com tratamento padronizado de erros. Buscas de produtos têm espera de 300 ms e cancelam respostas antigas.
- **Botão de confirmar desabilitado durante o envio**, para evitar venda duplicada por duplo clique.

## Estrutura do projeto

```
backend/
├── app/
│   ├── Enums/PaymentMethod.php
│   ├── Http/
│   │   ├── Controllers/        ProductController, SaleController
│   │   ├── Requests/           StoreSaleRequest
│   │   └── Resources/          ProductResource, SaleResource, SaleItemResource
│   ├── Models/                 Product, Sale, SaleItem
│   └── Services/SaleService.php
├── database/                   migrations, factories, seeders (18 produtos)
├── routes/api.php
└── tests/Feature/              SaleServiceTest, SaleApiTest, ProductApiTest

frontend/src/
├── components/                 Sidebar, Header, ProductGrid, CartPanel, PaymentModal, Receipt, ui
├── hooks/                      useProducts, useCart
├── pages/                      PosPage, LookupPage
├── services/                   api, productService, saleService
├── utils/currency.ts
└── types.ts
```

## Limitações e fora do escopo

- **Sem autenticação:** o operador no cabeçalho é fixo ("Caixa 01").
- **Sem controle de estoque:** existe apenas o campo `available`.
- **Sem cancelamento, estorno ou edição de vendas**, e sem listagem de histórico: a consulta é pelo número da venda.
- **Concorrência:** a disponibilidade é verificada dentro da transação, mas sem bloqueio (`lockForUpdate`). Um produto marcado como indisponível no exato instante de uma venda simultânea não é tratado.
- **SQLite:** a restrição `unsigned` não é imposta pelo banco, então valores negativos e quantidades inválidas são barrados pela validação da aplicação. A busca ignora maiúsculas e minúsculas em ASCII, mas **não ignora acentos** (`cafe` não encontra `Café`).
- **Preço no carrinho:** o carrinho mostra o preço do momento em que o produto foi carregado. Se o preço mudar antes da venda, o comprovante traz o valor oficial do servidor.
- **Ordem dos produtos:** vem do backend (por id); não há ordenação alfabética.
- **Sem testes automatizados do frontend:** as regras críticas estão cobertas no backend.
- **Impressão:** usa o diálogo de impressão do navegador (`window.print()`).

## Solução de problemas

- **O frontend mostra "Não foi possível conectar ao servidor" ou "O servidor não respondeu corretamente":** o `php artisan serve` não está rodando, ou está em outra porta (ajuste o proxy em `frontend/vite.config.ts`).
- **A primeira requisição à API demora vários segundos:** é normal no servidor embutido do PHP (`php artisan serve`) na primeira chamada.
- **`could not find driver` ao migrar:** habilite a extensão `pdo_sqlite` no `php.ini` (no XAMPP, `C:\xampp\php\php.ini`).
- **Erros do Composer sobre a extensão `zip`:** habilite `extension=zip` no `php.ini`.
- **Quer recomeçar com o banco limpo:** `php artisan migrate:fresh --seed` (**apaga todas as tabelas e dados**, inclusive as vendas).
