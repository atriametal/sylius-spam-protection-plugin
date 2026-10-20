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

namespace Tests\Atriametal\SyliusSpamProtectionPlugin\Unit\Form\Extension;

use Atriametal\SyliusSpamProtectionPlugin\Form\Extension\LiveComponentRenderTypeExtension;
use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpReputationList;
use FlorentinGarnier\SpamProtection\SpamProtection;
use FlorentinGarnier\SpamProtectionBundle\Form\SpamProtectionType;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

#[CoversClass(LiveComponentRenderTypeExtension::class)]
final class LiveComponentRenderTypeExtensionTest extends TypeTestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();

        parent::setUp();
    }

    public function testItVerifiesARegularSubmission(): void
    {
        $this->requestStack->push(new Request());

        self::assertCount(1, $this->submitEmptyForm()->getErrors());
    }

    public function testItSkipsTheVerificationWhenALiveComponentReRendersTheForm(): void
    {
        $this->requestStack->push(new Request(attributes: ['_live_component' => 'sylius_shop:account:register:form', '_component_default_action' => true]));

        self::assertCount(0, $this->submitEmptyForm()->getErrors());
    }

    public function testItKeepsTheTokenAndTheChallengeIssuedWithThePageWhenALiveComponentReRendersTheForm(): void
    {
        $this->requestStack->push(new Request(attributes: ['_live_component' => 'sylius_shop:account:register:form', '_component_default_action' => true]));

        $view = $this->submitForm(['rendered_at' => 'token', 'proof_challenge' => 'challenge'])->createView();

        self::assertSame('token', self::getValue($view, 'rendered_at'));
        self::assertSame('challenge', self::getValue($view, 'proof_challenge'));
    }

    public function testItIssuesAFreshTokenAndChallengeOnARegularSubmission(): void
    {
        $this->requestStack->push(new Request());

        $view = $this->submitForm(['rendered_at' => 'token', 'proof_challenge' => 'challenge'])->createView();

        self::assertNotSame('token', self::getValue($view, 'rendered_at'));
        self::assertNotSame('challenge', self::getValue($view, 'proof_challenge'));
    }

    public function testItVerifiesALiveAction(): void
    {
        $this->requestStack->push(new Request(attributes: ['_live_component' => 'app:quotation', '_live_action' => 'save']));

        self::assertCount(1, $this->submitEmptyForm()->getErrors());
    }

    private static function getValue(FormView $form, string $field): mixed
    {
        $view = $form->children['spam_protection']->children[$field] ?? null;
        self::assertNotNull($view);
        self::assertIsArray($view->vars);

        return $view->vars['value'] ?? null;
    }

    /**
     * @return FormInterface<mixed>
     */
    private function submitEmptyForm(): FormInterface
    {
        return $this->submitForm([]);
    }

    /**
     * @param array<string, string> $protection
     *
     * @return FormInterface<mixed>
     */
    private function submitForm(array $protection): FormInterface
    {
        $form = $this->factory->createBuilder()->add('spam_protection', SpamProtectionType::class)->getForm();
        $form->submit(['spam_protection' => $protection]);

        return $form;
    }

    /**
     * @return list<FormExtensionInterface>
     */
    protected function getExtensions(): array
    {
        $ipReputation = new IpReputation(new IpReputationList(sys_get_temp_dir() . '/missing_ip_reputation_list.php'));

        return [new PreloadedExtension([
            new SpamProtectionType(SpamProtection::create('secret', new ArrayAdapter(), $ipReputation), $this->requestStack, 'secret'),
        ], [SpamProtectionType::class => [new LiveComponentRenderTypeExtension($this->requestStack)]])];
    }
}
