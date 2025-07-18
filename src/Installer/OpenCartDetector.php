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
use Cartwryte\Sleuth\Helper\PathHelper;

/**
 * Detects OpenCart version and directory layout.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author  Anton Semenov
 */
final class OpenCartDetector
{
  private string $rootPath;
  private ?array $versionInfo = null;
  private ?array $pathsCache = null;

  /**
   * @param string $rootPath Path to OpenCart public folder
   */
  public function __construct(string $rootPath)
  {
    $this->rootPath = PathHelper::normalize($rootPath);
  }

  /**
   * @throws OpenCartNotDetectedException
   *
   * @return string Detected version, e.g. "4.1.0.4"
   */
  public function getVersion(): string
  {
    return $this->getVersionInfo()['version'];
  }

  /**
   * @throws OpenCartNotDetectedException
   *
   * @return int Major version (3, 4, etc.)
   */
  public function getMajorVersion(): int
  {
    return (int)explode('.', $this->getVersion(), 2)[0];
  }

  /**
   * @throws OpenCartNotDetectedException
   *
   * @return bool True if OpenCart 4.x
   */
  public function isVersion4(): bool
  {
    return $this->getMajorVersion() === 4;
  }

  /**
   * @return bool True if OpenCart detected
   */
  public function isOpenCart(): bool
  {
    try {
      $paths = $this->getPaths();
    } catch (OpenCartNotDetectedException $e) {
      return false;
    }

    // framework.php + default.php must exist
    if (!is_file($paths['framework'])) {
      return false;
    }

    $defaultConfig = PathHelper::join($paths['config'], 'default.php');

    return is_file($defaultConfig);
  }

  /**
   * @throws OpenCartNotDetectedException
   */
  public function getVersionInfo(): array
  {
    if (!is_null($this->versionInfo)) {
      return $this->versionInfo;
    }

    $this->versionInfo = [];
    $this->detectFromIndexFiles();

    return $this->versionInfo;
  }

  /**
   * @throws OpenCartNotDetectedException
   */
  public function getPaths(): array
  {
    if (!is_null($this->pathsCache)) {
      return $this->pathsCache;
    }

    // $rootPath already points to public dir (upload)
    $public = $this->rootPath;
    // system folder is always under public/system
    $system = PathHelper::join($public, 'system');
    $config = PathHelper::join($system, 'config');
    $framework = PathHelper::join($system, 'framework.php');
    $vendor = PathHelper::join($system, 'vendor.php'); // vendor.php loader
    $storageVendorDir = PathHelper::join($system, 'storage', 'vendor');

    $this->pathsCache = [
      'root' => $public,
      'upload' => $public,
      'system' => $system,
      'config' => $config,
      'framework' => $framework,
      'vendor' => $storageVendorDir,
      'loader' => $vendor,
    ];

    return $this->pathsCache;
  }

  /**
   * @throws OpenCartNotDetectedException
   */
  private function detectFromIndexFiles(): void
  {
    $candidates = [
      PathHelper::join($this->rootPath, 'index.php'),
      PathHelper::join($this->rootPath, 'upload', 'index.php'), // fallback
    ];

    foreach ($candidates as $file) {
      if (is_file($file)) {
        $this->parseVersionFromFile($file);
        $this->versionInfo['source'] = basename($file);

        return;
      }
    }

    throw new OpenCartNotDetectedException("index.php not found in $this->rootPath");
  }

  /**
   * @param string $file
   *
   * @throws OpenCartNotDetectedException
   */
  private function parseVersionFromFile(string $file): void
  {
    $content = file_get_contents($file, false, null, 0, 32768) ?: '';

    if (!preg_match(
      "/define\\s*\\(\\s*['\"]VERSION['\"]\\s*,\\s*['\"]([^'\"]+)['\"]\\s*\\)/",
      $content,
      $m,
    )) {
      throw new OpenCartNotDetectedException("VERSION constant not found in $file");
    }

    $this->versionInfo['version'] = $m[1];
  }
}
