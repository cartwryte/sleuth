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
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * AssetInstaller
 *
 * Copies pre-built Luminary assets to OpenCart public directory.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author  Anton Semenov
 */
final class AssetInstaller
{
  private string $sourceDir;
  private string $targetDir;

  public function __construct(string $openCartRoot)
  {
    $this->sourceDir = dirname(__DIR__) . '/Frontend/dist';
    $this->targetDir = $openCartRoot . '/catalog/view/sleuth';
  }

  /**
   * Install pre-built Luminary assets.
   */
  public function install(): bool
  {
    if (!is_dir($this->sourceDir)) {
      throw new RuntimeException("Pre-built assets not found: {$this->sourceDir}");
    }

    FileHelper::ensureDirectoryExists($this->targetDir);

    return $this->copyDirectory($this->sourceDir, $this->targetDir);
  }

  /**
   * Check if assets are already installed.
   */
  public function isInstalled(): bool
  {
    return is_dir($this->targetDir) && !empty(glob($this->targetDir . '/*'));
  }

  /**
   * Recursively copy directory contents.
   *
   * @param string $source
   * @param string $target
   */
  private function copyDirectory(string $source, string $target): bool
  {
    $directoryIterator = new RecursiveDirectoryIterator(
      $source,
      FilesystemIterator::SKIP_DOTS,
    );

    $iterator = new RecursiveIteratorIterator(
      $directoryIterator,
      RecursiveIteratorIterator::SELF_FIRST,
    );

    /** @var SplFileInfo $item */
    foreach ($iterator as $item) {
      $relativePath = $directoryIterator->getSubPath();

      if ($relativePath !== '') {
        $relativePath .= DIRECTORY_SEPARATOR;
      }
      $relativePath .= $item->getFilename();

      $targetPath = $target . DIRECTORY_SEPARATOR . $relativePath;

      if ($item->isDir()) {
        FileHelper::ensureDirectoryExists($targetPath);
      } else {
        if (!copy($item->getRealPath(), $targetPath)) {
          return false;
        }
      }
    }

    return true;
  }
}
