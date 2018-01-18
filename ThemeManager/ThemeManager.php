<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\ThemeManager;

use Mindy\Bundle\TemplateBundle\Finder\ThemeTemplateFinder;
use Mindy\Template\Finder\FinderInterface;
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
     * ThemeManager constructor.
     *
     * @param string              $publicDir
     * @param ThemeTemplateFinder $themeTemplateFinder
     */
    public function __construct(string $publicDir, ThemeTemplateFinder $themeTemplateFinder)
    {
        $this->projectPublicDir = $publicDir;
        $this->themeTemplateFinder = $themeTemplateFinder;
    }

    /**
     * @return FinderInterface
     */
    public function getFinder(): FinderInterface
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
        throw new \LogicException('Not implemented');
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
