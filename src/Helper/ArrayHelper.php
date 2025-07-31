<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Helper;

/**
 * ArrayHelper
 * Type-safe array value extraction helper
 *
 * @package Cartwryte\Sleuth\Helper
 *
 * @author  Anton Semenov
 */
final class ArrayHelper
{
  /**
   * Get string value from array with fallback
   *
   * @param array<string, mixed> $array
   * @param string               $key
   * @param string               $default
   *
   * @return string
   */
  public static function getString(array $array, string $key, string $default = ''): string
  {
    $value = $array[$key] ?? null;

    if (is_string($value)) {
      return $value;
    }

    if (is_scalar($value)) {
      return (string)$value;
    }

    return $default;
  }

  /**
   * Get int value from array with fallback
   *
   * @param array<string, mixed> $array
   * @param string               $key
   * @param int                  $default
   *
   * @return int
   */
  public static function getInt(array $array, string $key, int $default = 0): int
  {
    $value = $array[$key] ?? null;

    if (is_int($value)) {
      return $value;
    }

    if (is_numeric($value)) {
      return (int)$value;
    }

    return $default;
  }

  /**
   * Get bool value from array with fallback
   *
   * @param array<string, mixed> $array
   * @param string               $key
   * @param bool                 $default
   *
   * @return bool
   */
  public static function getBool(array $array, string $key, bool $default = false): bool
  {
    $value = $array[$key] ?? null;

    if (is_bool($value)) {
      return $value;
    }

    return (bool)$value;
  }

  /**
   * Get array value from array with fallback
   *
   * @param array<string, mixed> $array
   * @param string               $key
   * @param array<mixed>         $default
   *
   * @return array<mixed>
   */
  public static function getArray(array $array, string $key, array $default = []): array
  {
    $value = $array[$key] ?? null;

    return is_array($value) ? $value : $default;
  }
}
