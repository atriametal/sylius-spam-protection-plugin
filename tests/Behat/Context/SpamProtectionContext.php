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

namespace Tests\Atriametal\SyliusSpamProtectionPlugin\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Hook\BeforeScenario;
use Behat\Mink\Session;
use Behat\Step\Then;
use Behat\Step\When;
use DMore\ChromeDriver\ChromeDriver;
use Psr\Cache\CacheItemPoolInterface;
use Webmozart\Assert\Assert;

final class SpamProtectionContext implements Context
{
    /** Minimum age of a submission token, in seconds: faster submissions are rejected as bots. */
    private const HUMAN_FILLING_TIME = 3;

    private const SUBMISSION_TIMEOUT_IN_MILLISECONDS = 10000;

    public function __construct(
        private Session $session,
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * Used tokens and attempt counters are kept in the cache, shared with the web server: scenarios must not share them.
     */
    #[BeforeScenario]
    public function forgetPreviousSubmissions(): void
    {
        $this->cache->clear();
    }

    #[When('I take as long as a human to fill in the form')]
    public function iTakeAsLongAsAHumanToFillInTheForm(): void
    {
        sleep(self::HUMAN_FILLING_TIME);

        // Marks the page, so that the next step knows when the browser has left it.
        if ($this->isInBrowser()) {
            $this->session->executeScript('window.spamProtectionFormPage = true;');
        }
    }

    /**
     * The solver prevents the submission, solves the proof of work, then submits the form again: the page changes later.
     */
    #[When('I wait for my browser to solve the proof of work and send the form')]
    public function iWaitForMyBrowserToSolveTheProofOfWorkAndSendTheForm(): void
    {
        if (!$this->isInBrowser()) {
            return;
        }

        Assert::true(
            $this->session->wait(self::SUBMISSION_TIMEOUT_IN_MILLISECONDS, "undefined === window.spamProtectionFormPage && 'complete' === document.readyState"),
            'The browser did not send the form.',
        );
    }

    private function isInBrowser(): bool
    {
        return $this->session->getDriver() instanceof ChromeDriver;
    }

    #[Then('I should be notified that my request could not be sent')]
    public function iShouldBeNotifiedThatMyRequestCouldNotBeSent(): void
    {
        Assert::contains($this->session->getPage()->getText(), 'Your request could not be sent. Please try again.');
    }
}
