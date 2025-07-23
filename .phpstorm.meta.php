<?php

/*
 * (c) 2025 Anton Semenov <20430159+trydalcoholic@users.noreply.github.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PHPSTORM_META {
  override(\Opencart\System\Engine\Config::get(0), map([
    'error_display' => true,
    'error_log' => true,
    'error_page' => 'error.html',
    'cartwryte_editor_enabled' => true,
    'cartwryte_editor_default' => 'phpstorm',
    'cartwryte_editor_path_from' => '',
    'cartwryte_editor_path_to' => '',
  ]));
}
