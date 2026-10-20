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

namespace Atriametal\SyliusSpamProtectionPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class AtriametalSyliusSpamProtectionExtension extends Extension implements PrependExtensionInterface
{
    private const BLOCKS = [
        'atriametal_spam_protection_error' => ['template' => '@AtriametalSyliusSpamProtectionPlugin/spam_protection_error.html.twig', 'priority' => 1000],
        'atriametal_spam_protection' => ['template' => '@AtriametalSyliusSpamProtectionPlugin/spam_protection.html.twig', 'priority' => -100],
    ];

    /** Sylius 1 */
    private const FORM_TEMPLATE_EVENTS = [
        'sylius.shop.contact.request.form',
        'sylius.shop.register.form',
        'sylius.shop.request_password_reset_token.form',
    ];

    /** Sylius 2 */
    private const FORM_HOOKS = [
        'sylius_shop.contact.contact_request.content.form',
        'sylius_shop.account.register.content.form',
        'sylius_shop.account.forgotten_password.content.form_container.form',
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config')))->load('services.php');
    }

    /**
     * Shop templates end their forms with render_rest set to false, so the protection is rendered through their template
     * events (Sylius 1) or Twig Hooks (Sylius 2).
     * A theme that already renders the field explicitly is unaffected: the template skips a field that is already rendered.
     * The rejection message is rendered at the top of the form, as these forms do not render their own errors.
     */
    public function prepend(ContainerBuilder $container): void
    {
        if ((int) SyliusCoreBundle::MAJOR_VERSION >= 2) {
            $container->prependExtensionConfig('sylius_twig_hooks', [
                'hooks' => array_fill_keys(self::FORM_HOOKS, self::BLOCKS),
            ]);

            return;
        }

        $container->prependExtensionConfig('sylius_ui', [
            'events' => array_fill_keys(self::FORM_TEMPLATE_EVENTS, ['blocks' => self::BLOCKS]),
        ]);
    }
}
