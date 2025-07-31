<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\LayoutDto;

/**
 * LayoutTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class LayoutTransformer
{
  /**
   * @param array<string, mixed> $rawData The full raw data array from ViewModel
   */
  public function __construct(
    private array $rawData,
  ) {}

  public function toDto(): LayoutDto
  {
    return new LayoutDto(
      title: $this->rawData['title'] ?? 'Error | Sleuth',
    );
  }
}
