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
 * CodeLineDto
 *
 * @package Cartwryte\Sleuth\Dto
 *
 * @author  Anton Semenov
 */
final class CodeLineDto
{
  /**
   * @param int             $number
   * @param array<TokenDto> $tokens
   * @param bool            $isError
   * @param string          $editorUrl
   */
  public function __construct(
    public int $number,
    public array $tokens,
    public bool $isError,
    public string $editorUrl,
  ) {}
}
