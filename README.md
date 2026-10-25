# yuan/admin-core

The reusable core of the Laravel 13 admin boilerplate: modules, RBAC (one Level per user), the audit log, Beranda widgets, settings, exports, attachments and the TypeScript type bridge, with the React frontend the projects build against.

This repository is a read-only split of `packages/admin-core` in the boilerplate monorepo. Changes are made there and released with `composer release`; never commit here directly.

## A new project

From the boilerplate monorepo:

```bash
bin/new-project ../client-x --db=client_x --name="Client X"
```

It copies the skeleton at the newest release tag, requires this package of the same version, removes the demo modules, runs `composer setup` (databases `client_x` and `client_x_test`, seeded Super Admins, built assets) and commits the result in a new git repository. `--dry-run` shows the steps.

## Access

The repository is private. Composer reads it over SSH (`"no-api": true` in the project's `repositories.admin-core`), so no GitHub token is needed.

- **Developers:** your own SSH key, on a GitHub account with read access to this repository. Check with `ssh -T git@github.com`.
- **Production servers:** a read-only [deploy key](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/managing-deploy-keys) on this repository, one per server (`ssh-keygen -t ed25519 -f ~/.ssh/admin-core`). A server that already uses a key for its own project repository gets a host alias in `~/.ssh/config`:

  ```
  Host github-admin-core
      HostName github.com
      IdentityFile ~/.ssh/admin-core
      IdentitiesOnly yes
  ```

  and git rewrites this repository's URL to the alias, so the project's `composer.json` stays the same everywhere:

  ```bash
  git config --global url."git@github-admin-core:yuanmaharsyah/yuan-admin-core.git".insteadOf "git@github.com:yuanmaharsyah/yuan-admin-core.git"
  ```
- **Never commit `auth.json`** or a token (the skeleton's `.gitignore` already ignores `auth.json`).

## Upgrading

See [UPGRADE.md](UPGRADE.md) and [CHANGELOG.md](CHANGELOG.md).
