<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\Settings;

use Mindy\Bundle\SettingBundle\Settings\SettingsInterface;

class ThemeSettings implements SettingsInterface
{
    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return 'mindy.bundle.theme';
    }
}
