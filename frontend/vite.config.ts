import tailwindcss from '@tailwindcss/vite'; // Tailwind CSS 4 integrado ao Vite (sem arquivo de config)
import react from '@vitejs/plugin-react'; // suporte a React/JSX
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [react(), tailwindcss()],
  server: {
    port: 5173,
    // === PROXY DA API ===
    // Chamadas do navegador para /api/... são repassadas ao Laravel (php artisan serve).
    // Como o navegador só fala com o Vite, não há problema de CORS no desenvolvimento.
    proxy: {
      '/api': { target: 'http://127.0.0.1:8000', changeOrigin: true },
    },
  },
});
