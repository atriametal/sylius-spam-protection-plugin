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

namespace Atriametal\SyliusSpamProtectionPlugin\Form\Extension;

use FlorentinGarnier\SpamProtectionBundle\Form\SpamProtectionType;
use Sylius\Bundle\CoreBundle\Form\Type\ContactType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

final class ContactTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('spam_protection', SpamProtectionType::class, ['protection_scope' => 'contact', 'content_fields' => ['message']]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ContactType::class];
    }
}
