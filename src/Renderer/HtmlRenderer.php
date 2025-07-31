<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Renderer;

use Cartwryte\Sleuth\Formatter\LogFormatter;
use Cartwryte\Sleuth\Frontend\ViteHelper;
use Cartwryte\Sleuth\Helper\PathHelper;
use Cartwryte\Sleuth\Transformer\StoreDataTransformer;
use Cartwryte\Sleuth\View\ViewModel;
use JsonException;
use Opencart\System\Engine\Config;
use Opencart\System\Library\Log;
use RuntimeException;
use Throwable;

/**
 * HtmlRenderer
 *
 * Renders a self-contained HTML error page using simple .tpl templates.
 *
 * @package Cartwryte\Sleuth\Renderer
 */
final class HtmlRenderer implements RendererInterface
{
  private ViewModel $viewModel;
  /**
   * @var array{
   * enabled: bool,
   * defaultEditor: string,
   * editors: array<string, array{name: string, url: string}>,
   * pathMapping: array{from: string, to: string}
   * }
   */
  private array $editorConfig;
  private string $templatePath;

  /**
   * Constructor.
   *
   * @param Config $config
   * @param Log    $log
   */
  public function __construct(
    private Config $config,
    private Log $log,
  ) {
    $this->viewModel = new ViewModel();
    $this->editorConfig = $this->getEditorConfig();

    // Define paths relative to this file to make the component self-contained.
    $basePath = dirname(__DIR__) . '/Frontend';
    $this->templatePath = rtrim($basePath, '/') . '/template/';
  }

  /**
   * Render HTML error page for browser requests.
   *
   * Attempts to use the .tpl template file. If not found, it uses a built-in fallback.
   *
   * @param Throwable $e
   *
   * @throws JsonException
   */
  public function render(Throwable $e): void
  {
    $rawData = $this->viewModel->build($e);

    $storeData = (new StoreDataTransformer($rawData, $this->editorConfig))->transform();

    $viewData = [
      'titleEscaped' => htmlspecialchars($rawData['title'] ?? 'Error'),
      'viteAssets' => $this->getViteHelper()(['src/Frontend/js/luminary.js']),
      'storeData' => $storeData,
    ];

    // The main file is the single entry point for rendering.
    $indexFile = $this->templatePath . 'index.tpl';

    if (is_file($indexFile)) {
      $this->renderTemplate($indexFile, $viewData);
    } else {
      $this->logError(new RuntimeException("Layout template not found: $indexFile"));
      $this->renderFallback($viewData);
    }
  }

  /**
   * Renders a .tpl template file with the given data.
   *
   * @param string               $file The path to the .tpl template file.
   * @param array<string, mixed> $data the data to be extracted into variables for the template
   */
  private function renderTemplate(string $file, array $data): void
  {
    // Make variables available to the template file.
    extract($data, EXTR_SKIP);

    ob_start();

    // The required file will have access to all extracted variables.
    require($file);
    echo ob_get_clean();
  }

  /**
   * @return ViteHelper
   */
  private function getViteHelper(): ViteHelper
  {
    $root = PathHelper::getRoot();

    return new ViteHelper(
      buildPath: $root . '/catalog/view/sleuth',
      buildUrl: '/catalog/view/sleuth',
      hotFilePath: dirname(__DIR__) . '/Frontend/dist/hot',
    );
  }

  /**
   * Get editor configuration.
   *
   * @return array{
   * enabled: bool,
   * defaultEditor: string,
   * editors: array<string, array{name: string, url: string}>,
   * pathMapping: array{from: string, to: string}
   * }
   */
  private function getEditorConfig(): array
  {
    $pathTo = $this->config->get('dev_editor_path_to');

    $enabled = $this->config->get('dev_editor_enabled') && !empty($pathTo);

    return [
      'enabled' => $enabled,
      'defaultEditor' => $this->config->get('dev_editor_default') ?? 'phpstorm',
      'editors' => [
        'vscode' => [
          'name' => 'Visual Studio Code',
          'url' => 'vscode://file/{file}:{line}',
        ],
        'cursor' => [
          'name' => 'Cursor',
          'url' => 'cursor://file/{file}:{line}',
        ],
        'phpstorm' => [
          'name' => 'PHPStorm',
          'url' => 'phpstorm://open?file={file}&line={line}',
        ],
        'webstorm' => [
          'name' => 'WebStorm',
          'url' => 'webstorm://open?file={file}&line={line}',
        ],
        'sublimetext' => [
          'name' => 'Sublime Text',
          'url' => 'subl://open?url=file://{file}&line={line}',
        ],
        'zed' => [
          'name' => 'Zed',
          'url' => 'zed://file/{file}:{line}',
        ],
        'windsurf' => [
          'name' => 'Windsurf',
          'url' => 'windsurf://file/{file}:{line}',
        ],
      ],
      'pathMapping' => [
        'from' => $this->config->get('dev_editor_path_from') ?? '/var/www/',
        'to' => $pathTo ?? '',
      ],
    ];
  }

  /**
   * Log rendering errors.
   *
   * Logs template rendering failures or other internal errors.
   *
   * @param Throwable $e the exception to log
   */
  private function logError(Throwable $e): void
  {
    if ($this->config->get('config_error_log')) {
      $message = LogFormatter::simple($e);
      $this->log->write($message);
    }
  }

  /**
   * Render fallback when the main template is not available.
   *
   * @param array<string, mixed> $data
   */
  private function renderFallback(array $data): void
  {
    $title = $data['title'] ?? ($data['headingTitle'] . ': ' . $data['message']);

    echo '<!DOCTYPE html>';
    echo '<html><head>';
    echo '<title>' . htmlspecialchars($title) . '</title>';
    echo '<style>
            :root {
                --bg: oklch(97% 0.01 260);
                --text: oklch(20% 0.01 260);
                --error: oklch(55% 0.15 29);
                --warning: oklch(65% 0.12 65);
                --error-bg: oklch(55% 0.15 29 / 0.1);
                --border: oklch(85% 0.01 260);
                --code-bg: oklch(100% 0 0);
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: oklch(15% 0.01 260);
                    --text: oklch(90% 0.01 260);
                    --error: oklch(70% 0.15 29);
                    --warning: oklch(75% 0.12 65);
                    --error-bg: oklch(70% 0.15 29 / 0.15);
                    --border: oklch(30% 0.01 260);
                    --code-bg: oklch(20% 0.01 260);
                }
            }

            body {
                font-family: monospace;
                padding-block: 1.25rem;
                padding-inline: 1.25rem;
                background: var(--bg);
                color: var(--text);
                margin: 0;
            }

            h1 {
                color: var(--error);
                margin-block: 0 1rem;
                margin-inline: 0;
            }

            h2 {
                margin-block: 1.5rem 0.75rem;
                margin-inline: 0;
            }

            pre {
                background: var(--code-bg);
                padding-block: 0.625rem;
                padding-inline: 0.625rem;
                border: 1px solid var(--border);
                overflow-x: auto;
                margin: 0;
            }

            summary {
                cursor: pointer;
                font-weight: bold;
                padding-block: 0.5rem;
                padding-inline: 0;
            }

            details {
                margin-block: 0.75rem;
                margin-inline: 0;
            }

            .error-line {
                background: var(--error-bg);
                font-weight: bold;
            }

            .chain {
                background: var(--code-bg);
                padding-block: 0.9375rem;
                padding-inline: 0.9375rem;
                margin-block: 0.625rem;
                margin-inline: 0;
                border-inline-start: 0.25rem solid var(--warning);
            }

            .current {
                border-inline-start-color: var(--error);
            }

            .line {
                display: flex;
            }

            .line-num {
                inline-size: 3rem;
                text-align: end;
                padding-inline-end: 0.5rem;
                color: var(--text);
                opacity: 0.6;
                user-select: none;
            }

            .line-content {
                flex: 1;
            }

            .arrow {
                color: var(--error);
                font-weight: bold;
            }
        </style>';
    echo '</head><body>';

    // Header
    echo '<h1>' . ($data['headingTitle'] ?? 'Error') . '</h1>';
    echo '<p>' . ($data['message'] ?? 'Unknown error') . '</p>';

    // Exception Chain
    if (!empty($data['exceptions']) && count($data['exceptions']) > 1) {
      echo '<h2>Exception Chain</h2>';

      foreach ($data['exceptions'] as $index => $exception) {
        $isCurrent = $index === 0;
        echo '<div class="chain' . ($isCurrent ? ' current' : '') . '">';
        echo '<strong>' . ($index + 1) . '. ' . $exception['class'] . '</strong><br>';
        echo $exception['message'] . '<br>';
        echo '<small>' . $exception['file'] . ':' . $exception['line'] . '</small>';
        echo '</div>';
      }
    }

    // Stack Trace
    if (!empty($data['frames'])) {
      echo '<h2>Stack Trace</h2>';

      foreach ($data['frames'] as $index => $frame) {
        $isFirst = $index === 0;
        echo '<details' . ($isFirst ? ' open' : '') . '>';
        echo '<summary><strong>' . $frame['file'] . ':' . $frame['line'] . ' - ' . $frame['function'] . '()</strong></summary>';

        if (isset($frame['codeHtml'])) {
          echo '<div class="code-container">' . $frame['codeHtml'] . '</div>';
        }

        echo '</details>';
      }
    }

    echo '</body></html>';
  }
}
