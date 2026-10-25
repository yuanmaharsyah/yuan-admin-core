---
name: admin-translate
description: "Use when adding or changing user-facing text in an admin module's React pages (labels, headings, messages), in Bahasa Indonesia and English."
---

# Translate a module's UI strings

1. `modules/{Name}/resources/js/lang/id.ts`: `export const id = { '{slug}.title': '…' } as const satisfies ModuleDictionary<'{slug}'>;` plus `declare module '@admin-core/lib/i18n' { interface Dictionaries { {slug}: typeof id } }`. A key without the `{slug}.` prefix fails `tsc`.
2. `modules/{Name}/resources/js/lang/en.ts`: `export const en: Record<keyof typeof id, string> = { … }`; a missing or extra key fails `tsc`.
3. In components: `const t = useT();` then `t('{slug}.title')`; an unknown key fails `tsc`. The app loads every module's dictionaries itself.
4. Server strings (enum labels, flash messages, validation attribute names) use `__()`.
5. Bahasa Indonesia is the default; never hardcode user text in TSX. Run `npm run typecheck`, then `composer check`.
