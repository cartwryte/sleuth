<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Renderer;

use Throwable;

/**
 * Error Renderer Interface
 *
 * Defines contract for error page renderers
 *
 * @package Cartwryte\Sleuth\Renderer
 */
interface RendererInterface
{
  /**
   * Render error data
   *
   * @param Throwable $e
   *
   * @return void
   */
  public function render(Throwable $e): void;
}
