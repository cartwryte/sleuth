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
use Cartwryte\Sleuth\Helper\PathHelper;

/**
 * Configuration Files Patcher for OpenCart
 *
 * Modifies OpenCart configuration files to integrate Cartwryte Sleuth
 * error handling system by updating framework.php and config files.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author Anton Semenov
 */
final class ConfigPatcher
{
  private OpenCartDetector $detector;
  private BackupManager $backupManager;

  public function __construct(OpenCartDetector $detector, BackupManager $backupManager)
  {
    $this->detector = $detector;
    $this->backupManager = $backupManager;
  }

  /**
   * Apply all patches to integrate Sleuth error handler
   *
   * @throws OpenCartNotDetectedException When patching fails
   *
   * @return bool True if all patches applied successfully
   */
  public function applyPatches(): bool
  {
    $paths = $this->detector->getPaths();

    // Track if any patches were applied
    $patched = false;

    if ($this->patchFramework($paths['framework'])) {
      $patched = true;
    }

    if ($this->patchDefaultConfig(PathHelper::join($paths['config'], 'default.php'))) {
      $patched = true;
    }

    if ($this->patchActionConfig(PathHelper::join($paths['config'], 'catalog.php'), 'catalog')) {
      $patched = true;
    }

    if ($this->patchActionConfig(PathHelper::join($paths['config'], 'admin.php'), 'admin')) {
      $patched = true;
    }

    if ($this->patchVendorRegistration(PathHelper::join($paths['system'], 'vendor.php'))) {
      $patched = true;
    }

    // Return true if at least one patch was applied
    return $patched;
  }

  /**
   * Remove all patches and restore original files.
   *
   * @return bool true if all existing backups were restored successfully
   */
  public function removePatches(): bool
  {
    $paths = $this->detector->getPaths();
    $files = [
      $paths['framework'],
      PathHelper::join($paths['config'], 'default.php'),
      PathHelper::join($paths['config'], 'catalog.php'),
      PathHelper::join($paths['config'], 'admin.php'),
      PathHelper::join($paths['system'], 'vendor.php'),
    ];

    $success = true;

    foreach ($files as $file) {
      if ($this->backupManager->hasBackup($file)) {
        // Restore from backup
        if (!$this->backupManager->restore($file)) {
          $success = false;
        }
      } elseif (basename($file) === 'vendor.php') {
        // Fallback: uncomment our snippet manually
        if (!$this->unpatchVendorRegistration($file)) {
          $success = false;
        }
      } else {
        // old fallback for catalog/admin configs
        $base = basename($file);

        if (in_array($base, ['catalog.php', 'admin.php'], true) && !$this->unpatchActionConfig($file)) {
          $success = false;
        }
      }
    }

    return $success;
  }

  /**
   * Patch framework.php — comment out default error handlers
   *
   * @param string $frameworkFile
   *
   * @return bool True if patched, false if already patched
   */
  private function patchFramework(string $frameworkFile): bool
  {
    if (!file_exists($frameworkFile)) {
      throw new OpenCartNotDetectedException("framework.php not found: $frameworkFile");
    }

    // Read array of lines
    $lines = FileHelper::readLinesAsArray($frameworkFile);

    // Comment out blocks
    [$lines, $c1] = FileHelper::commentOutBlock($lines, 'set_error_handler');
    [$lines, $c2] = FileHelper::commentOutBlock($lines, 'set_exception_handler');

    if (!($c1 || $c2)) {
      return false;
    }

    $this->backupManager->backup($frameworkFile);
    file_put_contents(
      $frameworkFile,
      implode("\n", $lines) . "\n",
    );

    return true;
  }

  /**
   * Patch default.php config to add development settings
   *
   * @param string $configFile
   *
   * @return bool True if patched, false if already patched
   */
  private function patchDefaultConfig(string $configFile): bool
  {
    if (!file_exists($configFile)) {
      throw new OpenCartNotDetectedException("default.php config not found: $configFile");
    }

    $content = FileHelper::readFileContent($configFile);

    if (str_contains($content, '// Development (added by Cartwryte Sleuth)')) {
      return false;
    }

    $this->backupManager->backup($configFile);

    $devConfig = "\n// Development (added by Cartwryte Sleuth)\n";
    $devConfig .= "\$_['cartwryte_editor_enabled']   = true;\n";
    $devConfig .= "\$_['cartwryte_editor_default']   = 'phpstorm'; "
        . "// phpstorm, vscode, cursor, sublimetext, zed, windsurf\n";
    $devConfig .= "\$_['cartwryte_editor_path_from'] = ''; "
        . "// Docker container path - leave empty for local development\n";
    $devConfig .= "\$_['cartwryte_editor_path_to']   = ''; "
        . "// Host filesystem path - leave empty for local development\n";

    // Add development configuration before closing PHP tag
    if (str_contains($content, '?>')) {
      $content = str_replace('?>', $devConfig . "\n?>", $content);
    } else {
      // No closing tag - append at end
      $content .= $devConfig;
    }

    file_put_contents($configFile, $content);

    return true;
  }

  /**
   * Patch action config file (catalog.php or admin.php) to disable startup/error
   *
   * @param string $configFile
   * @param string $type       'catalog' or 'admin'
   *
   * @return bool True if patched, false if already patched
   */
  private function patchActionConfig(string $configFile, string $type): bool
  {
    if (!file_exists($configFile)) {
      throw new OpenCartNotDetectedException("$type.php config not found: $configFile");
    }

    $content = FileHelper::readFileContent($configFile);

    if (str_contains($content, "//'startup/error',")) {
      return false;
    }

    $this->backupManager->backup($configFile);

    $content = preg_replace(
      "/^(\\s*)'startup\\/error',/m",
      "$1// 'startup/error', // Cartwryte Sleuth disabled",
      $content,
    );

    file_put_contents($configFile, $content);

    return true;
  }

  /**
   * Revert only our Cartwryte Sleuth patch to the action config.
   *
   * @param string $configFile
   *
   * @return bool True if at least one line was uncommented
   */
  private function unpatchActionConfig(string $configFile): bool
  {
    if (!file_exists($configFile)) {
      return false;
    }

    $content = FileHelper::readFileContent($configFile);

    // Look for lines commented out by us
    $pattern = "/^(\\s*)\\/\\/\\s*'startup\\/error',\\s*\\/\\/\\s*Cartwryte Sleuth disabled/m";
    $replacement = '$1\'startup/error\',';

    $new = preg_replace($pattern, $replacement, $content);

    if (!is_null($new) && $new !== $content) {
      file_put_contents($configFile, $new);

      return true;
    }

    return false;
  }

  /**
   * Patch vendor.php to register Sleuth namespace in OC autoloader.
   *
   * @param string $vendorFile
   *
   * @throws OpenCartNotDetectedException
   *
   * @return bool True if registration was applied
   */
  private function patchVendorRegistration(string $vendorFile): bool
  {
    if (!file_exists($vendorFile)) {
      throw new OpenCartNotDetectedException("vendor.php not found: {$vendorFile}");
    }

    $orig = FileHelper::readFileContent($vendorFile);

    // guard: don't patch twice
    if (str_contains($orig, '// Cartwryte Sleuth')) {
      return false;
    }

    $lines = FileHelper::splitIntoLines($orig);

    // add to the end
    $lines[] = '';
    $lines[] = '// Cartwryte Sleuth';
    // обратный слэш в PHP строках экранируется двойным слэшем
    $lines[] = "\$autoloader->register('Cartwryte\\\\Sleuth', "
        . "DIR_STORAGE . 'vendor/cartwryte/sleuth/src/', true);";

    $new = implode("\n", $lines) . "\n";

    // backup and save
    $this->backupManager->backup($vendorFile);
    file_put_contents($vendorFile, $new);

    return true;
  }

  /**
   * Remove the Sleuth registration snippet from vendor.php.
   *
   * @param string $vendorFile
   *
   * @return bool True if snippet was removed
   */
  private function unpatchVendorRegistration(string $vendorFile): bool
  {
    $content = FileHelper::readFileContent($vendorFile);

    $snippet = "\n// Cartwryte Sleuth\n"
        . "\$autoloader->register(\n"
        . "    'Cartwryte\\\\Sleuth',\n"
        . "    DIR_STORAGE . 'vendor/cartwryte/sleuth/src/',\n"
        . "    true\n"
        . ");\n";

    if (str_contains($content, $snippet)) {
      $new = str_replace($snippet, '', $content);
      file_put_contents($vendorFile, $new);

      return true;
    }

    return false;
  }
}
