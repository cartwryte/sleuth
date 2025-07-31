<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\ExceptionDto;

/**
 * ExceptionTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class ExceptionTransformer
{
  /**
   * @param array<string, mixed> $rawException
   */
  public function __construct(
    private array $rawException,
  ) {}

  public function toDto(): ExceptionDto
  {
    return new ExceptionDto(
      class: (string)($this->rawException['class'] ?? 'Exception'),
      message: (string)($this->rawException['message'] ?? ''),
      file: (string)($this->rawException['file'] ?? 'unknown file'),
      line: (int)($this->rawException['line'] ?? 0),
    );
  }
}
