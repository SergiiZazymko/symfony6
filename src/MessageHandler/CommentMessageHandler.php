<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Class CommentMessageHandler
 * Namespace App\MessageHandler
 */
#[AsMessageHandler]
class CommentMessageHandler
{
    /**
     * CommentMessageHandler constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param SpamChecker $spamChecker
     * @param CommentRepository $commentRepository
     * @param MessageBusInterface $bus
     * @param WorkflowInterface $commentStateMachine
     * @param MailerInterface $mailer
     * @param string $adminEmail
     * @param NotifierInterface $notifier
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        private EntityManagerInterface              $entityManager,
        private SpamChecker                         $spamChecker,
        private CommentRepository                   $commentRepository,
        private MessageBusInterface                 $bus,
        private WorkflowInterface                   $commentStateMachine,
        private MailerInterface                     $mailer,
        #[Autowire('%admin_email%')] private string $adminEmail,
        private NotifierInterface                   $notifier,
        private ?LoggerInterface                    $logger = null,
    ) {
    }

    /**
     * @param CommentMessage $message
     * @return void
     * @throws TransportExceptionInterface
     */
    public function __invoke(CommentMessage $message)
    {
        $comment = $this->commentRepository->find($message->getId());
        if (!$comment) {
            return;
        }

        //if (2 === $this->spamChecker->getSpamScore($comment, $message->getContext())) {
        //    $comment->setState('spam');
        //} else {
        //    $comment->setState('published');
        //}

        //$this->mailer->send((new NotificationEmail())
        //    ->subject('New comment posted')
        //    ->htmlTemplate('emails/comment_notification.html.twig')
        //    ->from($this->adminEmail)
        //    ->to($this->adminEmail)
        //    ->context(['comment' => $comment])
        //);

        $this->notifier->send(new CommentReviewNotification($comment), ...$this->notifier->getAdminRecipients());

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $message->getContext());
            $transition = match ($score) {
                2 => 'reject_spam',
                1 => 'might_be_spam',
                default => 'accept',
            };
            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
            $this->bus->dispatch($message);
        } elseif ($this->commentStateMachine->can($comment, 'publish') || $this->commentStateMachine->can($comment, 'publish_ham')) {
//            $this->commentStateMachine->apply($comment, $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham');
//            $this->entityManager->flush();
            $this->mailer->send((new NotificationEmail())
                ->subject('New comment posted')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment' => $comment])
            );
        } elseif ($this->logger) {
            $this->logger->debug('Dropping comment message', [
                'comment' => $comment->getId(),
                'state' => $comment->getState(),
            ]);
        }

        $this->entityManager->flush();
    }
}
