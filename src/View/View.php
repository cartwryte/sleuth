<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\View;

use Cartwryte\Sleuth\Renderer\HtmlRenderer;
use Cartwryte\Sleuth\Renderer\JsonRenderer;
use JsonException;
use Opencart\System\Engine\Config;
use Opencart\System\Library\Log;
use Throwable;

/**
 * Error View
 *
 * Renders error pages in HTML or JSON format for debugging and production use.
 * Handles both Twig-based templating and fallback rendering.
 *
 * @package Cartwryte\Sleuth\View
 */
final class View
{
  // Renderers are now nullable and will be created on-demand.
  private ?HtmlRenderer $htmlRenderer = null;
  private ?JsonRenderer $jsonRenderer = null;

  /**
   * Constructor
   *
   * Stores dependencies for later use by the renderers.
   *
   * @param Config $config
   * @param Log    $log
   */
  public function __construct(
    private Config $config,
    private Log $log,
  ) {}

  /**
   * Render error page
   *
   * @param Throwable $e
   * @param string    $responseType Response format (html|json)
   *
   * @throws JsonException
   *
   * @return void
   */
  public function render(Throwable $e, string $responseType = 'html'): void
  {
    match ($responseType) {
      'json' => $this->getJsonRenderer()->render($e),
      default => $this->getHtmlRenderer()->render($e),
    };
  }

  /**
   * Lazily creates and returns the HtmlRenderer instance.
   *
   * @return HtmlRenderer
   */
  private function getHtmlRenderer(): HtmlRenderer
  {
    if (is_null($this->htmlRenderer)) {
      $this->htmlRenderer = new HtmlRenderer($this->config, $this->log);
    }

    return $this->htmlRenderer;
  }

  /**
   * Lazily creates and returns the JsonRenderer instance.
   *
   * @return JsonRenderer
   */
  private function getJsonRenderer(): JsonRenderer
  {
    if (is_null($this->jsonRenderer)) {
      $this->jsonRenderer = new JsonRenderer();
    }

    return $this->jsonRenderer;
  }
}
