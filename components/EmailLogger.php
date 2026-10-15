<?php

/**
 * Email Log
 * @link https://www.cuzy.app
 * @license https://www.cuzy.app/cuzy-license
 * @author [Marc FARRE](https://marc.fun)
 */

namespace humhub\modules\emailLog\components;

use humhub\components\mail\Mailer;
use humhub\modules\emailLog\models\Configuration;
use humhub\modules\emailLog\models\EmailLog;
use humhub\modules\emailLog\Module;
use humhub\modules\emailLog\notifications\EmailSendingFailed;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\User;
use Yii;
use yii\log\EmailTarget;
use yii\mail\MailEvent;
use yii\mail\MessageInterface;
use yii\symfonymailer\Message;

class EmailLogger
{
    /**
     * Minimum delay between 2 notifications to the system administrators
     */
    public const NOTIFY_ADMINS_INTERVAL = 3600;

    /**
     * @var array Recipients of the messages being sent, indexed by message object ID,
     * as HumHub's Mailer removes the suppressed recipients from the message before sending
     */
    private static array $recipients = [];

    public static function beforeSend(MailEvent $event): void
    {
        $mailer = $event->sender;
        $message = $event->message;

        if (static::isLogTargetMailer($mailer)) {
            return;
        }

        if (!$event->isValid) {
            // Sending has been cancelled by a previous event handler: the after send event won't be triggered
            static::log($message, static::getRecipients($message), EmailLog::STATUS_CANCELLED);
            return;
        }

        if ($mailer instanceof \yii\symfonymailer\Mailer) {
            $transport = $mailer->getTransport();
            if ($transport instanceof LoggingTransport) {
                $transport->lastError = null;
            } else {
                $mailer->setTransport(new LoggingTransport($transport));
            }
        }

        static::$recipients[spl_object_id($message)] = static::getRecipients($message);
    }

    public static function afterSend(MailEvent $event): void
    {
        $mailer = $event->sender;
        $message = $event->message;

        if (static::isLogTargetMailer($mailer)) {
            return;
        }

        $objectId = spl_object_id($message);
        $recipients = static::$recipients[$objectId] ?? static::getRecipients($message);
        unset(static::$recipients[$objectId]);

        // HumHub's Mailer doesn't send emails to suppressed recipients (e.g. demo users) but returns true
        $suppressedRecipients = $mailer instanceof Mailer ? $mailer->surpressedRecipients : [];
        $sent = $suppressed = [];
        foreach ($recipients as $email => $type) {
            if (static::isSuppressed($email, $suppressedRecipients)) {
                $suppressed[$email] = $type;
            } else {
                $sent[$email] = $type;
            }
        }
        static::log($message, $suppressed, EmailLog::STATUS_SUPPRESSED);

        if ($event->isSuccessful) {
            static::log($message, $sent, EmailLog::STATUS_SUCCESS);
            return;
        }

        $transport = $mailer instanceof \yii\symfonymailer\Mailer ? $mailer->getTransport() : null;
        $error = $transport instanceof LoggingTransport && $transport->lastError
            ? $transport->lastError
            : Yii::t('EmailLogModule.base', 'Unknown error (see the HumHub logs)');
        $logs = static::log($message, $sent, EmailLog::STATUS_FAILED, $error);
        static::handleFailure($message, $sent, $error, $logs);
    }

    /**
     * @param MessageInterface $message
     * @param array $recipients email => recipient type
     * @param string $status
     * @param string|null $error
     * @return EmailLog[]
     */
    protected static function log(MessageInterface $message, array $recipients, string $status, ?string $error = null): array
    {
        if (!$recipients) {
            return [];
        }

        $configuration = Module::getInstance()->getConfiguration();
        [$htmlBody, $textBody] = static::getContent($message, $configuration);
        $subject = mb_substr((string)$message->getSubject(), 0, 255);

        $userIds = User::find()
            ->select(['id', 'email'])
            ->where(['email' => array_keys($recipients)])
            ->asArray()
            ->all();
        $userIdsByEmail = [];
        foreach ($userIds as $user) {
            $userIdsByEmail[mb_strtolower($user['email'])] = (int)$user['id'];
        }

        $logs = [];
        foreach ($recipients as $email => $type) {
            $log = new EmailLog([
                'user_id' => $userIdsByEmail[mb_strtolower($email)] ?? null,
                'email' => mb_substr($email, 0, 255),
                'recipient_type' => $type,
                'subject' => $subject,
                'html_body' => $htmlBody,
                'text_body' => $textBody,
                'status' => $status,
                'error' => $error,
            ]);
            if ($log->save()) {
                $logs[] = $log;
            } else {
                Yii::error('Email Log module: cannot save log for ' . $email . ': ' . print_r($log->getErrors(), true), 'email-log');
            }
        }
        return $logs;
    }

    /**
     * @param MessageInterface $message
     * @param array $recipients
     * @param string $error
     * @param EmailLog[] $logs
     */
    protected static function handleFailure(MessageInterface $message, array $recipients, string $error, array $logs): void
    {
        $module = Module::getInstance();
        $configuration = $module->getConfiguration();

        if ($configuration->logErrors) {
            Yii::error(sprintf(
                'Email "%s" could not be sent to %s: %s%s',
                $message->getSubject(),
                implode(', ', array_keys($recipients)),
                $error,
                $logs ? ' (email log ID: ' . $logs[0]->id . ')' : '',
            ), 'email-log');
        }

        if (!$configuration->notifyAdmins || !$logs) {
            return;
        }

        // Throttle notifications; the setting is updated before sending,
        // as the notification can itself be sent by email and fail again
        $lastNotification = (int)$module->settings->get('lastAdminNotificationTime');
        if (time() - $lastNotification < static::NOTIFY_ADMINS_INTERVAL) {
            return;
        }
        $module->settings->set('lastAdminNotificationTime', time());

        $adminGroup = Group::getAdminGroup();
        if ($adminGroup !== null) {
            EmailSendingFailed::instance()
                ->about($logs[0])
                ->sendBulk($adminGroup->getUsers()->active());
        }
    }

    /**
     * @return array email => recipient type
     */
    protected static function getRecipients(MessageInterface $message): array
    {
        $recipients = [];
        foreach ([
            EmailLog::RECIPIENT_TYPE_TO => $message->getTo(),
            EmailLog::RECIPIENT_TYPE_CC => $message->getCc(),
            EmailLog::RECIPIENT_TYPE_BCC => $message->getBcc(),
        ] as $type => $addresses) {
            foreach ((array)$addresses as $key => $value) {
                // Addresses can be either `[email => name]` or `[email]`
                $email = is_string($key) ? $key : (string)$value;
                if ($email !== '' && !isset($recipients[$email])) {
                    $recipients[$email] = $type;
                }
            }
        }
        return $recipients;
    }

    /**
     * @return array [html body, text body]
     */
    protected static function getContent(MessageInterface $message, Configuration $configuration): array
    {
        if (
            $configuration->contentStorage === Configuration::CONTENT_STORAGE_NONE
            || !$message instanceof Message
        ) {
            return [null, null];
        }

        $htmlBody = $configuration->contentStorage === Configuration::CONTENT_STORAGE_FULL
            ? $message->getHtmlBody() ?: null
            : null;
        $textBody = $message->getTextBody() ?: null;

        if ($configuration->redactTokens) {
            $parameters = $configuration->getRedactedUrlParameterList();
            $htmlBody = static::redact($htmlBody, $parameters);
            $textBody = static::redact($textBody, $parameters);
        }

        return [$htmlBody, $textBody];
    }

    /**
     * Replaces the values of the URL query parameters (e.g. `?token=xxx` or `&amp;token=xxx`)
     */
    public static function redact(?string $content, array $parameters): ?string
    {
        if ($content === null || $content === '' || !$parameters) {
            return $content;
        }

        $parameters = implode('|', array_map(static fn($parameter) => preg_quote((string)$parameter, '/'), $parameters));

        return preg_replace(
            '/((?:[?&;]|%26|%3F)(?:' . $parameters . ')(?:=|%3D))[^&\s"\'<>#]+/i',
            '$1' . EmailLog::REDACTED,
            $content,
        ) ?? $content;
    }

    /**
     * Emails sent by log targets (e.g. errors sent to the webmaster) are not logged,
     * to avoid an infinite loop if sending fails and an error is logged
     */
    protected static function isLogTargetMailer($mailer): bool
    {
        foreach (Yii::$app->getLog()->targets as $target) {
            if ($target instanceof EmailTarget && $target->mailer === $mailer) {
                return true;
            }
        }
        return false;
    }

    /**
     * Same as private method `humhub\components\mail\Mailer::isRecipientSurpressed()`
     */
    protected static function isSuppressed(string $email, array $suppressedRecipients): bool
    {
        $email = strtolower($email);
        foreach ($suppressedRecipients as $suppressed) {
            if (str_contains($email, (string)$suppressed)) {
                return true;
            }
        }
        return false;
    }
}
