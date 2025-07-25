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
   * Highlight PHP syntax using built-in tokenizer.
   *
   * @param string $code PHP code snippet (a single line or fragment)
   *
   * @return string HTML with <span> wrappers around tokens
   */
  public static function highlight(string $code): string
  {
    // Prepend an open-tag so tokenizer treats it as PHP
    $source = '<?php ' . $code;
    $tokens = token_get_all($source);
    $highlightedHtml = '';

    // Optional tokens (may not exist in all PHP versions)
    $tMatch = defined('T_MATCH') ? T_MATCH : 0;
    $tEnum = defined('T_ENUM') ? T_ENUM : 0;
    $tTrue = defined('T_TRUE') ? T_TRUE : 0;
    $tFalse = defined('T_FALSE') ? T_FALSE : 0;
    $tNull = defined('T_NULL') ? T_NULL : 0;

    foreach ($tokens as $token) {
      if (is_array($token)) {
        [$id, $text] = $token;

        // Skip the synthetic open-tag we added
        if ($id === T_OPEN_TAG) {
          continue;
        }

        // Escape for safe HTML
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE);

        // Decide CSS class by token ID
        $class = match ($id) {
          // PHP tags
          T_CLOSE_TAG => 'code-line__token--tag',

          // Comments
          T_COMMENT, T_DOC_COMMENT => 'code-line__token--comment',

          // String literals
          T_CONSTANT_ENCAPSED_STRING,
          T_ENCAPSED_AND_WHITESPACE,
          T_START_HEREDOC,
          T_END_HEREDOC => 'code-line__token--string',

          // Identifiers (namespaces, class/function names…)
          T_STRING,
          T_NAME_QUALIFIED,
          T_NAME_RELATIVE,
          T_NAME_FULLY_QUALIFIED => 'code-line__token--string-literal',

          // Variables
          T_VARIABLE => 'code-line__token--variable',

          // Numbers
          T_LNUMBER, T_DNUMBER => 'code-line__token--number',

          // Booleans & null
          $tTrue, $tFalse, $tNull => 'code-line__token--literal',

          // Declaration keywords
          T_FUNCTION,
          T_CLASS,
          T_TRAIT,
          T_INTERFACE,
          T_NAMESPACE,
          T_USE,
          T_EXTENDS,
          T_IMPLEMENTS,
          T_PUBLIC,
          T_PROTECTED,
          T_PRIVATE,
          T_STATIC,
          T_ABSTRACT,
          T_FINAL,
          T_CONST,
          T_NEW,
          $tEnum => 'code-line__token--keyword',

          // Control structures
          T_IF,
          T_ELSE,
          T_ELSEIF,
          T_WHILE,
          T_FOR,
          T_FOREACH,
          T_SWITCH,
          T_CASE,
          T_DEFAULT,
          T_BREAK,
          T_CONTINUE,
          T_RETURN,
          T_TRY,
          T_CATCH,
          T_FINALLY,
          T_THROW,
          $tMatch => 'code-line__token--control',

          // Built-ins & includes
          T_ECHO,
          T_PRINT,
          T_EXIT,
          T_INCLUDE,
          T_INCLUDE_ONCE,
          T_REQUIRE,
          T_REQUIRE_ONCE => 'code-line__token--builtin',

          // Everything else: no class
          default => '',
        };

        // Wrap in <span> if we have a class, otherwise append plain text
        $highlightedHtml .= $class
            ? "<span class=\"$class\">$escaped</span>"
            : $escaped;
      } else {
        // Single‐char token (punctuation, operators…)
        $highlightedHtml .= htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE);
      }
    }

    return $highlightedHtml;
  }
}
