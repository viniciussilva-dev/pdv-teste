<?php

use Illuminate\Foundation\Application; // aplicação Laravel
use Illuminate\Foundation\Configuration\Exceptions; // configuração do tratamento de erros
use Illuminate\Foundation\Configuration\Middleware; // configuração de middlewares
use Illuminate\Http\Request; // usada para saber se a rota é da API
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException; // erro 404 (rota ou registro inexistente)

return Application::configure(basePath: dirname(__DIR__))
    // === ROTAS ===
    // "api:" registra routes/api.php com o prefixo /api e o grupo de middleware "api"
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    // === ERROS DA API ===
    ->withExceptions(function (Exceptions $exceptions) {
        // Toda rota /api/* responde em JSON (inclusive erros de validação 422),
        // mesmo que o cliente não envie o cabeçalho "Accept: application/json"
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 404 padronizado e em português (rota inexistente ou registro não encontrado)
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Recurso não encontrado.'], 404);
            }
        });
    })->create();
