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
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Sylius 2 renders its registration form with a Live Component, which submits the form every time it re-renders it.
 * A re-render saves nothing: verifying it would consume the single-use token and count a rejected attempt.
 * It keeps the token and the challenge issued with the page as well: a fresh token, issued by the re-render that follows
 * the last field, would be too young when the visitor submits the form right after.
 * Live actions are still verified, as they may save the form.
 */
final class LiveComponentRenderTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Runs before the verification listener of SpamProtectionType, registered with the default priority.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            if ($this->isLiveComponentReRender()) {
                $event->stopPropagation();
            }
        }, 1);
    }

    /**
     * Runs after SpamProtectionType::finishView(), which issues a fresh token and challenge.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!$this->isLiveComponentReRender() || !$form->isSubmitted()) {
            return;
        }

        $token = $form->get('rendered_at')->getData();
        $challenge = $form->get('proof_challenge')->getData();
        if (!\is_string($token) || '' === $token || !\is_string($challenge) || '' === $challenge) {
            return;
        }

        self::setValue($view, 'rendered_at', $token);
        self::setValue($view, 'proof_challenge', $challenge);
    }

    /**
     * FormView::$vars is only typed as an array since Symfony 7.
     */
    private static function setValue(FormView $view, string $child, string $value): void
    {
        $childView = $view->children[$child] ?? null;
        if (null === $childView || !\is_array($childView->vars)) {
            return;
        }

        $vars = $childView->vars;
        $vars['value'] = $value;
        $childView->vars = $vars;
    }

    private function isLiveComponentReRender(): bool
    {
        return true === $this->requestStack->getCurrentRequest()?->attributes->get('_component_default_action');
    }

    public static function getExtendedTypes(): iterable
    {
        return [SpamProtectionType::class];
    }
}
