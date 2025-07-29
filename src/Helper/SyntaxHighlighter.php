<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Helper;

/**
 * PHP Syntax Highlighter
 *
 * Highlights PHP code using built-in tokenizer
 *
 * @package Cartwryte\Sleuth\Helper
 */
final class SyntaxHighlighter
{
  /**
   * Tokenize PHP code for web components.
   *
   * @param string $code PHP code snippet
   *
   * @return array<int, array{type: string, content: string}>
   */
  public static function tokenize(string $code): array
  {
    $source = '<?php ' . $code;
    $tokens = token_get_all($source);
    $result = [];

    foreach ($tokens as $token) {
      if (is_array($token)) {
        [$id, $text] = $token;

        // Skip synthetic open tag
        if ($id === T_OPEN_TAG) {
          continue;
        }

        $result[] = [
          'type' => self::getTokenType($id),
          'content' => $text,
        ];
      } else {
        // Single-char token (punctuation, operators)
        $result[] = [
          'type' => 'punctuation',
          'content' => $token,
        ];
      }
    }

    return $result;
  }

  /**
   * Get semantic token type for web components.
   *
   * @param int $tokenId
   *
   * @return string
   */
  private static function getTokenType(int $tokenId): string
  {
    $tMatch = defined('T_MATCH') ? T_MATCH : 0;
    $tEnum = defined('T_ENUM') ? T_ENUM : 0;
    $tTrue = defined('T_TRUE') ? T_TRUE : 0;
    $tFalse = defined('T_FALSE') ? T_FALSE : 0;
    $tNull = defined('T_NULL') ? T_NULL : 0;

    return match ($tokenId) {
      T_COMMENT, T_DOC_COMMENT => 'comment',
      T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE => 'string',
      T_STRING, T_NAME_QUALIFIED, T_NAME_RELATIVE, T_NAME_FULLY_QUALIFIED => 'string-literal',
      T_VARIABLE => 'variable',
      T_LNUMBER, T_DNUMBER => 'number',
      $tTrue, $tFalse, $tNull => 'literal',
      T_FUNCTION, T_CLASS, T_TRAIT, T_INTERFACE, T_NAMESPACE, T_USE, T_EXTENDS,
      T_IMPLEMENTS, T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT,
      T_FINAL, T_CONST, T_NEW, $tEnum => 'keyword',
      T_IF, T_ELSE, T_ELSEIF, T_WHILE, T_FOR, T_FOREACH, T_SWITCH, T_CASE,
      T_DEFAULT, T_BREAK, T_CONTINUE, T_RETURN, T_TRY, T_CATCH, T_FINALLY,
      T_THROW, $tMatch => 'control',
      T_ECHO, T_PRINT, T_EXIT, T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE => 'builtin',
      T_WHITESPACE => 'whitespace',
      default => 'default',
    };
  }
}
