<?php

declare(strict_types=1);

/**
 * Configuration of PHPStan that depends on the Magento version of the installation (environment variable
 * MAGENTO_ROOT, by default the one of the Anodosys development environment), included by phpstan.neon.
 *
 * Magento 2.4.8 (magento/framework 103.0.8) declares the LocalizedException of AbstractModel::_init() and load(), older
 * versions do not. Modules declare it as 2.4.8 needs it, so with older versions the tag is reported as too wide; the
 * check stays strict with 2.4.8 and newer, which run the same code.
 *
 * Before Magento 2.4.9 (magento/framework 103.0.9), Escaper::escapeHtml() declares string|array as result, also for a
 * string; phpstan.escaper.stub declares the types of 2.4.9.
 *
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

$magentoRoot = rtrim(getenv('MAGENTO_ROOT') ?: '/var/www/magento', '/');
$installed = is_file($magentoRoot . '/vendor/composer/installed.php') ?
    include $magentoRoot . '/vendor/composer/installed.php' : [];
$frameworkVersion = $installed['versions']['magento/framework']['version'] ?? '';
// for example 103.0.6.0-patch15
$frameworkVersion = preg_replace('/^(\d+\.\d+\.\d+).*$/', '$1', $frameworkVersion);

$ignoreErrors = [];

if ($frameworkVersion !== '' && version_compare($frameworkVersion, '103.0.8', '<')) {
    $ignoreErrors[] = [
        'message'         => '#^Method .+ has Magento\\\\Framework\\\\Exception\\\\LocalizedException in PHPDoc @throws tag but it\'s not thrown\.$#',
        'reportUnmatched' => false
    ];
}

$stubFiles = [];

if ($frameworkVersion !== '' && version_compare($frameworkVersion, '103.0.9', '<')) {
    $stubFiles[] = __DIR__ . '/phpstan.escaper.stub';
}

return ['parameters' => ['ignoreErrors' => $ignoreErrors, 'stubFiles' => $stubFiles]];
