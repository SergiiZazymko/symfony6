<?php

namespace App\EventSubscriber;

use App\Repository\ConferenceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * Class TwigEventSubscriber
 * Namespace App\EventSubscriber
 */
class TwigEventSubscriber implements EventSubscriberInterface
{
    /**
     * TwigEventSubscriber constructor.
     *
     * @param Environment $twig
     * @param ConferenceRepository $conferenceRepository
     */
    public function __construct(
        private Environment          $twig,
        private ConferenceRepository $conferenceRepository
    ) {
    }

    /**
     * @param ControllerEvent $event
     * @return void
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $this->twig->addGlobal('conferences', $this->conferenceRepository->findAll());
    }

    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }
}
