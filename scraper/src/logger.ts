/**
 * Simple progress logger for scraper operations.
 */
export function progressBar(current: number, total: number, label: string): void {
  const pct = Math.round((current / total) * 100);
  const filled = Math.round(pct / 5);
  const bar = "█".repeat(filled) + "░".repeat(20 - filled);
  process.stdout.write(`\r  [${bar}] ${pct}% — ${label} (${current}/${total})`);
  if (current === total) {
    process.stdout.write("\n");
  }
}

export function log(message: string): void {
  console.log(`  ${message}`);
}

export function logSection(title: string): void {
  console.log(`\n${"─".repeat(60)}`);
  console.log(`  ${title}`);
  console.log(`${"─".repeat(60)}`);
}
