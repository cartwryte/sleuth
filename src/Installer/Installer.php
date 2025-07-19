<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Installer;

use Cartwryte\Sleuth\Exception\OpenCartNotDetectedException;
use Cartwryte\Sleuth\Exception\UnsupportedVersionException;
use Exception;

/**
 * Orchestrates the installation and uninstallation of Cartwryte Sleuth.
 *
 * Coordinates detection of OpenCart, backup creation, autoloader
 * registration, configuration patching, and error handler commenting.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author  Anton Semenov
 */
final class Installer
{
  private OpenCartDetector $detector;
  private BackupManager $backupManager;
  private AutoloaderInstaller $autoloaderInstaller;
  private ConfigPatcher $configPatcher;

  /**
   * Constructor.
   *
   * @param string $rootPath Path to the OpenCart root directory
   */
  public function __construct(string $rootPath)
  {
    $this->detector = new OpenCartDetector($rootPath);
    $this->backupManager = new BackupManager($this->detector);
    $this->autoloaderInstaller = new AutoloaderInstaller($this->detector, $this->backupManager);
    $this->configPatcher = new ConfigPatcher($this->detector, $this->backupManager);
  }

  /**
   * Performs installation of Cartwryte Sleuth.
   *
   * @return array<string,mixed> Report with:
   *                             - success: bool
   *                             - version: string|null
   *                             - steps:   list<string>
   *                             - errors:  list<string>
   */
  public function install(): array
  {
    $report = [
      'success' => false,
      'version' => null,
      'steps' => [],
      'errors' => [],
    ];

    try {
      $this->validateInstallation($report);
      $this->installAutoloader($report);
      $this->applyPatches($report);
      $this->initializeErrorHandler($report);

      $report['success'] = true;
      $report['steps'][] = 'Installation completed successfully';
    } catch (Exception $e) {
      $report['errors'][] = $e->getMessage();
      $report['steps'][] = 'Installation failed: ' . $e->getMessage();
    }

    return $report;
  }

  /**
   * Performs uninstallation of Cartwryte Sleuth.
   *
   * @return array<string,mixed> Report with:
   *                             - success: bool
   *                             - steps:   list<string>
   *                             - errors:  list<string>
   */
  public function uninstall(): array
  {
    $report = [
      'success' => false,
      'steps' => [],
      'errors' => [],
    ];

    try {
      if ($this->configPatcher->removePatches()) {
        $report['steps'][] = 'Configuration restored from backup';
      } else {
        $report['steps'][] = 'Configuration patches already removed';
      }

      if ($this->autoloaderInstaller->uninstall()) {
        $report['steps'][] = 'Autoloader registration removed';
      } else {
        $report['steps'][] = 'Autoloader registration was not present';
      }

      if ($this->backupManager->removeAllBackups()) {
        $report['steps'][] = 'All backups removed';
      }

      $report['success'] = true;
      $report['steps'][] = 'Uninstallation completed successfully';
    } catch (Exception $e) {
      $report['errors'][] = $e->getMessage();
      $report['steps'][] = 'Uninstallation failed: ' . $e->getMessage();
    }

    return $report;
  }

  /**
   * Check whether Sleuth is already installed.
   *
   * @return bool True if autoloader registration is present
   */
  public function isInstalled(): bool
  {
    return $this->autoloaderInstaller->isInstalled();
  }

  /**
   * Get current installation status.
   *
   * @return array<string,mixed> Status including:
   *                             - opencart_detected:      bool
   *                             - opencart_version:       string|null
   *                             - opencart_major_version: int|null
   *                             - sleuth_installed:       bool
   *                             - autoloader_installed:   bool
   *                             - backups_exist:          bool
   *                             - backup_count:           int
   *                             - paths:                  array<string,string>
   */
  public function getStatus(): array
  {
    try {
      $version = $this->detector->getVersion();
      $paths = $this->detector->getPaths();

      return [
        'opencart_detected' => $this->detector->isOpenCart(),
        'opencart_version' => $version,
        'opencart_major_version' => $this->detector->getMajorVersion(),
        'sleuth_installed' => $this->isInstalled(),
        'autoloader_installed' => $this->autoloaderInstaller->isInstalled(),
        'backups_exist' => !empty($this->backupManager->getBackedUpFiles()),
        'backup_count' => count($this->backupManager->getBackedUpFiles()),
        'paths' => $paths,
      ];
    } catch (Exception $e) {
      return [
        'opencart_detected' => false,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Validates OpenCart installation and version support.
   *
   * @param array<string,mixed>& $report Reference to the report to append steps
   *
   * @throws OpenCartNotDetectedException if OpenCart is not detected
   * @throws UnsupportedVersionException  if OpenCart version is not supported
   */
  private function validateInstallation(array &$report): void
  {
    if (!$this->detector->isOpenCart()) {
      throw new OpenCartNotDetectedException('OpenCart installation not detected');
    }

    $version = $this->detector->getVersion();
    $majorVersion = $this->detector->getMajorVersion();

    $report['version'] = $version;
    $report['steps'][] = "Detected OpenCart version: {$version}";

    if (!in_array($majorVersion, [3, 4], true)) {
      throw new UnsupportedVersionException("Unsupported OpenCart version: {$version}");
    }

    $report['steps'][] = "OpenCart version {$version} is supported";
  }

  /**
   * Registers Sleuth namespace in OpenCart autoloader.
   *
   * @param array<string,mixed>& $report Reference to the report to append steps
   *
   * @throws OpenCartNotDetectedException if registration fails
   */
  private function installAutoloader(array &$report): void
  {
    if ($this->autoloaderInstaller->isInstalled()) {
      $report['steps'][] = 'Autoloader registration already present';

      return;
    }

    if (!$this->autoloaderInstaller->install()) {
      throw new OpenCartNotDetectedException('Failed to register autoloader');
    }

    $report['steps'][] = 'Autoloader registration installed';
  }

  /**
   * Applies configuration patches to OpenCart files.
   *
   * @param array<string,mixed>& $report Reference to the report to append steps
   *
   * @throws OpenCartNotDetectedException if patching fails unexpectedly
   */
  private function applyPatches(array &$report): void
  {
    $applied = $this->configPatcher->applyPatches();

    if ($applied) {
      $report['steps'][] = 'Configuration patches applied';
    } else {
      $report['steps'][] = 'Configuration patches already applied';
    }
  }

  /**
   * Adds a final step indicating that runtime error handlers
   * have been commented out (actual init happens in OpenCart bootstrap).
   *
   * @param array<string,mixed>& $report Reference to the report to append steps
   */
  private function initializeErrorHandler(array &$report): void
  {
    $report['steps'][] = 'Error handlers commented out';
  }
}
