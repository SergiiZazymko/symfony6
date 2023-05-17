<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use App\Service\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConferenceController
 * Namespace App\Controller
 */
class ConferenceController extends AbstractController
{
    /**
     * ConferenceController constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     */
    public function __construct(
        private EntityManagerInterface       $entityManager,
        private readonly MessageBusInterface $bus,
    ) {
    }

    /**
     * @param Request $request
     * @param ConferenceRepository $conferenceRepository
     * @return Response
     */
    #[Route('/', name: 'homepage')]
    public function index(
        Request              $request,
        ConferenceRepository $conferenceRepository
    ): Response {

        //return $this->render('conference/bulya.html.twig');

        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ])->setSharedMaxAge(3600);
    }

    /**
     * @param Request $request
     * @param Conference $conference
     * @param CommentRepository $commentRepository
     * @param NotifierInterface $notifier
     * @param string $photoDir
     * @return Response
     * @throws \Exception
     */
    #[Route('/conference/{slug}', name: 'conference')]
    public function show(
        Request                           $request,
        Conference                        $conference,
        CommentRepository                 $commentRepository,
        NotifierInterface                 $notifier,
        //SpamChecker                       $spamChecker,
        #[Autowire('%photo_dir%')] string $photoDir
    ) {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6)) . '.' . $photo->guessExtension();
                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $e) {
                    // unable to upload the photo, give up
                }
                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);

            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            //if (2 === $spamChecker->getSpamScore($comment, $context)) {
            //    throw new \RuntimeException('Blatant spam, go away!');
            //}

            $this->entityManager->flush();

            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));

            $notifier->send(new Notification(
                'Thank you for the feedback; your comment will be posted after moderation.',
                ['browser'],
            ));

            $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $notifier->send(new Notification(
                'Can you check your submission? There are some problems with it.',
                ['browser'],
            ));
        }

        $offset = max(0, $request->query->getInt('offset'));

        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form' => $form->createView(),
        ]);
    }
}
