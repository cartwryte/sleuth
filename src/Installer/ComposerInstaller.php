<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Installer;

use Cartwryte\Sleuth\Helper\FileHelper;
use Composer\Composer;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Exception;
use RuntimeException;

/**
 * Composer plugin installer for Cartwryte Sleuth.
 *
 * Hooks into Composer events to trigger Sleuth error handler
 * installation and uninstallation in an OpenCart project.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author  Anton Semenov
 */
final class ComposerInstaller implements EventSubscriberInterface, PluginInterface
{
  private Composer $composer;
  private ?string $configFilePath = null;

  /**
   * Returns the events this plugin subscribes to.
   *
   * @return array<string, string>
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ScriptEvents::POST_INSTALL_CMD => 'onPostInstallOrUpdate',
      ScriptEvents::POST_UPDATE_CMD => 'onPostInstallOrUpdate',
      PackageEvents::PRE_PACKAGE_UNINSTALL => 'onPrePackageUninstall',
    ];
  }

  /**
   * Called by Composer when the plugin is activated.
   *
   * @param Composer    $composer Composer instance
   * @param IOInterface $io       IO interface for console output
   *
   * @return void
   */
  public function activate(Composer $composer, IOInterface $io): void
  {
    $this->composer = $composer;
  }

  /**
   * Called by Composer when the plugin is deactivated.
   * No action required.
   *
   * @param Composer    $composer
   * @param IOInterface $io
   *
   * @return void
   */
  public function deactivate(Composer $composer, IOInterface $io): void
  {
    // TODO
  }

  /**
   * Called by Composer when the plugin is uninstalled.
   * No action required.
   *
   * @param Composer    $composer
   * @param IOInterface $io
   *
   * @return void
   */
  public function uninstall(Composer $composer, IOInterface $io): void
  {
    // TODO
  }

  /**
   * Handles Composer post-install and post-update events.
   * Delegates all the work to the "clean" Installer.
   *
   * @param Event $event Composer script event
   *
   * @return void
   */
  public function onPostInstallOrUpdate(Event $event): void
  {
    $io = $event->getIO();
    $io->writeError('<info>=== Cartwryte Sleuth Installer ===</info>');

    $vendorDir = $this->composer->getConfig()->get('vendor-dir');

    if (!is_string($vendorDir)) {
      $io->writeError('<error>Cannot determine vendor directory.</error>');

      return;
    }

    $this->configFilePath = $vendorDir . '/cartwryte/sleuth/src/config/paths.php';

    // Detect OpenCart root
    $root = $this->detectRoot();

    if (is_null($root)) {
      $io->writeError('<error>OpenCart root not detected. Aborting.</error>');

      return;
    }

    // Save the detected path to our portable config file
    $this->savePathConfig($root, $io);

    // Call the "pure" Installer
    try {
      $installer = new Installer();
      $report = $installer->install();
    } catch (Exception $e) {
      $io->writeError('<error>Installer error: ' . $e->getMessage() . '</error>');

      return;
    }

    // Render report
    if ($report['success']) {
      $io->writeError('<info>✔ Sleuth installed successfully</info>');
    } else {
      $io->writeError('<error>✖ Sleuth installation failed</error>');
    }

    $io->writeError('<info>Steps:</info>');

    foreach ($report['steps'] as $step) {
      $io->writeError('  - ' . $step);
    }

    if (!empty($report['errors'])) {
      $io->writeError('<error>Errors:</error>');

      foreach ($report['errors'] as $error) {
        $io->writeError('  - ' . $error);
      }
    }

    $io->writeError('');
  }

  public function onPrePackageUninstall(PackageEvent $event): void
  {
    $operation = $event->getOperation();

    if (!$operation instanceof UninstallOperation) {
      return;
    }

    $package = $operation->getPackage();

    if ($package->getName() !== 'cartwryte/sleuth') {
      return;
    }

    $io = $event->getIO();
    $io->writeError('<info>=== Cartwryte Sleuth Uninstaller ===</info>');

    $root = $this->detectRoot();

    if (is_null($root)) {
      $io->writeError('<error>OpenCart root not detected. Aborting uninstallation.</error>');

      return;
    }

    try {
      $installer = new Installer();
      $report = $installer->uninstall();
    } catch (Exception $e) {
      $io->writeError('<error>Uninstaller error: ' . $e->getMessage() . '</error>');

      return;
    }

    if ($report['success']) {
      $io->writeError('<info>✔ Sleuth uninstalled successfully</info>');
    } else {
      $io->writeError('<error>✖ Sleuth uninstallation failed</error>');
    }

    $io->writeError('<info>Steps:</info>');

    foreach ($report['steps'] as $step) {
      $io->writeError('  - ' . $step);
    }

    if (!empty($report['errors'])) {
      $io->writeError('<error>Errors:</error>');

      foreach ($report['errors'] as $err) {
        $io->writeError('  - ' . $err);
      }
    }

    $io->writeError('');
  }

  /**
   * Walks up from vendor-dir to find the first directory
   * containing an index.tpl. That is assumed to be OpenCart root.
   *
   * @return string|null path to OpenCart root or null if not found
   */
  private function detectRoot(): ?string
  {
    $vendorDir = $this->composer->getConfig()->get('vendor-dir');

    if (!is_string($vendorDir)) {
      return null;
    }

    $path = realpath($vendorDir) ?: $vendorDir;
    $maxDepth = 5;

    for ($i = 0; $i < $maxDepth; $i++) {
      if (is_file($path . '/index.tpl')) {
        return $path;
      }

      $parent = dirname($path);

      if ($parent === $path) {
        break;
      }

      $path = $parent;
    }

    return null;
  }

  /**
   * Saves the detected OpenCart root path to a portable config file.
   *
   * @param string      $rootPath
   * @param IOInterface $io
   */
  private function savePathConfig(string $rootPath, IOInterface $io): void
  {
    if (!$this->configFilePath) {
      return;
    }

    $configDir = dirname($this->configFilePath);
    $realRootPath = realpath($rootPath);

    if ($realRootPath === false) {
      throw new RuntimeException("Failed to resolve real path for OpenCart root: $rootPath");
    }

    $content = "<?php\n\n// This file is auto-generated by ComposerInstaller. Do not edit.\n\n";
    $content .= "return ['opencart_root' => '$realRootPath'];\n";

    FileHelper::ensureDirectoryExists($configDir);
    FileHelper::writeFile($this->configFilePath, $content);

    $io->writeError('  - OpenCart root path saved to configuration.');
  }
}
