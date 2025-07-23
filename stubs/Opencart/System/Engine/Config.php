<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Opencart\System\Engine;

/**
 * OpenCart Config stub for IDE support
 */
class Config
{
  protected string $directory;
  private array $path = [];
  private array $data = [];

  public function addPath(string $namespace, string $directory = ''): void {}

  public function get(string $key): mixed {}

  public function set(string $key, $value): void {}

  public function has(string $key): bool {}

  public function load(string $filename): array {}
}
