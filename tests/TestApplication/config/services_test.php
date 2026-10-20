<?php

/*
 * This file is part of the atriametal/sylius-spam-protection-plugin package.
 *
 * (c) Atriametal <contact@kemp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Behat\Behat\Context\Context;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    // The Behat scenarios run on Sylius 2 only: the Behat services of Sylius 1.14 need a mocker container that the
    // Test Application does not provide. Behat is also removed on the Symfony versions it does not support (5.4, 8).
    if (str_starts_with($container->env() ?? '', 'test') && '1' !== SyliusCoreBundle::MAJOR_VERSION && interface_exists(Context::class)) {
        // Sylius 2.3 ships its Behat services in PHP, earlier versions in XML (which Symfony 8 can no longer load).
        $syliusBehatServices = \dirname(__DIR__, 3) . '/vendor/sylius/sylius/src/Sylius/Behat/Resources/config/services';
        $container->import(file_exists($syliusBehatServices . '.php') ? $syliusBehatServices . '.php' : $syliusBehatServices . '.xml');
        $container->import('@AtriametalSyliusSpamProtectionPlugin/tests/Behat/Resources/services.php');
    }
};
