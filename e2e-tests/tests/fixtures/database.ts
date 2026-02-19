import { execSync } from 'child_process';

const EXEC_OPTIONS = {
  cwd: process.env.PROJECT_ROOT || '/Users/diegoferreyra/WebDevelopment/conclave',
  timeout: 60_000,
};

export function seedDatabase(): void {
  execSync('ddev php artisan db:seed --no-interaction', EXEC_OPTIONS);
}

export function resetDatabase(): void {
  execSync('ddev php artisan migrate:fresh --seed --no-interaction', EXEC_OPTIONS);
}
