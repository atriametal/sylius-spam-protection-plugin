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

namespace Atriametal\SyliusSpamProtectionPlugin\Mailer;

use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpRiskLevel;
use Sylius\Component\Mailer\Event\EmailSendEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Email;

/**
 * A submission sent from a datacenter, a VPN or Tor may be legitimate, so it is not rejected: the e-mail it sends to the
 * shop is flagged instead, for its recipient to tell at a glance and to filter.
 */
final class SuspiciousSubmissionSubjectListener
{
    public const IP_RISK_HEADER = 'X-Spam-Protection-Ip-Risk';

    /**
     * @param list<string> $emailCodes Sylius e-mails sent to the shop by a protected form
     */
    public function __construct(
        private IpReputation $ipReputation,
        private RequestStack $requestStack,
        private string $subjectPrefix,
        private array $emailCodes,
    ) {
    }

    public function __invoke(EmailSendEvent $event): void
    {
        $message = $event->getMessage();
        $ipAddress = $this->requestStack->getCurrentRequest()?->getClientIp();

        if (!$message instanceof Email || null === $ipAddress || !\in_array($event->getEmail()->getCode(), $this->emailCodes, true)) {
            return;
        }

        $riskLevel = $this->ipReputation->getRiskLevel($ipAddress);
        if (IpRiskLevel::Normal === $riskLevel) {
            return;
        }

        $message->subject($this->subjectPrefix . $message->getSubject());
        $message->getHeaders()->addTextHeader(self::IP_RISK_HEADER, $riskLevel->value);
    }
}
