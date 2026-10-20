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

namespace Tests\Atriametal\SyliusSpamProtectionPlugin\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use Sylius\Bundle\CoreBundle\Form\Type\ContactType;
use Sylius\Bundle\CoreBundle\Form\Type\Customer\CustomerRegistrationType;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Sylius\Bundle\UserBundle\Form\Type\UserRequestPasswordResetType;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

/**
 * Renders the Sylius shop forms with the shop templates, through the template events (Sylius 1) or the Twig hooks (Sylius 2).
 */
final class ShopFormsTest extends KernelTestCase
{
    private const REJECTION_MESSAGE = 'Your request could not be sent. Please try again.';

    /**
     * The last value is the factory of the data the Sylius controller gives to the form.
     *
     * @return iterable<string, array{class-string<FormTypeInterface<mixed>>, string, string, ?string}>
     */
    public static function protectedForms(): iterable
    {
        yield 'contact' => [ContactType::class, 'sylius.shop.contact.request.form', 'sylius_shop.contact.contact_request.content.form', null];
        yield 'registration' => [CustomerRegistrationType::class, 'sylius.shop.register.form', 'sylius_shop.account.register.content.form', 'sylius.factory.customer'];
        yield 'password reset' => [UserRequestPasswordResetType::class, 'sylius.shop.request_password_reset_token.form', 'sylius_shop.account.forgotten_password.content.form_container.form', null];
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     */
    #[DataProvider('protectedForms')]
    public function testTheShopRendersTheProtectionOfItsForm(string $formType, string $templateEvent, string $hook, ?string $dataFactory): void
    {
        $form = self::createForm($formType, $dataFactory);

        $html = self::renderForm($form, $templateEvent, $hook);

        self::assertSame(1, substr_count($html, \sprintf('name="%s[spam_protection][rendered_at]"', $form->getName())));
        self::assertStringNotContainsString(self::REJECTION_MESSAGE, $html);
    }

    /**
     * The shop forms do not render their own errors: without the plugin, a rejected visitor would not know why.
     *
     * @param class-string<FormTypeInterface<mixed>> $formType
     */
    #[DataProvider('protectedForms')]
    public function testTheShopTellsTheVisitorThatTheSubmissionWasRejected(string $formType, string $templateEvent, string $hook, ?string $dataFactory): void
    {
        $form = self::createForm($formType, $dataFactory);
        $form->submit([]);

        self::assertSame(1, substr_count(self::renderForm($form, $templateEvent, $hook), self::REJECTION_MESSAGE));
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     *
     * @return FormInterface<mixed>
     */
    private static function createForm(string $formType, ?string $dataFactory): FormInterface
    {
        // The CSRF token of the Sylius forms is stored in the session.
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        self::getService(RequestStack::class)->push($request);

        $data = null;
        if (null !== $dataFactory) {
            $factory = self::getContainer()->get($dataFactory);
            self::assertInstanceOf(FactoryInterface::class, $factory);
            $data = $factory->createNew();
        }

        return self::getService(FormFactoryInterface::class)->create($formType, $data);
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private static function renderForm(FormInterface $form, string $templateEvent, string $hook): string
    {
        $template = '1' === SyliusCoreBundle::MAJOR_VERSION
            ? \sprintf("{{ sylius_template_event('%s', { form }) }}", $templateEvent)
            : \sprintf("{%% hook '%s' with { form } %%}", $hook);

        return self::getService(Environment::class)->createTemplate($template)->render(['form' => $form->createView()]);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    private static function getService(string $id): object
    {
        $service = self::getContainer()->get($id);
        self::assertInstanceOf($id, $service);

        return $service;
    }
}
