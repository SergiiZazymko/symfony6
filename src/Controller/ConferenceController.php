<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ConferenceController
 * Namespace App\Controller
 */
class ConferenceController extends AbstractController
{
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

        return $this->render('conference/bulya.html.twig');

        return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
    }

    /**
     * @param Conference $conference
     * @param CommentRepository $commentRepository
     * @return Response
     */
    #[Route('/conference/{id}', name: 'conference')]
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository)
    {
        $offset = max(0, $request->query->getInt('offset'));

        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return $this->render('conference/show.html.twig', [
            'conference' => $conference,
            'comments' => $paginator,
            'previous' => $offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next' => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE)
        ]);
    }
}
