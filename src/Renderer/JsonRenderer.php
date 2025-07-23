<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Renderer;

use Cartwryte\Sleuth\View\ViewModel;
use JsonException;
use Throwable;

/**
 * JsonRenderer
 *
 * @package Cartwryte\Sleuth\Renderer
 */
final class JsonRenderer implements RendererInterface
{
  private ViewModel $viewModel;

  public function __construct()
  {
    $this->viewModel = new ViewModel();
  }

  /**
   * Render JSON error response
   *
   * @param Throwable $e
   *
   * @throws JsonException
   *
   * @return void
   */
  public function render(Throwable $e): void
  {
    $data = $this->viewModel->build($e);

    $trace = array_map(static fn (array $frame): array => [
      'file' => $frame['file'],
      'line' => $frame['line'],
      'function' => $frame['function'],
    ], $data['frames']);

    $suggestions = array_map(static fn (array $suggestion): string => $suggestion['text'], $data['suggestions']);

    header('Content-Type: application/json');

    echo json_encode([
      'error' => true,
      'message' => $data['message'],
      'type' => $data['heading_title'],
      'file' => $data['file'],
      'line' => $data['line'],
      'exceptions' => $data['exceptions'],
      'trace' => $trace,
      'suggestions' => $suggestions,
      'tech_info' => $data['tech_info'],
    ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
  }
}
