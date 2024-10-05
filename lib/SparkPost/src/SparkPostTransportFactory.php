<?php

declare(strict_types=1);

namespace SparkPost;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class SparkPostTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $user = $this->getUser($dsn);

        if ('sparkpost+api' === $scheme) {
            return new SparkPostApiTransport($user, $this->client, $this->dispatcher, $this->logger);
        }

        throw new UnsupportedSchemeException($dsn, 'sparkpost', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['sparkpost+api'];
    }
}
