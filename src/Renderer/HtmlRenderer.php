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
use Cartwryte\Sleuth\Helper\SyntaxHighlighter;
use Cartwryte\Sleuth\View\ViewModel;
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
  private array $editorConfig;
  private string $templatePath;
  private string $assetPath;

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
    $this->assetPath = rtrim($basePath, '/') . '/';
  }

  /**
   * Render HTML error page for browser requests.
   *
   * Attempts to use the .tpl template file. If not found, it uses a built-in fallback.
   *
   * @param Throwable $e
   */
  public function render(Throwable $e): void
  {
    $data = $this->viewModel->build($e);
    $data = $this->addHtmlData($data);

    // The main layout file is the single entry point for rendering.
    $layoutFile = $this->templatePath . 'layout.tpl';

    if (is_file($layoutFile)) {
      $this->renderTemplate($layoutFile, $data);
    } else {
      $this->logError(new RuntimeException("Layout template not found: $layoutFile"));
      $this->renderFallback($data);
    }
  }

  /**
   * Renders a .tpl template file with the given data.
   *
   * @param string $file The path to the .tpl template file.
   * @param array  $data the data to be extracted into variables for the template
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
   * Enrich data with HTML-specific elements.
   *
   * @param array $data
   *
   * @return array
   */
  private function addHtmlData(array $data): array
  {
    $data['css'] = $this->getCss();
    $data['js'] = $this->getJs();

    foreach ($data['frames'] as &$frame) {
      if (isset($frame['code'])) {
        $frame['codeHtml'] = $this->buildCodeHtml(
          $frame['code'],
          $frame['startLine'],
          $frame['line'],
          $frame['fullPath'],
        );
      }
    }

    return $data;
  }

  /**
   * Build HTML for syntax highlighted code with line numbers.
   *
   * @param string $rawCode   Raw source code
   * @param int    $start     Starting line number
   * @param int    $errorLine Line number where error occurred
   * @param string $filePath  Full file path for editor links
   *
   * @return string HTML with syntax highlighting
   */
  private function buildCodeHtml(string $rawCode, int $start, int $errorLine, string $filePath): string
  {
    $codeLines = explode("\n", $rawCode);
    $html = '';

    foreach ($codeLines as $index => $rawLine) {
      $lineNumber = $start + $index;
      $isError = $lineNumber === $errorLine;
      $classes = 'code-line' . ($isError ? ' code-line--error' : '');
      $highlighted = SyntaxHighlighter::highlight($rawLine);

      $editorUrl = $this->generateEditorUrl($filePath, $lineNumber);

      $html .= sprintf(
        '<div class="%s">
						<a href="%s" class="editor-link" title="Open in editor">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
								<path d="M21 6.75736L19 8.75736V4H10V9H5V20H19V17.2426L21 15.2426V21.0082C21 21.556 20.5551 22 20.0066 22H3.9934C3.44476 22 3 21.5501 3 20.9932V8L9.00319 2H19.9978C20.5513 2 21 2.45531 21 2.9918V6.75736ZM21.7782 8.80761L23.1924 10.2218L15.4142 18L13.9979 17.9979L14 16.5858L21.7782 8.80761Z"></path>
							</svg>
						</a>
						<span class="code-line__number">%d</span>
						<span class="code-line__content">%s</span>
					</div>',
        $classes,
        htmlspecialchars($editorUrl, ENT_QUOTES, 'UTF-8'),
        $lineNumber,
        $highlighted,
      );
    }

    return $html;
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
    $config = $this->getEditorConfig();

    if (!$config['enabled']) {
      return '#';
    }

    $hostPath = $this->mapPath($file, $config['path_mapping']);
    $editorKey = $config['default_editor'];

    if (!isset($config['editors'][$editorKey])) {
      return '#';
    }

    $editor = $config['editors'][$editorKey];

    return str_replace(
      ['{file}', '{line}'],
      [urlencode($hostPath), $line],
      $editor['url'],
    );
  }

  /**
   * Map container path to host path using configuration.
   *
   * If mapping is not configured (empty strings), returns path as-is.
   * Otherwise, performs Docker container to host path mapping.
   *
   * @param string $path    File path to map
   * @param array  $mapping Mapping configuration with 'from' and 'to' keys
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

  /**
   * Get editor configuration
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
   * Get error CSS content
   */
  private function getCss(): false|string
  {
    $file = $this->assetPath . 'css/error.css';

    return is_file($file) ? file_get_contents($file) : '';
  }

  /**
   * Get error JS content
   */
  private function getJs(): false|string
  {
    $file = $this->assetPath . 'js/error.js';

    return is_file($file) ? file_get_contents($file) : '';
  }

  /**
   * Render fallback when Twig is not available
   *
   * @param array $data
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

        $lines = explode("\n", $frame['code']);
        $startLine = $frame['startLine'];
        $errorLine = $frame['line'];

        echo '<pre>';

        foreach ($lines as $lineIndex => $lineContent) {
          $currentLineNumber = $startLine + $lineIndex;
          $isErrorLine = ($currentLineNumber === $errorLine);

          $prefix = $isErrorLine ? '→ ' : '  ';
          $lineNum = str_pad((string)$currentLineNumber, 3);
          $content = rtrim($lineContent);

          if ($isErrorLine) {
            echo '<span class="error-line">' . $prefix . $lineNum . ' ' . htmlspecialchars($content) . '</span>' . "\n";
          } else {
            echo $prefix . $lineNum . ' ' . htmlspecialchars($content) . "\n";
          }
        }
        echo '</pre>';
        echo '</details>';
      }
    }

    echo '</body></html>';
  }
}
