<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\View;

use Cartwryte\Sleuth\Analyzer\SolutionAnalyzer;
use Cartwryte\Sleuth\Transformer\TechInfoTransformer;
use Error;
use ErrorException;
use Exception;
use Throwable;

/**
 * Error View Model
 *
 * Prepares exception data for View rendering with proper error location detection
 * and code snippet extraction for debugging purposes.
 *
 * @package Cartwryte\Sleuth\View
 */
final class ViewModel
{
  private const MAX_FRAMES = 15;
  private const CONTEXT_LINES = 7;
  private const SYSTEM_PATHS = ['/system/engine/', '/system/library/'];

  /**
   * Build complete error data array for View rendering
   *
   * @param Throwable $e The exception to process
   *
   * @return array<string, mixed> Complete error data for View
   */
  public function build(Throwable $e): array
  {
    return [
      'e' => $e,
      'title' => $this->buildTitle($e),
      'headingTitle' => $this->buildHeadingTitle($e),
      'message' => htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'),
      'file' => $e->getFile(),
      'line' => $e->getLine(),
      'exceptions' => $this->buildExceptionChain($e),
      'frames' => $this->buildFrames($e),
      'techInfo' => (new TechInfoTransformer())->toArray(),
      'suggestions' => $this->buildSuggestions($e),
    ];
  }

  /**
   * Build full page title combining heading and message
   *
   * @param Throwable $e The exception
   *
   * @return string Complete page title
   */
  private function buildTitle(Throwable $e): string
  {
    return $this->buildHeadingTitle($e) . ': ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
  }

  /**
   * Build main heading showing exception class, file and line
   *
   * @param Throwable $e The exception
   *
   * @return string Formatted heading
   */
  private function buildHeadingTitle(Throwable $e): string
  {
    return htmlspecialchars(get_class($e), ENT_QUOTES, 'UTF-8') . ' in ' . $e->getFile() . ' on line ' . $e->getLine();
  }

  /**
   * Build complete exception chain for nested exceptions
   *
   * @param Throwable $e The root exception
   *
   * @return array<int, array<string, mixed>> Array of exception data
   */
  private function buildExceptionChain(Throwable $e): array
  {
    $exceptions = [];
    $current = $e;

    while (!is_null($current)) {
      $exceptions[] = [
        'class' => get_class($current),
        'message' => htmlspecialchars($current->getMessage(), ENT_QUOTES, 'UTF-8'),
        'file' => $current->getFile(),
        'line' => $current->getLine(),
      ];

      $current = $current->getPrevious();
    }

    return $exceptions;
  }

  /**
   * Extract stack trace frames with code snippets
   *
   * @param Throwable $e The exception
   *
   * @return array<int, array<string, mixed>> Array of frame data with code snippets
   */
  private function buildFrames(Throwable $e): array
  {
    $frames = [];
    $errorLocation = $this->determineErrorLocation($e);

    // Add main error frame first
    $frames[] = $this->buildCodeFrame($errorLocation['file'], $errorLocation['line'], 'ERROR SOURCE');

    // Add stack trace frames
    $traces = $e->getTrace();

    foreach (array_slice($traces, 0, self::MAX_FRAMES) as $trace) {
      if (!isset($trace['file'], $trace['line']) || !is_file($trace['file'])) {
        continue;
      }

      // Skip duplicate with main error frame
      if ($trace['file'] === $errorLocation['file'] && $trace['line'] === $errorLocation['line']) {
        continue;
      }

      $function = ($trace['class'] ?? '') . ($trace['type'] ?? '') . $trace['function'];

      $frames[] = $this->buildCodeFrame($trace['file'], $trace['line'], $function);
    }

    return $frames;
  }

  /**
   * Build a single code frame with syntax highlighting
   *
   * @param string $file     Full file path
   * @param int    $line     Line number
   * @param string $function Function/method name for this frame
   *
   * @return array<string, mixed> Frame data with highlighted code
   */
  private function buildCodeFrame(string $file, int $line, string $function): array
  {
    $lines = @file($file);

    if ($lines === false) {
      return [
        'file' => htmlspecialchars($this->relativePath($file), ENT_QUOTES, 'UTF-8'),
        'line' => $line,
        'function' => htmlspecialchars($function, ENT_QUOTES, 'UTF-8'),
        'code' => 'File not readable',
        'startLine' => $line,
        'fullPath' => $file,
      ];
    }

    $start = max(1, $line - self::CONTEXT_LINES);
    $end = min(count($lines), $line + self::CONTEXT_LINES);

    $raw_code = implode(
      "\n",
      array_map('rtrim', array_slice($lines, $start - 1, $end - $start + 1)),
    );

    return [
      'file' => htmlspecialchars($this->relativePath($file), ENT_QUOTES, 'UTF-8'),
      'line' => $line,
      'function' => htmlspecialchars($function, ENT_QUOTES, 'UTF-8'),
      'code' => $raw_code,
      'startLine' => $start,
      'fullPath' => $file,
    ];
  }

  /**
   * Determine the actual location where error should be displayed
   *
   * Different logic for different exception types:
   * - Error/ErrorException: Show actual error location
   * - User exceptions: Show where exception was thrown
   * - System exceptions: Show where user code called system code
   *
   * @param Throwable $e The exception
   *
   * @return array{file: string, line: int} Error location
   */
  private function determineErrorLocation(Throwable $e): array
  {
    // For PHP errors (Error/ErrorException) - always use exception location
    if ($e instanceof Error || $e instanceof ErrorException) {
      return [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ];
    }

    // For other throwables, check if thrown in user or system code
    $traces = $e->getTrace();

    // If thrown in user code - show where it was thrown
    if ($this->isUserThrownException($e)) {
      return [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
      ];
    }

    // If thrown in system code - show where user code called it
    if (isset($traces[0]['file'], $traces[0]['line'])) {
      return [
        'file' => $traces[0]['file'],
        'line' => $traces[0]['line'],
      ];
    }

    // Fallback to exception location
    return [
      'file' => $e->getFile(),
      'line' => $e->getLine(),
    ];
  }

  /**
   * Check if exception was thrown directly by user code
   *
   * @param Throwable $e The exception to check
   *
   * @return bool True if thrown in user code, false if in system code
   */
  private function isUserThrownException(Throwable $e): bool
  {
    $exceptionFile = $e->getFile();

    foreach (self::SYSTEM_PATHS as $path) {
      if (str_contains($exceptionFile, $path)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Convert absolute file path to relative path from project root
   *
   * @param string $file Absolute file path
   *
   * @return string Relative file path
   */
  private function relativePath(string $file): string
  {
    return str_replace(dirname(DIR_SYSTEM) . '/', '', $file);
  }

  /**
   * Build suggestions for resolving the error
   *
   * @param Throwable $e The exception to analyze
   *
   * @return array<int, array{icon: string, text: string}> Array of suggestions
   */
  private function buildSuggestions(Throwable $e): array
  {
    return (new SolutionAnalyzer())->analyze($e);
  }
}
