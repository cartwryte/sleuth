<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

/**
 * TechInfoTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class TechInfoTransformer
{
  /**
   * Gathers technical information about the current request and environment.
   *
   * @return array<string, string>
   */
  public function toArray(): array
  {
    $memoryUsage = memory_get_peak_usage(true);
    $memoryMb = round($memoryUsage / 1024 / 1024, 2);
    $memoryLimit = ini_get('memory_limit');

    return [
      'PHP Version' => PHP_VERSION,
      'OpenCart Version' => defined('VERSION') ? VERSION : 'Unknown',
      'Error Time' => date('Y-m-d H:i:s'),
      'Peak Memory' => "{$memoryMb} MB / {$memoryLimit}",
      'Request Method' => htmlspecialchars($this->getServerString('REQUEST_METHOD'), ENT_QUOTES, 'UTF-8'),
      'Request URI' => htmlspecialchars($this->getServerString('REQUEST_URI'), ENT_QUOTES, 'UTF-8'),
      'Server Software' => htmlspecialchars($this->getServerString('SERVER_SOFTWARE'), ENT_QUOTES, 'UTF-8'),
    ];
  }

  /**
   * Get string value from $_SERVER with fallback
   *
   * @param string $key
   * @param string $default
   *
   * @return string
   */
  private function getServerString(string $key, string $default = 'Unknown'): string
  {
    $value = $_SERVER[$key] ?? null;

    return is_string($value) ? $value : $default;
  }
}
