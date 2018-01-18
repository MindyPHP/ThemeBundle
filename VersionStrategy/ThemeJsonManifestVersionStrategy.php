<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\VersionStrategy;

use Mindy\Bundle\ThemeBundle\ThemeManager\ThemeManager;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

class ThemeJsonManifestVersionStrategy extends JsonManifestVersionStrategy
{
    /**
     * ThemeJsonManifestVersionStrategy constructor.
     *
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        parent::__construct(sprintf(
            '%s/manifest.json',
            $themeManager->getPublicThemePath()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function applyVersion($path)
    {
        try {
            return parent::applyVersion($path);
        } catch (\RuntimeException $e) {
            return $path;
        }
    }
}
