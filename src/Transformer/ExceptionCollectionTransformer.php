<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\ExceptionCollectionDto;

/**
 * ExceptionCollectionTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class ExceptionCollectionTransformer
{
  /**
   * @param array<int, array<string, mixed>> $rawExceptions
   */
  public function __construct(
    private array $rawExceptions,
  ) {}

  public function toDto(): ExceptionCollectionDto
  {
    $exceptionDtos = array_map(
      static fn (array $raw) => (new ExceptionTransformer($raw))->toDto(),
      $this->rawExceptions,
    );

    return new ExceptionCollectionDto(
      title: 'Exception Chain',
      exceptions: $exceptionDtos,
    );
  }
}
