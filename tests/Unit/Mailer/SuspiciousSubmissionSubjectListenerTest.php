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

namespace Tests\Atriametal\SyliusSpamProtectionPlugin\Unit\Mailer;

use Atriametal\SyliusSpamProtectionPlugin\Mailer\SuspiciousSubmissionSubjectListener;
use FlorentinGarnier\SpamProtection\IpReputation\IpRangeSet;
use FlorentinGarnier\SpamProtection\IpReputation\IpReputation;
use FlorentinGarnier\SpamProtection\IpReputation\IpReputationList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Mailer\Event\EmailSendEvent;
use Sylius\Component\Mailer\Model\Email as SyliusEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mime\Email;

#[CoversClass(SuspiciousSubmissionSubjectListener::class)]
final class SuspiciousSubmissionSubjectListenerTest extends TestCase
{
    private const HOSTING_IP = '203.0.113.7';

    private const TOR_IP = '198.51.100.9';

    private const NORMAL_IP = '192.0.2.1';

    private string $listPath;

    protected function setUp(): void
    {
        $this->listPath = sys_get_temp_dir() . '/spam_protection_' . bin2hex(random_bytes(6)) . '/ip_reputation.php';
        (new IpReputationList($this->listPath))->save([
            'hosting' => IpRangeSet::fromCidrs(['203.0.113.0/24']),
            'tor' => IpRangeSet::fromCidrs([self::TOR_IP . '/32']),
        ]);
    }

    protected function tearDown(): void
    {
        unlink($this->listPath);
        rmdir(\dirname($this->listPath));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function riskyAddresses(): iterable
    {
        yield 'datacenter or VPN' => [self::HOSTING_IP, 'hosting'];
        yield 'Tor exit node' => [self::TOR_IP, 'tor'];
    }

    #[DataProvider('riskyAddresses')]
    public function testItFlagsTheContactRequestSentFromARiskyAddress(string $ipAddress, string $riskLevel): void
    {
        $message = $this->send('contact_request', $ipAddress);

        self::assertSame('[Spam?] Contact request', $message->getSubject());
        self::assertSame($riskLevel, $message->getHeaders()->getHeaderBody(SuspiciousSubmissionSubjectListener::IP_RISK_HEADER));
    }

    public function testItLeavesTheContactRequestSentFromANormalAddressUntouched(): void
    {
        $message = $this->send('contact_request', self::NORMAL_IP);

        self::assertSame('Contact request', $message->getSubject());
        self::assertFalse($message->getHeaders()->has(SuspiciousSubmissionSubjectListener::IP_RISK_HEADER));
    }

    /**
     * The other e-mails are sent to the customer, who must not read that the shop suspects them.
     */
    public function testItLeavesTheOtherEmailsUntouched(): void
    {
        $message = $this->send('order_confirmation', self::HOSTING_IP);

        self::assertSame('Contact request', $message->getSubject());
    }

    public function testItLeavesTheEmailsSentOutsideARequestUntouched(): void
    {
        $message = $this->send('contact_request', null);

        self::assertSame('Contact request', $message->getSubject());
    }

    private function send(string $emailCode, ?string $ipAddress): Email
    {
        $requestStack = new RequestStack();
        if (null !== $ipAddress) {
            $requestStack->push(Request::create('/en_US/contact', 'POST', server: ['REMOTE_ADDR' => $ipAddress]));
        }

        $email = new SyliusEmail();
        $email->setCode($emailCode);
        $message = (new Email())->subject('Contact request');

        $listener = new SuspiciousSubmissionSubjectListener(new IpReputation(new IpReputationList($this->listPath)), $requestStack, '[Spam?] ', ['contact_request']);
        $listener(new EmailSendEvent($message, $email, []));

        return $message;
    }
}
