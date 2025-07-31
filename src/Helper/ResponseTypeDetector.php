<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Helper;

/**
 * ResponseTypeDetector
 *
 * @package Cartwryte\Sleuth\Helper
 */
final class ResponseTypeDetector
{
  /**
   * Detect the expected response type based on request headers
   *
   * Analyzes HTTP headers to determine if the client expects JSON or HTML response.
   * Checks for AJAX requests, JSON content type, and accept headers.
   *
   * @return string Response type ('json' or 'html')
   */
  public static function detect(): string
  {
    // X-Requested-With: XMLHttpRequest
    $xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $xRequestedWith = is_string($xRequestedWith) ? strtolower($xRequestedWith) : '';

    if ($xRequestedWith === 'xmlhttprequest') {
      return 'json';
    }

    // Content-Type: application/json
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $contentType = is_string($contentType) ? strtolower($contentType) : '';

    if (str_contains($contentType, 'application/json')) {
      return 'json';
    }

    // Accept: application/json, text/html
    $httpAccept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $httpAccept = is_string($httpAccept) ? strtolower($httpAccept) : '';

    if (empty($httpAccept)) {
      return 'html';
    }

    // Accept: application/json (without text/html)
    if (str_contains($httpAccept, 'application/json') && !str_contains($httpAccept, 'text/html')) {
      return 'json';
    }

    // Accept: application/json, text/html (JSON has higher priority)
    $jsonPos = strpos($httpAccept, 'application/json');
    $htmlPos = strpos($httpAccept, 'text/html');

    if ($jsonPos !== false && $htmlPos !== false && $jsonPos < $htmlPos) {
      return 'json';
    }

    return 'html';
  }
}
