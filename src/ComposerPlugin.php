<?php

declare(strict_types=1);

namespace Project\StripFileHeaders;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'stripHeaders',
            ScriptEvents::POST_UPDATE_CMD => 'stripHeaders',
        ];
    }

    public function stripHeaders(Event $event): void
    {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $config = StripperConfig::fromComposer($composer);
        if (!$config->enabled) {
            $io->write('<info>[strip-file-headers] disabled by configuration.</info>', true, IOInterface::VERBOSE);
            return;
        }

        if (self::envFlagIsTruthy('STRIP_FILE_HEADERS_DISABLE')) {
            $io->write('<info>[strip-file-headers] disabled by STRIP_FILE_HEADERS_DISABLE.</info>', true, IOInterface::VERBOSE);
            return;
        }

        if (!$event->isDevMode()) {
            $io->write('<info>[strip-file-headers] skipped because Composer is running without dev dependencies.</info>', true, IOInterface::VERBOSE);
            return;
        }

        $verbose = $io->isVerbose();
        $stripper = new HeaderStripper();
        $result = $stripper->strip(
            $config,
            false,
            $verbose,
            static function (string $message) use ($io): void {
                $io->write($message);
            }
        );

        $io->write(sprintf(
            '<info>[strip-file-headers] scanned %d file(s); stripped %d file(s).</info>',
            $result->scanned,
            $result->changed
        ));
    }

    private static function envFlagIsTruthy(string $name): bool
    {
        $value = getenv($name);
        if ($value === false) {
            return false;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
