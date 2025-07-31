<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\SuggestionDto;

/**
 * SuggestionTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class SuggestionTransformer
{
  /**
   * @param array{icon: string, text: string} $rawSuggestion
   */
  public function __construct(
    private array $rawSuggestion,
  ) {}

  public function toDto(): SuggestionDto
  {
    return new SuggestionDto(
      icon: $this->rawSuggestion['icon'],
      text: $this->rawSuggestion['text'],
    );
  }
}
