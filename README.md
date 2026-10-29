# yuanmaharsyah/admin-core

The core of [Admin Boilerplate](https://github.com/yuanmaharsyah/admin-boilerplate), a Laravel 13 + Inertia 3 + React 19 admin panel: modules declared in `module.php`, level-based RBAC with a permission matrix, a tamper-resistant audit log with personal-data masking, a module generator, settings, exports and attachments.

**Documentation, issues and contributions: [github.com/yuanmaharsyah/admin-boilerplate](https://github.com/yuanmaharsyah/admin-boilerplate).** This repository is a read-only split of `packages/admin-core` there, published on every release; don't open pull requests here.

## Install

You don't require this package by hand. Start a project from the skeleton, which requires it:

```bash
composer create-project yuanmaharsyah/admin-skeleton my-app
```

Then follow the [Getting Started](https://github.com/yuanmaharsyah/admin-boilerplate#getting-started) guide. Upgrading: [UPGRADE.md](UPGRADE.md); changes: [CHANGELOG.md](CHANGELOG.md).

## Private forks over SSH

If you run your own fork of the package in a private repository, point your project at it instead of Packagist:

```bash
composer config repositories.admin-core '{"type": "vcs", "url": "git@github.com:<you>/<your-admin-core>.git", "no-api": true}'
composer require yuanmaharsyah/admin-core:^1.0
```

`"no-api": true` makes Composer use plain git over SSH, so no GitHub token is needed:

- **Developers:** your own SSH key, on an account with read access. Check with `ssh -T git@github.com`.
- **Production servers:** a read-only [deploy key](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/managing-deploy-keys) on that repository, one per server (`ssh-keygen -t ed25519 -f ~/.ssh/admin-core`). If the server already uses another key for github.com, add a host alias in `~/.ssh/config`:

  ```
  Host github-admin-core
      HostName github.com
      IdentityFile ~/.ssh/admin-core
      IdentitiesOnly yes
  ```

  and let git rewrite the URL, so the project's `composer.json` stays the same everywhere:

  ```bash
  git config --global url."git@github-admin-core:<you>/<your-admin-core>.git".insteadOf "git@github.com:<you>/<your-admin-core>.git"
  ```
- **Never commit `auth.json`** or a token (the skeleton's `.gitignore` already ignores `auth.json`).

## License

MIT. See [LICENSE](LICENSE).
