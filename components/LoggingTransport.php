<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\components;

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Throwable;

/**
 * Decorates the mailer transport to keep the error message of the last failed sending,
 * as `yii\symfonymailer\Mailer::sendMessage()` catches the exception and only returns `false`
 */
class LoggingTransport implements TransportInterface
{
    public ?string $lastError = null;

    public function __construct(public readonly TransportInterface $transport)
    {
    }

    /**
     * @inheritdoc
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $this->lastError = null;
        try {
            return $this->transport->send($message, $envelope);
        } catch (Throwable $e) {
            $this->lastError = $e::class . ': ' . $e->getMessage();
            throw $e;
        }
    }

    public function __toString(): string
    {
        return (string)$this->transport;
    }
}
