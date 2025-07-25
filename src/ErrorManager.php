<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth;

use Cartwryte\Sleuth\Formatter\LogFormatter;
use Cartwryte\Sleuth\Helper\ResponseTypeDetector;
use Cartwryte\Sleuth\View\View;
use ErrorException;
use Exception;
use Opencart\System\Engine\Config;
use Opencart\System\Library\Log;
use Throwable;

/**
 * Exception Handler for OpenCart
 *
 * Provides a beautiful error page with stack traces, technical information,
 * and helpful suggestions for resolving common issues.
 *
 * @package Cartwryte\Sleuth
 */
final class ErrorManager
{
  private bool $isRegistered = false;

  /**
   * Constructor
   *
   * @param Config $config
   * @param Log    $log
   */
  public function __construct(
    private Config $config,
    private Log $log,
  ) {}

  /**
   * Register all error handlers
   */
  public function register(): void
  {
    // Avoid double registration
    // Don't register handlers for CLI
    if ($this->isRegistered || PHP_SAPI === 'cli') {
      return;
    }

    set_exception_handler([$this, 'handle']);
    set_error_handler([$this, 'handleError']);
    register_shutdown_function([$this, 'handleShutdown']);

    $this->isRegistered = true;
  }

  /**
   * Unregister all error handlers
   */
  public function unregister(): void
  {
    if (!$this->isRegistered) {
      return;
    }

    restore_exception_handler();
    restore_error_handler();
    // Note: can't unregister shutdown function

    $this->isRegistered = false;
  }

  /**
   * Check if handlers are registered
   */
  public function isRegistered(): bool
  {
    return $this->isRegistered;
  }

  /**
   * Handle PHP errors
   *
   * @param int    $severity
   * @param string $message
   * @param string $file
   * @param int    $line
   *
   * @throws Throwable
   *
   * @return bool
   */
  public function handleError(int $severity, string $message, string $file, int $line): bool
  {
    // If error suppressed with @ - don't handle
    if (!(error_reporting() & $severity)) {
      return false; // Pass to PHP for standard handling
    }

    $exception = new ErrorException($message, 0, $severity, $file, $line);
    $this->handle($exception);

    return true; // Suppress standard PHP error handling
  }

  /**
   * Handle exception and display error page
   *
   * @param Throwable $e
   *
   * @throws Throwable
   *
   * @return void
   */
  public function handle(Throwable $e): void
  {
    // Log error in all cases
    $this->logError($e);

    // Redirect if display disabled
    if (!$this->config->get('error_display')) {
      header('Location: ' . $this->config->get('error_page'));
      exit();
    }

    // Normal error page rendering
    $this->renderErrorPage($e);
  }

  /**
   * Handle fatal errors on shutdown
   *
   * @throws Throwable
   */
  public function handleShutdown(): void
  {
    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
      $exception = new ErrorException(
        message: $error['message'],
        code: 0,
        severity: $error['type'],
        filename: $error['file'],
        line: $error['line'],
      );

      $this->handle($exception);
    }
  }

  /**
   * Log error message if logging is enabled
   *
   * @param Throwable $e
   *
   * @return void
   */
  private function logError(Throwable $e): void
  {
    if ($this->config->get('error_log')) {
      $message = LogFormatter::simple($e); // or json/psr3/apache

      $this->log->write($message);
    }
  }

  /**
   * Render error page for display
   *
   * @param Throwable $e
   *
   * @throws Exception
   *
   * @return void
   */
  private function renderErrorPage(Throwable $e): void
  {
    // Set HTTP code only if headers not sent
    if (!headers_sent()) {
      http_response_code(500);
    }

    $view = new View($this->config, $this->log);
    $view->render($e, ResponseTypeDetector::detect());

    exit();
  }
}
