<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\SuggestionCollectionDto;

/**
 * SuggestionCollectionTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class SuggestionCollectionTransformer
{
  /**
   * @param array<int, array{icon: string, text: string}> $rawSuggestions
   */
  public function __construct(
    private array $rawSuggestions,
  ) {}

  public function toDto(): SuggestionCollectionDto
  {
    $suggestionDtos = array_map(
      static fn (array $raw) => (new SuggestionTransformer($raw))->toDto(),
      $this->rawSuggestions,
    );

    return new SuggestionCollectionDto(
      title: 'How to fix this',
      items: $suggestionDtos,
    );
  }
}
