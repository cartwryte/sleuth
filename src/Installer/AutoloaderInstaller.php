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
use Cartwryte\Sleuth\Helper\FileHelper;

/**
 * Autoloader Installer for Cartwryte Sleuth in OpenCart.
 *
 * Registers/unregisters the Cartwryte\Sleuth namespace in
 * OpenCart's vendor.php so that its PSR-4 classes load natively.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author  Anton Semenov
 */
final class AutoloaderInstaller
{
  private OpenCartDetector $detector;
  private BackupManager $backupManager;

  public function __construct(
    OpenCartDetector $detector,
    BackupManager $backupManager,
  ) {
    $this->detector = $detector;
    $this->backupManager = $backupManager;
  }

  /**
   * Check if Sleuth namespace is already registered in vendor.php.
   *
   * @return bool
   */
  public function isInstalled(): bool
  {
    $paths = $this->detector->getPaths();
    $vendorFile = $paths['system'] . '/vendor.php';

    if (!file_exists($vendorFile)) {
      return false;
    }

    $content = FileHelper::readFileContent($vendorFile);

    return str_contains($content, '// Cartwryte Sleuth');
  }

  /**
   * Install Sleuth namespace registration into vendor.php.
   *
   * @throws OpenCartNotDetectedException
   *
   * @return bool True if registration was applied
   */
  public function install(): bool
  {
    $paths = $this->detector->getPaths();
    $vendorFile = $paths['system'] . '/vendor.php';

    if (!file_exists($vendorFile)) {
      throw new OpenCartNotDetectedException("vendor.php not found: $vendorFile");
    }

    // guard: don't patch twice
    if ($this->isInstalled()) {
      return false;
    }

    $lines = FileHelper::splitIntoLines(
      FileHelper::readFileContent($vendorFile),
    );

    // insert at the end
    $lines[] = '';
    $lines[] = '// Cartwryte Sleuth';
    $lines[] = "\$autoloader->register('Cartwryte\\\\Sleuth', "
        . "DIR_STORAGE . 'vendor/cartwryte/sleuth/src/', true);";

    $new = implode("\n", $lines) . "\n";

    $this->backupManager->backup($vendorFile);
    file_put_contents($vendorFile, $new);

    return true;
  }

  /**
   * Uninstall Sleuth namespace registration from vendor.php.
   *
   * @throws OpenCartNotDetectedException
   *
   * @return bool True if unregistration was applied
   */
  public function uninstall(): bool
  {
    $paths = $this->detector->getPaths();
    $vendorFile = $paths['system'] . '/vendor.php';

    if ($this->backupManager->hasBackup($vendorFile)) {
      return $this->backupManager->restore($vendorFile);
    }

    $content = FileHelper::readFileContent($vendorFile);

    $pattern = "/^\\s*\\/\\/ Cartwryte Sleuth\n"
        . "\$autoloader->register\\('Cartwryte\\\\Sleuth'.*;\n/m";

    $new = preg_replace($pattern, '', $content);

    return !is_null($new) && file_put_contents($vendorFile, $new) !== false;
  }
}
