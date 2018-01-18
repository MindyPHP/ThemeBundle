<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\ThemeManager;

use Mindy\Bundle\SettingBundle\Settings\SettingsManager;
use Mindy\Bundle\TemplateBundle\Finder\ThemeTemplateFinder;
use function GuzzleHttp\json_decode;

class ThemeManager
{
    /**
     * @var string
     */
    protected $projectPublicDir;
    /**
     * @var ThemeTemplateFinder
     */
    protected $themeTemplateFinder;
    /**
     * @var SettingsManager
     */
    protected $settingsManager;

    /**
     * ThemeManager constructor.
     *
     * @param string              $publicDir
     * @param ThemeTemplateFinder $themeTemplateFinder
     */
    public function __construct(string $publicDir, ThemeTemplateFinder $themeTemplateFinder, SettingsManager $settingsManager)
    {
        $this->projectPublicDir = $publicDir;
        $this->themeTemplateFinder = $themeTemplateFinder;
        $this->settingsManager = $settingsManager;
    }

    /**
     * @return ThemeTemplateFinder
     */
    public function getFinder(): ThemeTemplateFinder
    {
        return $this->themeTemplateFinder;
    }

    /**
     * @return Manifest[]|array
     */
    public function getThemes(): array
    {
        $themesPath = $this->themeTemplateFinder->getThemesPath();

        $paths = glob(sprintf('%s/*/manifest.json', $themesPath));

        $themes = [];
        foreach ($paths as $manifestFile) {
            $content = file_get_contents($manifestFile);
            $jsonManifest = json_decode($content, true);
            $themes[basename(dirname($manifestFile))] = $this->createManifest(array_merge($jsonManifest, [
                'path' => dirname($manifestFile),
            ]));
        }

        return $themes;
    }

    /**
     * @param array $parameters
     *
     * @return Manifest
     */
    public function createManifest(array $parameters): Manifest
    {
        return new Manifest($parameters);
    }

    /**
     * @param $themeName
     *
     * @return bool
     */
    public function hasTheme($themeName): bool
    {
        return array_key_exists($themeName, $this->getThemes());
    }

    /**
     * @param $themeName
     *
     * @return bool
     */
    public function applyTheme($themeName): bool
    {
        return $this->settingsManager->set([
            'mindy.bundle.theme.theme' => $themeName
        ]);
    }

    /**
     * @return string
     */
    public function getCurrentThemePath(): string
    {
        return current($this->themeTemplateFinder->getPaths());
    }

    public function getPublicThemePath(): string
    {
        return sprintf(
            '%s/themes/%s',
            $this->projectPublicDir,
            $this->themeTemplateFinder->getTheme()
        );
    }
}
