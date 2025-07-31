<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

use Cartwryte\Sleuth\Helper\ArrayHelper;

/**
 * StoreDataTransformer
 *
 * @package Cartwryte\Sleuth\Transformer
 *
 * @author  Anton Semenov
 */
final class StoreDataTransformer
{
  /**
   * @param array<string, mixed> $rawData from ViewModel
   * @param array{
   * enabled: bool,
   * defaultEditor: string,
   * editors: array<string, array{name: string, url: string}>,
   * pathMapping: array{from: string, to: string}
   * } $editorConfig Configuration for the editor links
   */
  public function __construct(
    private array $rawData,
    private array $editorConfig,
  ) {}

  /**
   * @return array<string, object> the final data structure for the JS store
   */
  public function transform(): array
  {
    $storeData = [];

    // Layout
    $storeData['LuminaryLayout:layout-main'] = (new LayoutTransformer($this->rawData))->toDto();

    // Header
    $storeData['LuminaryHeader:header-main'] = (new HeaderTransformer($this->rawData))->toDto();

    // Suggestions
    $suggestions = $this->getSuggestions();

    if (!empty($suggestions)) {
      $storeData['LuminarySuggestions:suggestions-main'] = (new SuggestionCollectionTransformer($suggestions))->toDto();
    }

    // Exceptions chain
    $exceptions = $this->getExceptions();

    if (!empty($exceptions)) {
      $storeData['LuminaryExceptionsChain:exceptions-main'] = (new ExceptionCollectionTransformer($exceptions))->toDto();
    }

    // Tech info
    $techInfo = ArrayHelper::getArray($this->rawData, 'techInfo', []);

    if (!empty($techInfo)) {
      $storeData['LuminaryTechInfo:tech-info-main'] = (object)$techInfo;
    }

    // Stack trace and all its children are now handled by one transformer
    $frames = $this->getFrames();

    if (!empty($frames)) {
      $storeData['LuminaryStackTrace:stack-trace-main'] = (new StackTraceTransformer(
        $frames,
        $this->editorConfig,
      ))->toDto();
    }

    return $storeData;
  }

  /**
   * @return array<int, array{icon: string, text: string}>
   */
  private function getSuggestions(): array
  {
    $suggestions = $this->rawData['suggestions'] ?? [];

    if (!is_array($suggestions)) {
      return [];
    }

    $result = [];

    foreach ($suggestions as $index => $suggestion) {
      if (!is_array($suggestion)
          || !isset($suggestion['icon'], $suggestion['text'])
          || !is_string($suggestion['icon'])
          || !is_string($suggestion['text'])) {
        return [];
      }

      if (is_int($index)) {
        $result[$index] = [
          'icon' => $suggestion['icon'],
          'text' => $suggestion['text'],
        ];
      }
    }

    return $result;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function getExceptions(): array
  {
    $exceptions = $this->rawData['exceptions'] ?? [];

    if (!is_array($exceptions)) {
      return [];
    }

    $result = [];

    foreach ($exceptions as $exception) {
      if (!is_array($exception)) {
        return [];
      }

      $result[] = array_filter(
        $exception,
        static fn ($key): bool => is_string($key),
        ARRAY_FILTER_USE_KEY,
      );
    }

    return $result;
  }

  /**
   * @return array<int, array<string, mixed>>
   */
  private function getFrames(): array
  {
    $frames = $this->rawData['frames'] ?? [];

    if (!is_array($frames)) {
      return [];
    }

    $result = [];

    foreach ($frames as $frame) {
      if (!is_array($frame)) {
        return [];
      }

      $result[] = array_filter(
        $frame,
        static fn ($key): bool => is_string($key),
        ARRAY_FILTER_USE_KEY,
      );
    }

    return $result;
  }
}
