<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\StackFrameDto;

/**
 * StackFrameTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class StackFrameTransformer
{
  /**
   * @param array<string, mixed> $rawFrame
   * @param bool                 $isOpen
   * @param string               $frameId
   * @param array{
   * enabled: bool,
   * defaultEditor: string,
   * editors: array<string, array{name: string, url: string}>,
   * pathMapping: array{from: string, to: string}
   * } $editorConfig
   */
  public function __construct(
    private array $rawFrame,
    private bool $isOpen,
    private string $frameId,
    private array $editorConfig,
  ) {}

  public function toDto(): StackFrameDto
  {
    $codeLines = [];
    $rawCodeLines = explode("\n", $this->rawFrame['code'] ?? '');
    $startLine = (int)($this->rawFrame['startLine'] ?? 1);

    foreach ($rawCodeLines as $lineIndex => $rawLine) {
      $lineNumber = $startLine + $lineIndex;
      $editorUrl = $this->generateEditorUrl($this->rawFrame['fullPath'], $lineNumber);
      $isError = $lineNumber === (int)$this->rawFrame['line'];

      $codeLines[] = (new CodeLineTransformer($rawLine, $lineNumber, $isError, $editorUrl))->toDto();
    }

    return new StackFrameDto(
      id: $this->frameId,
      file: (string)($this->rawFrame['file'] ?? 'unknown file'),
      line: (int)($this->rawFrame['line'] ?? 0),
      function: (string)($this->rawFrame['function'] ?? ''),
      open: $this->isOpen,
      codeLines: $codeLines,
    );
  }

  /**
   * Generate editor URL for opening file at specific line.
   *
   * @param string $file
   * @param int    $line
   *
   * @return string
   */
  private function generateEditorUrl(string $file, int $line): string
  {
    // Use the property instead of calling the method again.
    $config = $this->editorConfig;

    if (!$config['enabled']) {
      return '#';
    }

    $hostPath = $this->mapPath($file, $config['pathMapping']);
    $editorKey = $config['defaultEditor'];

    if (!isset($config['editors'][$editorKey])) {
      return '#';
    }

    $editor = $config['editors'][$editorKey];

    // Cast $line to string to satisfy str_replace's expected types.
    return str_replace(
      ['{file}', '{line}'],
      [urlencode($hostPath), (string)$line],
      $editor['url'],
    );
  }

  /**
   * Map container path to host path using configuration.
   *
   * @param string                          $path    File path to map
   * @param array{from: string, to: string} $mapping Mapping configuration with 'from' and 'to' keys
   *
   * @return string Mapped path or original path if no mapping configured
   */
  private function mapPath(string $path, array $mapping): string
  {
    // If mapping is not configured (empty strings) - return path as-is
    if (empty($mapping['from']) || empty($mapping['to'])) {
      return $path;
    }

    // Otherwise perform Docker container to host path mapping
    if (str_starts_with($path, $mapping['from'])) {
      return str_replace($mapping['from'], $mapping['to'], $path);
    }

    return $path;
  }
}
