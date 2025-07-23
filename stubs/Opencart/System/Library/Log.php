<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Opencart\System\Library;

/**
 * OpenCart Log stub for IDE support
 */
class Log
{
  private string $file;

  public function __construct(string $filename) {}

  public function write($message): void {}
}
