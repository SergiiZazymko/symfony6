<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class AdminController
 * Namespace App\Controller
 */
#[Route('/admin')]
class AdminController extends AbstractController
{
    /**
     * AdminController constructor.
     *
     * @param Environment $twig
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     */
    public function __construct(
        private readonly Environment            $twig,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface    $bus,
    ) {
    }

    /**
     * @param Request $request
     * @param Comment $comment
     * @param WorkflowInterface $commentStateMachine
     * @return Response
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/comment/review/{id}', name: 'review_comment')]
    public function reviewComment(Request $request, Comment $comment, WorkflowInterface $commentStateMachine): Response
    {
        $accepted = !$request->query->get('reject');

        if ($commentStateMachine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif ($commentStateMachine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the right state.');
        }

        $commentStateMachine->apply($comment, $transition);
        $this->entityManager->flush();

        if ($accepted) {
            $this->bus->dispatch(new CommentMessage($comment->getId()));
        }

        return new Response($this->twig->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment' => $comment,
        ]));
    }
}
