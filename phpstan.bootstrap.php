<?php

declare(strict_types=1);

/**
 * Loads the PHPStan autoloader of Magento, which generates factories and extension attributes for the analysis.
 *
 * Factories are generated with the factory generator of Magento itself, as setup:di:compile does, so that create()
 * returns the created class (the stubs of the Magento test framework return nothing, which PHPStan sees as mixed). No
 * generator runs for classes that Composer finds outside of the generated directory: Magento has real classes with names
 * like generated ones (Magento\Shipping\Model\CarrierFactory, ...), whose methods have other signatures.
 *
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

$magentoRoot = getenv('MAGENTO_ROOT') ?: '/var/www/magento';

$composerLoader = require rtrim($magentoRoot, '/') . '/vendor/autoload.php';

require rtrim($magentoRoot, '/') . '/dev/tests/static/framework/Magento/PhpStan/autoload.php';

/** @var \Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader $generatedCodeAutoloader */
spl_autoload_unregister([$generatedCodeAutoloader, 'load']);

$factoryIo = new \Magento\Framework\Code\Generator\Io(
    new \Magento\Framework\Filesystem\Driver\File(),
    TESTS_TEMP_DIR . '/phpstan/generated'
);

spl_autoload_register(
    static function (string $className) use ($composerLoader, $generatedCodeAutoloader, $factoryIo): void {
        static $generating = [];

        $file = $composerLoader->findFile($className);

        if ($file !== false && ! str_contains(str_replace('\\', '/', $file), '/generated/code/')) {
            // a real class, which Composer loads itself
            return;
        }

        if (str_ends_with($className, 'Factory') && ! isset($generating[ $className ])) {
            $resultFile = $factoryIo->generateResultFileName($className);

            if (! $factoryIo->fileExists($resultFile)) {
                $sourceClassName = substr($className, 0, -7);

                $generating[ $className ] = true;

                try {
                    if (class_exists($sourceClassName) || interface_exists($sourceClassName)) {
                        (new \Magento\Framework\ObjectManager\Code\Generator\Factory(
                            $sourceClassName,
                            $className,
                            $factoryIo
                        ))->generate();
                    }
                } finally {
                    unset($generating[ $className ]);
                }
            }

            if ($factoryIo->fileExists($resultFile)) {
                include $resultFile;

                return;
            }
        }

        $generatedCodeAutoloader->load($className);
    }
);
