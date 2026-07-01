<?php

declare(strict_types=1);

namespace Project\StripFileHeaders;

use Composer\Composer;

final class StripperConfig
{
    private const DEFAULT_DIRS = ['vendor'];
    private const DEFAULT_EXTENSIONS = ['php', 'phtml', 'xml'];
    private const DEFAULT_SCOPES = ['vendor'];

    public string $root;

    /**
     * @var list<string>
     */
    public array $dirs;

    /**
     * @var list<string>
     */
    public array $extensions;

    /**
     * @var list<string>
     */
    public array $exclude;

    public bool $enabled;

    /**
     * @param list<string> $dirs
     * @param list<string> $extensions
     * @param list<string> $exclude
     */
    public function __construct(
        string $root,
        array $dirs = self::DEFAULT_DIRS,
        array $extensions = self::DEFAULT_EXTENSIONS,
        array $exclude = [],
        bool $enabled = true
    ) {
        $this->root = rtrim($root, DIRECTORY_SEPARATOR);
        $this->dirs = self::normalizePathList($dirs);
        $this->extensions = array_values(array_unique(array_map('strtolower', $extensions)));
        $this->exclude = self::normalizePathList($exclude);
        $this->enabled = $enabled;
    }

    public static function fromComposer(Composer $composer): self
    {
        $rootPackage = $composer->getPackage();
        $extra = $rootPackage->getExtra();
        $settings = [];

        if (isset($extra['strip-file-headers']) && is_array($extra['strip-file-headers'])) {
            $settings = $extra['strip-file-headers'];
        }

        $root = self::defaultRoot($composer);

        return new self(
            self::stringSetting($settings, 'root', $root),
            self::dirsFromSettings($settings),
            self::listSetting($settings, 'extensions', self::DEFAULT_EXTENSIONS),
            self::listSetting($settings, 'exclude', []),
            self::boolSetting($settings, 'enabled', true)
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function fromCliOptions(array $settings): self
    {
        $root = self::stringSetting($settings, 'root', getcwd() ?: '.');

        return new self(
            $root,
            self::dirsFromSettings($settings),
            self::listSetting($settings, 'extensions', self::DEFAULT_EXTENSIONS),
            self::listSetting($settings, 'exclude', []),
            self::boolSetting($settings, 'enabled', true)
        );
    }

    public function isExcluded(string $relativePath): bool
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');

        foreach ($this->exclude as $excluded) {
            if ($relativePath === $excluded || strpos($relativePath, $excluded . '/') === 0) {
                return true;
            }
        }

        return false;
    }

    private static function defaultRoot(Composer $composer): string
    {
        $vendorDir = $composer->getConfig()->get('vendor-dir');
        if (is_string($vendorDir) && $vendorDir !== '') {
            return dirname($vendorDir);
        }

        return getcwd() ?: '.';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function stringSetting(array $settings, string $key, string $default): string
    {
        if (!isset($settings[$key]) || !is_string($settings[$key]) || $settings[$key] === '') {
            return $default;
        }

        return $settings[$key];
    }

    /**
     * @param array<string, mixed> $settings
     * @param list<string> $default
     * @return list<string>
     */
    private static function listSetting(array $settings, string $key, array $default): array
    {
        if (!isset($settings[$key])) {
            return $default;
        }

        $value = $settings[$key];
        if (is_string($value)) {
            $value = [$value];
        }

        if (!is_array($value)) {
            return $default;
        }

        $items = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $items[] = $item;
            }
        }

        return $items !== [] ? array_values($items) : $default;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private static function boolSetting(array $settings, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $settings)) {
            return $default;
        }

        return filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * @param array<string, mixed> $settings
     * @return list<string>
     */
    private static function dirsFromSettings(array $settings): array
    {
        if (array_key_exists('dirs', $settings)) {
            return self::listSetting($settings, 'dirs', self::DEFAULT_DIRS);
        }

        return self::dirsForNames(self::listSetting($settings, 'scopes', self::DEFAULT_SCOPES));
    }

    /**
     * @param list<string> $names
     * @return list<string>
     */
    private static function dirsForNames(array $names): array
    {
        $dirs = [];
        foreach ($names as $name) {
            $dir = self::dirForName($name);
            if ($dir !== null) {
                $dirs[] = $dir;
            }
        }

        return $dirs !== [] ? $dirs : self::DEFAULT_DIRS;
    }

    private static function dirForName(string $name): ?string
    {
        switch (strtolower(trim($name))) {
            case 'app':
                return 'app';
            case 'vendor':
                return 'vendor';
            default:
                return null;
        }
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private static function normalizePathList(array $paths): array
    {
        $normalized = [];
        foreach ($paths as $path) {
            $path = trim(str_replace('\\', '/', $path), '/');
            if ($path !== '') {
                $normalized[] = $path;
            }
        }

        return array_values(array_unique($normalized));
    }
}
