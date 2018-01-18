<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\EventSubscriber;

use Mindy\Bundle\TemplateBundle\Finder\ThemeTemplateFinder;
use Mindy\Bundle\ThemeBundle\ThemeManager\ThemeConstrants;
use Mindy\Bundle\ThemeBundle\ThemeManager\ThemeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ThemeEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var ThemeTemplateFinder
     */
    protected $finder;
    /**
     * @var ThemeManager
     */
    protected $manager;

    /**
     * ThemeEventSubscriber constructor.
     *
     * @param ThemeTemplateFinder $finder
     * @param ThemeManager        $manager
     */
    public function __construct(ThemeTemplateFinder $finder, ThemeManager $manager)
    {
        $this->finder = $finder;
        $this->manager = $manager;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($request->cookies->has(ThemeConstrants::COOKIE_NAME)) {
            $themeName = $request->cookies->get(ThemeConstrants::COOKIE_NAME);
            if ($this->manager->hasTheme($themeName)) {
                $this->finder->setTheme($themeName);
            }
        } else {
//            $this->finder->setTheme($themeName);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => ['onRequest', 100],
        ];
    }
}
