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

namespace Tests\Atriametal\SyliusSpamProtectionPlugin\Unit\Templates;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;

/**
 * Covers the Twig template of the plugin, not a PHP class.
 */
#[CoversNothing]
final class SpamProtectionTemplateTest extends TestCase
{
    private const FIELD = 'name="form[spam_protection]"';

    private const PLUGIN_TEMPLATE = '{{ include("@AtriametalSyliusSpamProtectionPlugin/spam_protection.html.twig") }}';

    public function testItRendersTheFieldWhenTheThemeDoesNot(): void
    {
        self::assertSame(1, substr_count($this->render(self::PLUGIN_TEMPLATE), self::FIELD));
    }

    public function testItRendersTheFieldOfTheTwigHookContext(): void
    {
        self::assertSame(1, substr_count($this->render(self::PLUGIN_TEMPLATE, inHook: true), self::FIELD));
    }

    public function testItSkipsTheFieldAlreadyRenderedByTheTheme(): void
    {
        $html = $this->render('{{ form_widget(form.spam_protection) }}' . self::PLUGIN_TEMPLATE);

        self::assertSame(1, substr_count($html, self::FIELD));
    }

    private function render(string $page, bool $inHook = false): string
    {
        $templates = new FilesystemLoader(\dirname((string) (new \ReflectionClass(AppVariable::class))->getFileName()) . '/Resources/views/Form');
        $templates->addPath(\dirname(__DIR__, 3) . '/templates', 'AtriametalSyliusSpamProtectionPlugin');

        $twig = new Environment(new ChainLoader([new ArrayLoader(['page.html.twig' => $page]), $templates]), ['strict_variables' => true]);
        $twig->addExtension(new FormExtension());
        $twig->addExtension(new TranslationExtension());
        $twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn (): FormRenderer => new FormRenderer(new TwigRendererEngine(['form_div_layout.html.twig'], $twig)),
        ]));

        $form = Forms::createFormFactory()->createBuilder()->add('spam_protection', TextType::class)->getForm();

        $context = ['form' => $form->createView()];

        return $twig->render('page.html.twig', $inHook ? ['hookable_metadata' => ['context' => $context]] : $context);
    }
}
