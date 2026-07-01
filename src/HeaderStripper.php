<?php

declare(strict_types=1);

namespace Project\StripFileHeaders;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class HeaderStripper
{
    public function strip(StripperConfig $config, bool $dryRun, bool $verbose, ?callable $logger = null): StripResult
    {
        $scanned = 0;
        $changed = 0;

        foreach ($config->dirs as $dir) {
            $base = $config->root . DIRECTORY_SEPARATOR . $dir;
            if (!is_dir($base)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo || !$file->isFile()) {
                    continue;
                }

                $ext = strtolower($file->getExtension());
                if (!in_array($ext, $config->extensions, true)) {
                    continue;
                }

                $path = $file->getPathname();
                $relative = self::relativePath($config->root, $path);
                if ($config->isExcluded($relative)) {
                    continue;
                }

                $scanned++;
                $content = file_get_contents($path);
                if ($content === false || $content === '') {
                    continue;
                }

                $stripped = self::stripHeader($content, $ext);
                if ($stripped === null || $stripped === $content) {
                    continue;
                }

                $changed++;
                if ($dryRun) {
                    self::log($logger, "would strip: {$relative}");
                    continue;
                }

                file_put_contents($path, $stripped);
                if ($verbose) {
                    self::log($logger, "stripped: {$relative}");
                }
            }
        }

        return new StripResult($scanned, $changed);
    }

    public static function stripHeader(string $content, string $ext): ?string
    {
        $bom = '';
        if (strncmp($content, "\xEF\xBB\xBF", 3) === 0) {
            $bom = "\xEF\xBB\xBF";
            $content = substr($content, 3);
        }

        if ($ext === 'xml') {
            $new = self::stripXmlHeader($content);
        } elseif (
            strncmp($content, '<?php', 5) === 0
            || strncmp($content, '<?=', 3) === 0
            || strncmp($content, '<?', 2) === 0
        ) {
            $new = self::stripPhpHeader($content);
        } elseif (strncmp($content, '<!--', 4) === 0) {
            $new = self::stripXmlHeader($content);
        } else {
            $new = null;
        }

        if ($new === null) {
            return null;
        }

        return $bom . $new;
    }

    private static function stripPhpHeader(string $content): ?string
    {
        $pattern = '/^(<\?php|<\?=|<\?)\s*(?:(?:\/\*[\s\S]*?\*\/|\/\/[^\r\n]*|#[^\r\n]*)\s*)+/';
        $new = preg_replace($pattern, '$1' . "\n\n", $content, 1, $count);

        return $count > 0 ? $new : null;
    }

    private static function stripXmlHeader(string $content): ?string
    {
        $pattern = '/^(\s*<\?xml[\s\S]*?\?>)?\s*<!--[\s\S]*?-->[ \t]*\r?\n?/';

        if (!preg_match($pattern, $content, $matches)) {
            return null;
        }

        $declaration = isset($matches[1]) ? trim($matches[1]) : '';
        $rest = substr($content, strlen($matches[0]));

        return $declaration !== '' ? $declaration . "\n" . $rest : $rest;
    }

    private static function relativePath(string $root, string $path): string
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncmp($path, $root, strlen($root)) !== 0) {
            return $path;
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($root)));
    }

    private static function log(?callable $logger, string $message): void
    {
        if ($logger === null) {
            return;
        }

        $logger($message);
    }
}
