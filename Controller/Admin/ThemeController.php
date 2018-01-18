<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\Controller\Admin;

use Mindy\Bundle\MindyBundle\Controller\Controller;
use Mindy\Bundle\TemplateBundle\Finder\ThemeTemplateFinder;
use Mindy\Bundle\ThemeBundle\ThemeManager\ThemeConstrants;
use Mindy\Bundle\ThemeBundle\ThemeManager\ThemeManager;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

class ThemeController extends Controller
{
    public function list()
    {
        $themes = $this->get(ThemeManager::class)->getThemes();
        $currentTheme = $this->get(ThemeTemplateFinder::class)->getTheme();

        return $this->render('admin/theme/list.html', [
            'themes' => $themes,
            'current_theme' => $currentTheme,
        ]);
    }

    public function apply(string $theme)
    {
        $manager = $this->get(ThemeManager::class);
        if (false === $manager->hasTheme($theme)) {
            $this->createNotFoundException();
        }

        $manager->applyTheme($theme);

        $this->addFlash('success', 'Тема применена');

        $response = $this->redirectToRoute('admin_theme_list');
        $response->headers->removeCookie(ThemeConstrants::COOKIE_NAME);
        return $response;
    }

    public function preview(Request $request, string $theme)
    {
        $themeManager = $this->get(ThemeManager::class);
        $response = $this->render('theme/preview.html', [
            'themes' => $themeManager->getThemes(),
            'current_theme' => $theme,
            'preview_theme' => $request->cookies->get(ThemeConstrants::COOKIE_NAME),
        ]);
        $response->headers->setCookie(new Cookie(ThemeConstrants::COOKIE_NAME, $theme));
        return $response;
    }

    public function cancelPreview()
    {
        $response = $this->redirectToRoute('admin_theme_list');
        $response->headers->removeCookie(ThemeConstrants::COOKIE_NAME);
        return $response;
    }
}
