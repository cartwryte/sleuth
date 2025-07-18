<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Helper;

use Generator;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * File helper methods.
 *
 * @package Cartwryte\Sleuth\Helper
 *
 * @author  Anton Semenov
 */
final class FileHelper
{
  /**
   * Private constructor to prevent instantiation.
   */
  private function __construct() {}

  /**
   * Yield non-empty lines from a file, without trailing EOL.
   *
   * @param string $filePath
   *
   * @throws RuntimeException if file cannot be opened
   *
   * @return Generator<string>
   */
  public static function readLines(string $filePath): Generator
  {
    try {
      $file = new SplFileObject($filePath, 'rb');
    } catch (Throwable $e) {
      throw new RuntimeException("Cannot open file for reading: $filePath", 0, $e);
    }

    $file->setFlags(SplFileObject::DROP_NEW_LINE | SplFileObject::SKIP_EMPTY);

    foreach ($file as $line) {
      yield $line;
    }
  }

  /**
   * Comments out a callback block started by $fnName(
   * (e.g., set_error_handler or set_exception_handler)
   * until the closing ');', counting the depth of curly braces.
   *
   * @param string[] $lines  Source lines
   * @param string   $fnName Callback function name
   *
   * @return array{0:string[],1:bool} [new lines, whether there were changes]
   */
  public static function commentOutBlock(iterable $lines, string $fnName): array
  {
    $out = [];
    $inBlock = false;
    $depth = 0;
    $changed = false;
    $pattern = '/^\s*' . preg_quote($fnName, '/') . '\s*\(/';

    foreach ($lines as $line) {
      // convert to string just in case, so preg_match doesn't fail
      $line = (string)$line;

      if (!$inBlock) {
        if (preg_match($pattern, $line)) {
          $inBlock = true;
          $depth = self::calculateBraceDepth($line);
          $out[] = '// ' . $line;
          $changed = true;
        } else {
          $out[] = $line;
        }
      } else {
        $depth += self::calculateBraceDepth($line);
        $out[] = '// ' . $line;
        $changed = true;

        if ($depth <= 0 && str_contains($line, ');')) {
          $inBlock = false;
        }
      }
    }

    return [$out, $changed];
  }

  /**
   * Comments out any code block starting at a line matching $pattern
   * and ending when the brace-depth returns to zero.
   *
   * Can be used for class methods:
   *   FileHelper::commentOutBraceBlock($lines, '/public function errorHandler/');
   *
   * @param string[] $lines   Source lines
   * @param string   $pattern Regex to find the beginning of the block
   *
   * @return array{0:string[],1:bool} [new lines, whether there were changes]
   */
  public static function commentOutBraceBlock(array $lines, string $pattern): array
  {
    $out = [];
    $inBlock = false;
    $depth = 0;
    $changed = false;

    foreach ($lines as $line) {
      if (!$inBlock) {
        if (preg_match($pattern, $line)) {
          $inBlock = true;
          $depth = self::calculateBraceDepth($line);
          $out[] = '// ' . $line;
          $changed = true;
        } else {
          $out[] = $line;
        }
      } else {
        $depth += self::calculateBraceDepth($line);
        $out[] = '// ' . $line;
        $changed = true;

        // exit when all { } are closed
        if ($depth <= 0) {
          $inBlock = false;
        }
      }
    }

    return [$out, $changed];
  }

  /**
   * Comments out a block of lines relative to the first match of $pattern.
   *
   * @param string[] $lines        Array of lines
   * @param string   $pattern      Regex or substring to match
   * @param int      $linesAfter   How many lines after the matching line to comment.
   *                               Starts counting from the match line if $includeMatch=true,
   *                               otherwise from the next line.
   * @param bool     $includeMatch whether to comment the matching line itself
   *
   * @return array<string> Modified lines
   *
   * @example
   *   // disable line with pattern and next 9 lines:
   *   FileHelper::commentLinesAfterMatch($lines, '/pattern/', 10, true);
   */
  public static function commentLinesAfterMatch(
    array $lines,
    string $pattern,
    int $linesAfter,
    bool $includeMatch = true,
  ): array {
    $count = count($lines);

    foreach ($lines as $i => $line) {
      if (preg_match($pattern, $line)) {
        $start = $includeMatch ? $i : $i + 1;
        $end = min($count - 1, $i + $linesAfter - ($includeMatch ? 1 : 0));

        for ($j = $start; $j <= $end; $j++) {
          if (!str_starts_with($lines[$j], '//')) {
            $lines[$j] = '// ' . $lines[$j];
          }
        }
        break;
      }
    }

    return $lines;
  }

  /**
   * Comments out a block of lines preceding the first match of $pattern.
   *
   * @param string[] $lines        Array of lines
   * @param string   $pattern      Regex or substring to match
   * @param int      $linesBefore  How many lines before the matching line to comment
   *                               (not including the match line)
   * @param bool     $includeMatch whether to comment the matching line itself
   *
   * @return array<string> Modified lines
   *
   * @example
   *   // disable 4 lines before pattern, but not the pattern itself:
   *   FileHelper::commentLinesBeforeMatch($lines, '/pattern/', 4, false);
   */
  public static function commentLinesBeforeMatch(
    array $lines,
    string $pattern,
    int $linesBefore,
    bool $includeMatch = false,
  ): array {
    foreach ($lines as $i => $line) {
      if (preg_match($pattern, $line)) {
        $start = $includeMatch ? $i : $i - 1;
        $end = max(0, $i - $linesBefore);

        for ($j = $start; $j >= $end; $j--) {
          if (!str_starts_with($lines[$j], '//')) {
            $lines[$j] = '// ' . $lines[$j];
          }
        }

        if ($includeMatch && !str_starts_with($lines[$i], '//')) {
          $lines[$i] = '// ' . $lines[$i];
        }
        break;
      }
    }

    return $lines;
  }

  /**
   * Read non-empty lines as array.
   *
   * @param string $filePath
   *
   * @throws RuntimeException if file cannot be opened
   *
   * @return string[]
   */
  public static function readLinesAsArray(string $filePath): array
  {
    return iterator_to_array(self::readLines($filePath), false);
  }

  /**
   * Calculate the depth change of curly braces in a line.
   *
   * @param string $line Line to analyze
   *
   * @return int Positive if more opening braces, negative if more closing braces
   */
  private static function calculateBraceDepth(string $line): int
  {
    return substr_count($line, '{') - substr_count($line, '}');
  }
}
