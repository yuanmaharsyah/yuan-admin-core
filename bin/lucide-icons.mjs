// Writes resources/icons/lucide.json: every icon name of the lucide-react
// installed in the current project (run from the project root, e.g.
// `npm --prefix skeleton run icons`). ModuleDefinition validates `icon` against
// it; the root `composer check` regenerates it and fails on a diff.
import { execFileSync } from 'node:child_process';
import { writeFileSync } from 'node:fs';

// Imported from the project directory with ESM resolution (lucide-react ships
// no CommonJS build of `dynamic`, so require.resolve() can't find it).
const names = JSON.parse(
    execFileSync(
        process.execPath,
        [
            '--input-type=module',
            '-e',
            "const { iconNames } = await import('lucide-react/dynamic'); console.log(JSON.stringify([...iconNames].sort()));",
        ],
        { cwd: process.cwd(), encoding: 'utf8' },
    ),
);

writeFileSync(
    new URL('../resources/icons/lucide.json', import.meta.url),
    `${JSON.stringify(names, null, 1)}\n`,
);
