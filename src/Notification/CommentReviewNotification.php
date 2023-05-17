<?php

declare(strict_types=1);


namespace App\Notification;

use App\Entity\Comment;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

/**
 * Class CommentReviewNotification
 * Namespace App\Notification
 */
class CommentReviewNotification extends Notification implements EmailNotificationInterface
{
    /**
     * CommentReviewNotification constructor.
     *
     * @param Comment $comment
     */
    public function __construct(
        private Comment $comment,
    ) {
        parent::__construct('New comment posted');
    }

    /**
     * @param EmailRecipientInterface $recipient
     * @param string|null $transport
     * @return EmailMessage|null
     */
    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?EmailMessage
    {
        $message = EmailMessage::fromNotification($this, $recipient, $transport);
        $message->getMessage()
            ->htmlTemplate('emails/comment_notification.html.twig')
            ->context(['comment' => $this->comment])
        ;

        return $message;
    }
}
