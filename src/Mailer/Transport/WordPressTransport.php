<?php

namespace Rareloop\Lumberjack\Mailer\Transport;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\RuntimeException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class WordPressTransport extends AbstractTransport
{
    public function __toString(): string
    {
        return '';
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function doSend(SentMessage $message): void
    {
        $this->getLogger()->debug(\sprintf('Email transport "%s" starting', __CLASS__));

        try {
            $email = MessageConverter::toEmail($message->getOriginalMessage());
        } catch (\Exception $e) {
            throw new RuntimeException(\sprintf('Unable to send message with the "%s" transport: ', __CLASS__) . $e->getMessage(), 0, $e);
        }

        /** @var Envelope $envelope */
        $envelope = $message->getEnvelope();

        $email_to = $this->stringifyAddresses($this->getRecipients($email, $envelope));
        $email_html = $email->getHtmlBody();
        $email_subject = $email->getSubject();
        $email_headers = $email->getPreparedHeaders();
        $email_headers->remove('to');
        $email_headers->remove('subject');
        $email_headers->addHeader('content-type', 'text/html');

        $result = \wp_mail($email_to, $email_subject, $email_html, $email_headers->toArray());

        $this->getLogger()->debug(\sprintf('Email transport "%s" stopped', __CLASS__));
    }

    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return \array_filter($envelope->getRecipients(), function (Address $address) use ($email) {
            return \in_array($address, \array_merge($email->getCc(), $email->getBcc()), true) === false;
        });
    }

    private function prepareAttachments(Email $email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();
            $filename = $headers->getHeaderParameter('Content-Disposition', 'filename');

            $att = [
                'content' => \str_replace("\r\n", '', $attachment->bodyToString()),
                'name'    => $filename,
            ];

            $attachments[] = $att;
        }

        return $attachments;
    }

}
