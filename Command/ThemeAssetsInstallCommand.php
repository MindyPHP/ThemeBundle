<?php

declare(strict_types=1);

/*
 * Studio 107 (c) 2018 Maxim Falaleev
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mindy\Bundle\ThemeBundle\Command;

use Mindy\Bundle\ThemeBundle\ThemeManager\Manifest;
use Mindy\Bundle\ThemeBundle\ThemeManager\ThemeManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ThemeAssetsInstallCommand extends ContainerAwareCommand
{
    const METHOD_COPY = 'copy';
    const METHOD_ABSOLUTE_SYMLINK = 'absolute symlink';
    const METHOD_RELATIVE_SYMLINK = 'relative symlink';

    protected static $defaultName = 'theme:assets:install';

    /**
     * @var Filesystem
     */
    protected $filesystem;
    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @param Filesystem   $filesystem
     * @param ThemeManager $themeManager
     */
    public function __construct(Filesystem $filesystem, ThemeManager $themeManager)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
        $this->themeManager = $themeManager;
    }

    protected function configure()
    {
        $this
            ->setDefinition([
                new InputArgument('target', InputArgument::OPTIONAL, 'The target directory', 'public'),
            ])
            ->addOption('symlink', null, InputOption::VALUE_NONE, 'Symlinks the assets instead of copying it')
            ->addOption('relative', null, InputOption::VALUE_NONE, 'Make relative symlinks')
            ->setDescription('Installs themes web assets under a public directory');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $kernel = $this->getApplication()->getKernel();
        $targetArg = rtrim($input->getArgument('target'), '/');
        $themeManager = $this->getContainer()->get(ThemeManager::class);

        if (!is_dir($targetArg)) {
            $targetArg = (isset($baseDir) ? $baseDir : $kernel->getContainer()->getParameter('kernel.project_dir')).'/'.$targetArg;
        }

        $themesDir = $targetArg.'/themes/';

        $io = new SymfonyStyle($input, $output);
        $io->newLine();

        if ($input->getOption('relative')) {
            $expectedMethod = self::METHOD_RELATIVE_SYMLINK;
            $io->text('Trying to install assets as <info>relative symbolic links</info>.');
        } elseif ($input->getOption('symlink')) {
            $expectedMethod = self::METHOD_ABSOLUTE_SYMLINK;
            $io->text('Trying to install assets as <info>absolute symbolic links</info>.');
        } else {
            $expectedMethod = self::METHOD_COPY;
            $io->text('Installing assets as <info>hard copies</info>.');
        }

        $io->newLine();

        $rows = [];
        $copyUsed = false;
        $exitCode = 0;
        $validAssetDirs = [];

        foreach ($themeManager->getThemes() as $manifest) {
            /** @var Manifest $manifest */
            if (!is_dir($sourceDir = $manifest->getAssetsPath())) {
                continue;
            }

            $dirName = basename($manifest->getPath());
            $targetDir = $themesDir.$dirName;
            $validAssetDirs[] = $dirName;

            if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
                $message = sprintf("%s\n-> %s", $manifest->getName(), $targetDir);
            } else {
                $message = $manifest->getName();
            }

            try {
                $this->filesystem->remove($targetDir);

                if (self::METHOD_RELATIVE_SYMLINK === $expectedMethod) {
                    $method = $this->relativeSymlinkWithFallback($sourceDir, $targetDir);
                } elseif (self::METHOD_ABSOLUTE_SYMLINK === $expectedMethod) {
                    $method = $this->absoluteSymlinkWithFallback($sourceDir, $targetDir);
                } else {
                    $method = $this->hardCopy($sourceDir, $targetDir);
                }

                if (self::METHOD_COPY === $method) {
                    $copyUsed = true;
                }

                if ($method === $expectedMethod) {
                    $rows[] = [sprintf('<fg=green;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'OK' : "\xE2\x9C\x94" /* HEAVY CHECK MARK (U+2714) */), $message, $method];
                } else {
                    $rows[] = [sprintf('<fg=yellow;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'WARNING' : '!'), $message, $method];
                }
            } catch (\Exception $e) {
                $rows[] = [sprintf('<fg=red;options=bold>%s</>', '\\' === DIRECTORY_SEPARATOR ? 'ERROR' : "\xE2\x9C\x98" /* HEAVY BALLOT X (U+2718) */), $message, $e->getMessage()];

                $exitCode = 1;
            }
        }

        // remove the assets of the bundles that no longer exist
        if (is_dir($themesDir)) {
            $dirsToRemove = Finder::create()
                ->depth(0)
                ->directories()
                ->exclude($validAssetDirs)
                ->in($themesDir);
            $this->filesystem->remove($dirsToRemove);
        }

        if ($rows) {
            $io->table(['', 'Theme', 'Method / Error'], $rows);
        }

        if (0 !== $exitCode) {
            $io->error('Some errors occurred while installing assets.');
        } else {
            if ($copyUsed) {
                $io->note('Some assets were installed via copy. If you make changes to these assets you have to run this command again.');
            }
            $io->success($rows ? 'All assets were successfully installed.' : 'No assets were provided by any bundle.');
        }

        return $exitCode;
    }

    /**
     * Try to create relative symlink.
     *
     * Falling back to absolute symlink and finally hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function relativeSymlinkWithFallback($originDir, $targetDir)
    {
        try {
            $this->symlink($originDir, $targetDir, true);
            $method = self::METHOD_RELATIVE_SYMLINK;
        } catch (IOException $e) {
            $method = $this->absoluteSymlinkWithFallback($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Creates symbolic link.
     *
     * @param string $originDir
     * @param string $targetDir
     * @param bool   $relative
     *
     * @throws IOException if link can not be created
     */
    private function symlink($originDir, $targetDir, $relative = false)
    {
        if ($relative) {
            $this->filesystem->mkdir(dirname($targetDir));
            $originDir = $this->filesystem->makePathRelative($originDir, realpath(dirname($targetDir)));
        }
        $this->filesystem->symlink($originDir, $targetDir);
        if (!file_exists($targetDir)) {
            throw new IOException(sprintf('Symbolic link "%s" was created but appears to be broken.', $targetDir), 0, null, $targetDir);
        }
    }

    /**
     * Try to create absolute symlink.
     *
     * Falling back to hard copy.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function absoluteSymlinkWithFallback($originDir, $targetDir)
    {
        try {
            $this->symlink($originDir, $targetDir);
            $method = self::METHOD_ABSOLUTE_SYMLINK;
        } catch (IOException $e) {
            // fall back to copy
            $method = $this->hardCopy($originDir, $targetDir);
        }

        return $method;
    }

    /**
     * Copies origin to target.
     *
     * @param string $originDir
     * @param string $targetDir
     *
     * @return string
     */
    private function hardCopy($originDir, $targetDir)
    {
        $this->filesystem->mkdir($targetDir, 0777);
        // We use a custom iterator to ignore VCS files
        $this->filesystem->mirror($originDir, $targetDir, Finder::create()->ignoreDotFiles(false)->in($originDir));

        return self::METHOD_COPY;
    }
}
