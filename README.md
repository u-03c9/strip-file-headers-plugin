# Strip File Headers Composer Plugin

Composer plugin that removes leading license/doc-block comments from `.php`, `.phtml`, and `.xml` files under `vendor/` after dev-mode `composer install` and `composer update` runs.

This packages the behavior you would otherwise add to a root project like this:

```json
{
    "scripts": {
        "strip-file-headers": "@php scripts/strip-file-headers.php",
        "post-install-cmd": [
            "@strip-file-headers"
        ],
        "post-update-cmd": [
            "@strip-file-headers"
        ]
    }
}
```

Composer dependencies cannot normally inject root `scripts` into the consuming project. This package is therefore implemented as a Composer plugin: once the project allows the plugin, it subscribes to Composer's `post-install-cmd` and `post-update-cmd` events directly.

## Install From A Local Path

From the consuming project:

```sh
composer config repositories.strip-file-headers path /absolute/path/to/strip-file-headers-plugin
composer config allow-plugins.u-03c9/strip-file-headers-plugin true
composer require --dev u-03c9/strip-file-headers-plugin:@dev
```

## Install From GitHub

If the package is hosted at `https://github.com/u-03c9/strip-file-headers-plugin`, users can install it directly as a VCS repository:

```sh
composer config repositories.strip-file-headers vcs https://github.com/u-03c9/strip-file-headers-plugin
composer config allow-plugins.u-03c9/strip-file-headers-plugin true
composer require --dev u-03c9/strip-file-headers-plugin:dev-main
```

If Composer reports that the GitHub API limit is exhausted while resolving the repository, the plugin has not run yet. Composer is rate-limited while reading package metadata from GitHub. For local development, prefer the path repository install above because it does not call GitHub. For a public GitHub repository without a token, configure the repository with `no-api` so Composer clones it through Git instead of using the GitHub API:

```sh
composer config repositories.strip-file-headers '{"type":"vcs","url":"https://github.com/u-03c9/strip-file-headers-plugin","no-api":true}'
composer config allow-plugins.u-03c9/strip-file-headers-plugin true
composer require --dev u-03c9/strip-file-headers-plugin:dev-main
```

Alternatively, configure a GitHub OAuth token for Composer. Do not commit `auth.json`:

```sh
composer config --global github-oauth.github.com YOUR_TOKEN
```

After tagging a release:

```sh
git tag v1.0.0
git push origin v1.0.0
```

Users can install the release with:

```sh
composer require --dev u-03c9/strip-file-headers-plugin:^1.0
```

If the package is submitted to Packagist, users do not need the `repositories.strip-file-headers` command.

Install it as a dev dependency. The automatic Composer hook skips runs where Composer is operating without dev dependencies, such as `composer install --no-dev`.

## Manual Use

The package also exposes a Composer binary:

```sh
vendor/bin/strip-file-headers --dry-run
vendor/bin/strip-file-headers --verbose
vendor/bin/strip-file-headers app --dry-run
vendor/bin/strip-file-headers vendor app --dry-run
vendor/bin/strip-file-headers --dir=vendor --verbose
```

## Configuration

Default configuration:

```json
{
    "extra": {
        "strip-file-headers": {
            "enabled": true,
            "dirs": ["vendor"],
            "extensions": ["php", "phtml", "xml"],
            "exclude": [
                "vendor/autoload.php",
                "vendor/composer",
                "app/etc"
            ]
        }
    }
}
```

Use `dirs` to choose scan locations for the automatic Composer hook:

```json
{
    "extra": {
        "strip-file-headers": {
            "dirs": ["vendor"]
        }
    }
}
```

List every directory you want scanned:

```json
{
    "extra": {
        "strip-file-headers": {
            "dirs": ["vendor", "app"]
        }
    }
}
```

This scans `vendor/` and `app/`.

Custom paths are supported:

```json
{
    "extra": {
        "strip-file-headers": {
            "dirs": ["app/code", "vendor"]
        }
    }
}
```

You can disable automatic stripping temporarily with:

```sh
STRIP_FILE_HEADERS_DISABLE=1 composer install
```

## Important License Warning

This rewrites third-party files under `vendor/`. Several packages are distributed under licenses whose terms require retaining copyright notices. Removing those notices may breach those terms; that is a decision for the project owner.
