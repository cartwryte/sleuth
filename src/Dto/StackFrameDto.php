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
 * StackFrameDto
 *
 * @package Cartwryte\Sleuth\Dto
 *
 * @author  Anton Semenov
 */
final class StackFrameDto
{
  /**
   * @param string                  $id
   * @param string                  $file
   * @param int                     $line
   * @param string                  $function
   * @param bool                    $open
   * @param array<int, CodeLineDto> $codeLines This specifies it's an array of CodeLineDto objects
   */
  public function __construct(
    public string $id,
    public string $file,
    public int $line,
    public string $function,
    public bool $open,
    public array $codeLines,
  ) {}
}
