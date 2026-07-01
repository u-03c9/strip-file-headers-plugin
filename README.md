# Strip File Headers Composer Plugin

Composer plugin that removes leading license/doc-block comments from `.php`, `.phtml`, and `.xml` files under `app/` and `vendor/` after `composer install` and `composer update`.

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
composer require u-03c9/strip-file-headers-plugin:@dev
```

## Install From GitHub

If the package is hosted at `https://github.com/u-03c9/strip-file-headers-plugin`, users can install it directly as a VCS repository:

```sh
composer config repositories.strip-file-headers vcs https://github.com/u-03c9/strip-file-headers-plugin
composer config allow-plugins.u-03c9/strip-file-headers-plugin true
composer require u-03c9/strip-file-headers-plugin:dev-main
```

After tagging a release:

```sh
git tag v1.0.0
git push origin v1.0.0
```

Users can install the release with:

```sh
composer require u-03c9/strip-file-headers-plugin:^1.0
```

If the package is submitted to Packagist, users do not need the `repositories.strip-file-headers` command.

## Manual Use

The package also exposes a Composer binary:

```sh
vendor/bin/strip-file-headers --dry-run
vendor/bin/strip-file-headers --verbose
vendor/bin/strip-file-headers --scope=app --dry-run
vendor/bin/strip-file-headers --scope=vendor --verbose
```

## Configuration

Defaults match the original script:

```json
{
    "extra": {
        "strip-file-headers": {
            "enabled": true,
            "scope": "both",
            "extensions": ["php", "phtml", "xml"],
            "exclude": []
        }
    }
}
```

Use `scope` to choose where the automatic Composer hook runs:

```json
{
    "extra": {
        "strip-file-headers": {
            "scope": "vendor"
        }
    }
}
```

Allowed values are:

- `both`: scan `app/` and `vendor/`
- `app`: scan `app/` only
- `vendor`: scan `vendor/` only

For custom paths, use `dirs`. When `dirs` is set, it overrides `scope`:

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
