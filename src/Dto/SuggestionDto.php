<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Dto;

/**
 * SuggestionDto
 *
 * @package Cartwryte\Sleuth\Dto
 *
 * @author  Anton Semenov
 */
final class SuggestionDto
{
  public function __construct(
    public string $icon,
    public string $text,
  ) {}
}
