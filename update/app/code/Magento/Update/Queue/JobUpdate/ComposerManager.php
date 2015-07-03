<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Update\Queue\JobUpdate;

use Composer\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Class for managing main Magento composer configuration file.
 */
class ComposerManager
{
    /**#@+
     * Composer command
     */
    const COMPOSER_UPDATE = 'update';
    const COMPOSER_REQUIRE = 'require';
    const COMPOSER_SHOW = 'show';
    /**#@-*/

    const PACKAGE_NAME = 'package_name';
    const PACKAGE_VERSION = 'package_version';

    /**#@+
     * Composer command params
     */
    const PARAM_COMMAND = 'command';
    const PARAM_NO_UPDATE = '--no-update';
    const PARAM_PACKAGES = 'packages';
    const PARAM_PACKAGE = 'package';
    const PARAM_AVAILABLE = '--available';
    /**#@-*/

    const COMPOSER_HOME_DIR = 'var/composer_home';

    /** @var string */
    protected $composerConfigFileDir;

    /** @var \Magento\Update\Queue\JobUpdate\ConsoleArrayInputFactory */
    protected $consoleArrayInputFactory;

    /** @var \Symfony\Component\Console\Output\BufferedOutput */
    protected $consoleOutput;

    /** @var \Composer\Console\Application */
    protected $consoleApplication;

    /**
     * Initialize dependencies.
     *
     * @param string|null $composerConfigFileDir
     * @param ConsoleArrayInputFactory|null $consoleArrayInputFactory
     * @param BufferedOutput|null $consoleOutput
     * @param Application|null $consoleApplication
     */
    public function __construct(
        $composerConfigFileDir = null,
        ConsoleArrayInputFactory $consoleArrayInputFactory = null,
        BufferedOutput $consoleOutput = null,
        Application $consoleApplication = null
    ) {
        $this->composerConfigFileDir = $composerConfigFileDir ? $composerConfigFileDir : MAGENTO_BP;
        $this->consoleArrayInputFactory = $consoleArrayInputFactory ? $consoleArrayInputFactory
            : new ConsoleArrayInputFactory();
        $this->consoleOutput = $consoleOutput ? $consoleOutput : new BufferedOutput();
        $this->consoleApplication = $consoleApplication ? $consoleApplication : new Application();
    }

    /**
     * Update composer config file using provided directive
     *
     * @param string $directive
     * @param array $params
     * @return bool
     */
    public function updateComposerConfigFile($directive, array $params)
    {
        $camelCaseDirective = '';
        foreach (explode('-', $directive) as $item) {
            $camelCaseDirective .= ucfirst($item);
        }
        $directiveHandler = sprintf('update%sDirective', $camelCaseDirective);
        if (!method_exists($this, $directiveHandler)) {
            throw new \LogicException(sprintf('Composer directive "%s" is not supported', $directive));
        }
        return call_user_func([$this, $directiveHandler], $params);
    }

    /**
     * Run "composer update"
     *
     * @return bool
     * @throws \RuntimeException
     */
    public function runUpdate()
    {
        return $this->runComposerCommand([self::PARAM_COMMAND => self::COMPOSER_UPDATE]);
    }

    /**
     * Retrieve all available versions for a package
     *
     * @param string $package
     * @return array
     * @throws \RuntimeException
     */
    public function getAvailableVersions($package)
    {
        $versionsPattern = '/^versions\s*\:\s(.+)$/m';

        $commandParams = [
            self::PARAM_COMMAND => self::COMPOSER_SHOW,
            self::PARAM_PACKAGE => $package,
            self::PARAM_AVAILABLE => true
        ];
        $result = $this->runComposerCommand($commandParams);
        $matches = [];
        preg_match($versionsPattern, $result, $matches);
        if (!isset($matches[1])) {
            throw new \RuntimeException(
                sprintf('Couldn\'t get available versions for package %s', $commandParams[self::PARAM_PACKAGE])
            );
        }
        return explode(', ', $matches[1]);
    }

    /**
     * Update require directive in composer config file
     *
     * @param array $params
     * @return bool
     * @throws \RuntimeException
     */
    protected function updateRequireDirective(array $params)
    {
        $commandParams = [self::PARAM_COMMAND => self::COMPOSER_REQUIRE, self::PARAM_NO_UPDATE => true];
        $packageList = [];
        foreach ($params as $param) {
            if (!isset($param[self::PACKAGE_NAME]) || !isset($param[self::PACKAGE_VERSION])) {
                throw new \RuntimeException('Incorrect/missing parameters for composer directive "require"');
            }
            $commandParams[self::PARAM_PACKAGES][] = $param[self::PACKAGE_NAME] . ':' . $param[self::PACKAGE_VERSION];
            $packageList[] = $param[self::PACKAGE_NAME];
        }
        $this->removeReplaceDirective($packageList);
        return $this->runComposerCommand($commandParams);
    }

    /**
     * Run composer command
     *
     * @param array $commandParams
     * @return bool
     * @throws \RuntimeException
     */
    protected function runComposerCommand(array $commandParams)
    {
        $input = $this->consoleArrayInputFactory->create($commandParams);
        $this->consoleApplication->setAutoExit(false);
        putenv('COMPOSER_HOME=' . $this->composerConfigFileDir . '/' . self::COMPOSER_HOME_DIR);
        putenv('COMPOSER=' . $this->composerConfigFileDir . '/composer.json');
        $exitCode = $this->consoleApplication->run($input, $this->consoleOutput);
        $output = $this->consoleOutput->fetch();

        if ($exitCode) {
            $commandParamsString = json_encode($commandParams, JSON_UNESCAPED_SLASHES);
            throw new \RuntimeException(
                sprintf('Command "%s"%s failed: %s', $commandParams['command'], $commandParamsString, $output)
            );
        }
        return $output;
    }

    /**
     * Remove replace directive in composer config file
     *
     * @param string[] $packageList
     * @return void
     */
    protected function removeReplaceDirective($packageList)
    {
        if (!empty($packageList)) {
            $composerFilePath = $this->composerConfigFileDir . '/composer.json';
            $fileContent = file_get_contents($composerFilePath);
            $fileJsonFormat = json_decode($fileContent, true);
            $key = 'replace';
            if (array_key_exists($key, $fileJsonFormat)) {
                foreach ($packageList as $packageName) {
                    if (array_key_exists($packageName, $fileJsonFormat[$key])) {
                        unset($fileJsonFormat[$key][$packageName]);
                    }
                }
                $newFileContent = json_encode($fileJsonFormat, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                file_put_contents($composerFilePath, $newFileContent . "\n");
            }
        }
    }
}
