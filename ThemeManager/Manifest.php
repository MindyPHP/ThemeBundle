<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\ThemeManager;

class Manifest
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $author;
    /**
     * @var string
     */
    protected $path;
    /**
     * @var string
     */
    protected $assetsPath = 'build';

    /**
     * Manifest constructor.
     *
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getAssetsPath(): string
    {
        return sprintf(
            '%s/%s',
            rtrim($this->path, '/'),
            ltrim($this->assetsPath, '/')
        );
    }
}
