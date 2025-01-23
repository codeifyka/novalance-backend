import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
    plugins: [vue()],
    server: {
        proxy: {
            '/api': {
                target: 'http://127.0.0.1:8000',
            },
            '/storage': {
                target: 'http://127.0.0.1:8000',
            },
            '/ws': {
                target: 'wss://localhost:8001',
                ws: true,
                secure: false
            }
        }
    },
    esbuild: {
        loader: "ts",
        include: /.*\.(ts|component\.ts)$/,
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './src'),
        },
    },
});
