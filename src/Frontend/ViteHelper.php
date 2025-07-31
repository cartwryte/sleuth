<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cartwryte\Sleuth\Frontend;

use Cartwryte\Sleuth\Helper\FileHelper;
use JsonException;
use RuntimeException;

/**
 * ViteHelper
 *
 * @package Cartwryte\Sleuth\Frontend
 *
 * @author  Anton Semenov
 */
final class ViteHelper
{
  private string $buildPath;
  private string $buildUrl;
  private string $hotFilePath;
  /**
   * @var array<string, array<string, mixed>>|null
   */
  private ?array $manifest = null;

  /**
   * @param string $buildPath   Absolute path to the build directory on the filesystem (e.g., ".../public/build").
   * @param string $buildUrl    Public URL for the build directory (e.g., "/build").
   * @param string $hotFilePath absolute path to the 'hot' file
   */
  public function __construct(string $buildPath, string $buildUrl, string $hotFilePath)
  {
    $this->buildPath = rtrim($buildPath, '/');
    $this->buildUrl = rtrim($buildUrl, '/');
    $this->hotFilePath = $hotFilePath;
  }

  /**
   * Generates Vite tags for the given entry points.
   *
   * @param string|string[] $entrypoints
   *
   * @throws JsonException
   *
   * @return string the generated HTML tags
   */
  public function __invoke(array|string $entrypoints): string
  {
    $entrypoints = (array)$entrypoints;

    if ($this->isRunningHot()) {
      $tags = ['<script type="module" src="' . $this->hotAsset('@vite/client') . '"></script>'];

      foreach ($entrypoints as $entry) {
        $tags[] = '<script type="module" src="' . $this->hotAsset($entry) . '"></script>';
      }

      return implode('', $tags);
    }

    $manifest = $this->manifest();
    $tags = [];
    $preloads = [];

    foreach ($entrypoints as $entry) {
      $chunk = $this->chunk($manifest, $entry);

      $file = $chunk['file'] ?? null;

      if (!is_string($file)) {
        throw new RuntimeException("Vite manifest entry '$entry' is missing a 'file' key.");
      }

      $tags[$file] = '<script type="module" src="' . $this->assetUrl($file) . '"></script>';

      $imports = $chunk['imports'] ?? [];

      if (is_array($imports)) {
        foreach ($imports as $import) {
          if (!is_string($import) || !isset($manifest[$import]['file']) || !is_string($manifest[$import]['file'])) {
            continue;
          }

          $importFile = $manifest[$import]['file'];
          $preloads[$importFile] = '<link rel="modulepreload" href="' . $this->assetUrl($importFile) . '">';
        }
      }

      // 3. Safely get stylesheets
      $cssFiles = $chunk['css'] ?? [];

      if (is_array($cssFiles)) {
        foreach ($cssFiles as $cssFile) {
          if (!is_string($cssFile)) {
            continue;
          }

          $tags[$cssFile] = '<link rel="stylesheet" href="' . $this->assetUrl($cssFile) . '">';
          $preloads[$cssFile] = '<link rel="preload" href="' . $this->assetUrl($cssFile) . '" as="style">';
        }
      }
    }

    return implode('', $preloads) . implode('', $tags);
  }

  /**
   * Checks if the Vite development server is running.
   */
  private function isRunningHot(): bool
  {
    return file_exists($this->hotFilePath);
  }

  /**
   * Gets the URL for an asset from the Vite development server.
   *
   * @param string $asset
   *
   * @return string
   */
  private function hotAsset(string $asset): string
  {
    return rtrim(FileHelper::readFileContent($this->hotFilePath), '/') . '/' . ltrim($asset, '/');
  }

  /**
   * Gets the public URL for a built asset.
   *
   * @param string $path
   *
   * @return string
   */
  private function assetUrl(string $path): string
  {
    return $this->buildUrl . '/' . $path;
  }

  /**
   * Reads and decodes the manifest file.
   *
   * @throws JsonException|RuntimeException
   *
   * @return array<string, array<string, mixed>>
   */
  private function manifest(): array
  {
    if (!is_null($this->manifest)) {
      return $this->manifest;
    }

    // Try both locations for manifest
    $manifestPaths = [
      $this->buildPath . '/manifest.json',
      $this->buildPath . '/.vite/manifest.json',
    ];

    $manifestPath = null;

    foreach ($manifestPaths as $path) {
      if (file_exists($path)) {
        $manifestPath = $path;
        break;
      }
    }

    if (is_null($manifestPath)) {
      throw new RuntimeException('Vite manifest not found. Please run "npm run build".');
    }

    $content = FileHelper::readFileContent($manifestPath);
    $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($decoded)) {
      throw new RuntimeException('Invalid manifest format: expected array');
    }

    $validatedManifest = [];

    foreach ($decoded as $key => $value) {
      if (!is_string($key)) {
        throw new RuntimeException('Invalid manifest format: keys must be strings');
      }

      if (!is_array($value)) {
        throw new RuntimeException("Invalid manifest format: entry '$key' must be an array");
      }

      $validatedManifest[$key] = array_filter(
        $value,
        static fn ($innerKey): bool => is_string($innerKey),
        ARRAY_FILTER_USE_KEY,
      );
    }

    $this->manifest = $validatedManifest;

    return $this->manifest;
  }

  /**
   * Gets a specific chunk from the manifest.
   *
   * @param array<string, array<string, mixed>> $manifest
   * @param string                              $file
   *
   * @return array<string, mixed>
   */
  private function chunk(array $manifest, string $file): array
  {
    if (!isset($manifest[$file])) {
      throw new RuntimeException("Unable to locate file in Vite manifest: $file.");
    }

    return $manifest[$file];
  }
}
