<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\CodeLineDto;
use Cartwryte\Sleuth\Dto\TokenDto;
use Cartwryte\Sleuth\Helper\SyntaxHighlighter;

/**
 * CodeLineTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class CodeLineTransformer
{
  public function __construct(
    private string $rawLine,
    private int $lineNumber,
    private bool $isErrorLine,
    private string $editorUrl,
  ) {}

  public function toDto(): CodeLineDto
  {
    $rawTokens = SyntaxHighlighter::tokenize($this->rawLine);

    $tokenDtos = array_map(
      static fn (array $token): TokenDto => new TokenDto(
        type: $token['type'],
        content: $token['content'],
      ),
      $rawTokens,
    );

    return new CodeLineDto(
      number: $this->lineNumber,
      tokens: $tokenDtos,
      isError: $this->isErrorLine,
      editorUrl: $this->editorUrl,
    );
  }
}
