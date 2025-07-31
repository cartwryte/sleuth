<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Dto\StackTraceDto;

/**
 * StackTraceTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class StackTraceTransformer
{
  /**
   * @param array<int, array<string, mixed>> $rawFrames
   * @param array{
   * enabled: bool,
   * defaultEditor: string,
   * editors: array<string, array{name: string, url: string}>,
   * pathMapping: array{from: string, to: string}
   * } $editorConfig
   */
  public function __construct(
    private array $rawFrames,
    private array $editorConfig,
  ) {}

  public function toDto(): StackTraceDto
  {
    $frameDtos = [];

    foreach ($this->rawFrames as $index => $frame) {
      $frameId = "frame-{$index}";
      $isOpen = ($index === 0);
      $frameDtos[] = (new StackFrameTransformer($frame, $isOpen, $frameId, $this->editorConfig))->toDto();
    }

    return new StackTraceDto(
      title: 'Stack Trace',
      frames: $frameDtos,
    );
  }
}
