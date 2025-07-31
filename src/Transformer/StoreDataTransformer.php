<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Transformer;

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
    if (!empty($this->rawData['suggestions'])) {
      $storeData['LuminarySuggestions:suggestions-main'] = (new SuggestionCollectionTransformer($this->rawData['suggestions']))->toDto();
    }

    // Exceptions chain
    if (!empty($this->rawData['exceptions'])) {
      $storeData['LuminaryExceptionsChain:exceptions-main'] = (new ExceptionCollectionTransformer($this->rawData['exceptions']))->toDto();
    }

    // Tech info
    if (!empty($this->rawData['techInfo'])) {
      $storeData['LuminaryTechInfo:tech-info-main'] = $this->rawData['techInfo'];
    }

    // Stack trace and all its children are now handled by one transformer
    if (!empty($this->rawData['frames'])) {
      $storeData['LuminaryStackTrace:stack-trace-main'] = (new StackTraceTransformer(
        $this->rawData['frames'],
        $this->editorConfig,
      ))->toDto();
    }

    return $storeData;
  }
}
