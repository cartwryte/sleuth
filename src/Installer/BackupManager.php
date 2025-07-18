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
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Backup Manager for OpenCart Files
 *
 * Creates and manages backups of OpenCart files before modification
 * to ensure safe installation and rollback capabilities.
 *
 * @package Cartwryte\Sleuth\Installer
 *
 * @author Anton Semenov
 */
final class BackupManager
{
  private OpenCartDetector $detector;
  private string $backupDir;

  public function __construct(OpenCartDetector $detector)
  {
    $this->detector = $detector;
    $this->backupDir = $this->detector->getPaths()['system'] . '/storage/backup/cartwryte-sleuth';
  }

  /**
   * Create backup of a file
   *
   * @param string $filePath Path to file to backup
   *
   * @throws OpenCartNotDetectedException When file cannot be backed up
   *
   * @return string Path to backup file
   */
  public function backup(string $filePath): string
  {
    if (!file_exists($filePath)) {
      throw new OpenCartNotDetectedException("File not found for backup: {$filePath}");
    }

    $backupPath = $this->getBackupPath($filePath);
    $this->ensureBackupDirectoryForFile($backupPath);

    if (!copy($filePath, $backupPath)) {
      throw new OpenCartNotDetectedException("Failed to create backup: {$backupPath}");
    }

    return $backupPath;
  }

  /**
   * Restore file from backup
   *
   * @param string $filePath Original file path to restore
   *
   * @return bool True if restoration successful
   */
  public function restore(string $filePath): bool
  {
    $backupPath = $this->getBackupPath($filePath);

    if (!file_exists($backupPath)) {
      return false; // No backup exists
    }

    return copy($backupPath, $filePath);
  }

  /**
   * Check if backup exists for a file
   *
   * @param string $filePath Original file path
   *
   * @return bool True if backup exists
   */
  public function hasBackup(string $filePath): bool
  {
    return file_exists($this->getBackupPath($filePath));
  }

  /**
   * Get all backed up files
   *
   * @return array<string> List of original file paths that have backups
   */
  public function getBackedUpFiles(): array
  {
    if (!is_dir($this->backupDir)) {
      return [];
    }

    $backups = [];
    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->backupDir, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $file) {
      if ($file->isFile() && str_ends_with($file->getFilename(), '.backup')) {
        $relativePath = substr($file->getPathname(), strlen($this->backupDir . '/'));
        $originalPath = str_replace('.backup', '', $relativePath);
        $backups[] = $this->detector->getPaths()['root'] . '/' . $originalPath;
      }
    }

    return $backups;
  }

  /**
   * Remove backup for a file
   *
   * @param string $filePath Original file path
   *
   * @return bool True if backup was removed
   */
  public function removeBackup(string $filePath): bool
  {
    $backupPath = $this->getBackupPath($filePath);

    if (file_exists($backupPath)) {
      return unlink($backupPath);
    }

    return true; // No backup to remove
  }

  /**
   * Remove all backups
   *
   * @return bool True if all backups were removed
   */
  public function removeAllBackups(): bool
  {
    if (!is_dir($this->backupDir)) {
      return true; // Nothing to remove
    }

    return $this->removeDirectory($this->backupDir);
  }

  /**
   * Get backup path for a file
   *
   * @param string $filePath Original file path
   *
   * @return string Path where backup should be stored
   */
  private function getBackupPath(string $filePath): string
  {
    $rootPath = $this->detector->getPaths()['root'];
    $relativePath = str_replace($rootPath . '/', '', $filePath);

    return $this->backupDir . '/' . $relativePath . '.backup';
  }

  /**
   * Ensure backup directory exists
   *
   * @param string $backupPath
   *
   * @throws OpenCartNotDetectedException When directory cannot be created
   *
   * @return void
   */
  private function ensureBackupDirectoryForFile(string $backupPath): void
  {
    $backupDir = dirname($backupPath);

    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
      throw new OpenCartNotDetectedException("Cannot create backup directory: {$backupDir}");
    }
  }

  /**
   * Recursively remove directory
   *
   * @param string $dir Directory to remove
   *
   * @return bool True if successful
   */
  private function removeDirectory(string $dir): bool
  {
    if (!is_dir($dir)) {
      return false;
    }

    $files = array_diff(scandir($dir), ['.', '..']);

    foreach ($files as $file) {
      $path = $dir . '/' . $file;
      is_dir($path) ? $this->removeDirectory($path) : unlink($path);
    }

    return rmdir($dir);
  }
}
