<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FreeShipping;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Core\Install\Database;
use Thelia\Module\BaseModule;

class FreeShipping extends BaseModule
{
    public const DOMAIN_NAME = 'freeshipping';

    /**
     * Compare the threshold with the tax included product total. "0" compares it
     * with the untaxed total, which is what a B2B shop expects.
     */
    public const CONFIG_THRESHOLD_INCLUDES_TAXES = 'threshold_includes_taxes';

    /**
     * Subtract cart discounts before comparing with the threshold.
     */
    public const CONFIG_DEDUCT_DISCOUNTS = 'deduct_discounts';

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([__DIR__.'/I18n/*', __DIR__.'/Config/**/*.php', __DIR__.'/Tests/*', __DIR__.'/FreeShipping.php'])
            ->autowire(true)
            ->autoconfigure(true);
    }

    public function postActivation(?ConnectionInterface $con = null): void
    {
        if (!self::getConfigValue('is_initialized')) {
            (new Database($con))->insertSql(null, [__DIR__.'/Config/TheliaMain.sql']);

            self::setConfigValue(self::CONFIG_THRESHOLD_INCLUDES_TAXES, '1');
            self::setConfigValue(self::CONFIG_DEDUCT_DISCOUNTS, '1');
            self::setConfigValue('is_initialized', '1');
        }
    }

    public function update($currentVersion, $newVersion, ?ConnectionInterface $con = null): void
    {
        $updateDir = __DIR__.'/Config/update';

        if (!is_dir($updateDir)) {
            return;
        }

        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in($updateDir);

        $database = new Database($con);

        foreach ($finder as $file) {
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }
}
