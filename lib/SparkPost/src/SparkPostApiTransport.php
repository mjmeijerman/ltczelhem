<?php

declare(strict_types=1);

namespace SparkPost;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\ParameterizedHeader;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class SparkPostApiTransport extends AbstractApiTransport
{
    public function __construct(private string $key, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        parent::__construct($client, $dispatcher, $logger);
    }

    public function __toString(): string
    {
        return 'sparkpost+api://';
    }

    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $payload = [
            'recipients' => $this->buildRecipients($envelope),
            'content'    => $this->buildContent($email, $envelope),
        ];

        $response = $this->client->request('POST', 'https://api.eu.sparkpost.com/api/v1/transmissions/', [
            'headers' => [
                'Authorization' => $this->key,
                'Content-Type'  => 'application/json',
            ],
            'json'    => $payload,
        ]);

        $this->handleError($response);

        return $response;
    }

    private function buildRecipients(Envelope $envelope): array
    {
        $result = [];
        foreach ($envelope->getRecipients() as $to) {
            $result[] = [
                'address' => array_filter([
                    'name'  => $to->getName(),
                    'email' => $to->getAddress(),
                ]),
            ];
        }

        return $result;
    }

    private function buildContent(Email $email, Envelope $envelope): array
    {
        $from = $envelope->getSender();

        return array_filter([
            'from'        => array_filter([
                'name'  => $from->getName(),
                'email' => $from->getAddress(),
            ]),
            'subject'     => $email->getSubject(),
            'text'        => $email->getTextBody(),
            'html'        => $email->getHtmlBody(),
            'replyTo'     => $email->getReplyTo(),
            'attachments' => $this->buildAttachments($email),
        ]);
    }

    private function buildAttachments(Email $email): array
    {
        $result = [];
        foreach ($email->getAttachments() as $attachment) {
            /** @var ParameterizedHeader $file */
            $file = $attachment->getPreparedHeaders()->get('Content-Disposition');
            /** @var ParameterizedHeader $type */
            $type = $attachment->getPreparedHeaders()->get('Content-Type');

            $result[] = [
                'name' => $file->getParameter('filename'),
                'type' => $type->getValue(),
                'data' => base64_encode($attachment->getBody()),
            ];
        }

        return $result;
    }

    private function handleError(ResponseInterface $response): void
    {
        if (200 === $response->getStatusCode()) {
            return;
        }

        /** @var array $data */
        $data = json_decode($response->getContent(false), true);
        $this->getLogger()->error('SparkPostApiTransport error response', $data);

        $errors = json_encode($data['errors']);
        if (!is_string($errors)) {
            throw new HttpTransportException('Weird errors', $response, $response->getStatusCode());
        }

        throw new HttpTransportException($errors, $response, $response->getStatusCode());
    }
}
