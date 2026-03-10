import { defineConfig } from '@playwright/test';

declare const process: { env: Record<string, string | undefined> };

const env = process.env;

export default defineConfig({
  testDir: 'tests/e2e',
  fullyParallel: false,
  retries: env.CI ? 1 : 0,
  workers: env.CI ? 1 : undefined,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: env.E2E_BASE_URL || 'http://127.0.0.1:8010',
    headless: true,
    viewport: { width: 1536, height: 960 },
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
  },
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8010',
    url: 'http://127.0.0.1:8010',
    reuseExistingServer: !env.CI,
    timeout: 120000,
  },
});
