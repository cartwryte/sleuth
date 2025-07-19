<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Helper;

use RuntimeException;

/**
 * Path helper methods.
 *
 * Allows reliable joining of path parts, normalizing separators
 * and removing redundant slashes.
 *
 * @package Cartwryte\Sleuth\Helper
 *
 * @author Anton Semenov
 */
final class PathHelper
{
  /**
   * Private constructor to prevent instantiation.
   */
  private function __construct() {}

  /**
   * Joins path segments into one, using DIRECTORY_SEPARATOR.
   *
   * @param string ...$segments Path part by part
   *
   * @return string Normalized path
   *
   * @example
   *   PathHelper::join('var', 'www/', '/html', 'index.php')
   *   // → 'var/www/html/index.php'
   */
  public static function join(string ...$segments): string
  {
    if (empty($segments)) {
      return '';
    }

    // Process all segments to remove leading/trailing slashes from each part.
    $parts = array_map(static fn (string $seg): string => trim($seg, '/\\'), $segments);

    // Filter out any empty parts that might result from trimming (e.g., a segment that was just '/').
    $parts = array_filter($parts, static fn (string $part): bool => $part !== '');

    // Join the clean parts with the correct separator.
    $path = implode(DIRECTORY_SEPARATOR, $parts);

    // If the very first original segment was an absolute path, restore the leading slash.
    if (str_starts_with($segments[0], '/') || str_starts_with($segments[0], '\\')) {
      return DIRECTORY_SEPARATOR . $path;
    }

    return $path;
  }

  /**
   * Normalizes path: replaces all slashes with DIRECTORY_SEPARATOR,
   * and removes double/trailing ones.
   *
   * @param string $path
   *
   * @return string
   *
   * @example
   *   PathHelper::normalize('/var//www/html/')
   *   // → '/var/www/html'
   */
  public static function normalize(string $path): string
  {
    $path = preg_replace('#[\\\/]+#', DIRECTORY_SEPARATOR, $path);

    if (is_null($path)) {
      throw new RuntimeException('Failed to normalize path during slash replacement.');
    }

    return $path;
  }
}
