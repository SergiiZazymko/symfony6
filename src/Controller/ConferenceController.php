<?php

namespace App\Controller;

use App\Entity\Conference;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConferenceController extends AbstractController
{
    /**
     * @param Request $request
     * @param string $name
     * @return Response
     */
    #[Route(
        '/hello/{name}',
        name: 'homepage',
        defaults: ['name' => 'Sergii']
    )]
    public function index(Request $request, string $name = 'Sergii'): Response
    {
        $con = new Conference();
        $con->isInternational1 = 23;

        var_dump($con->isInternational1);
        die;

        $greet = '';

        if ($name) {
            $greet = sprintf('<h1>Hello %s!</h1>', htmlspecialchars($name));
        }

        return new Response(<<<EOF
<html>
    <body>
        $greet
        <img src="/images/under-construction.gif" />
    </body>
</html>
EOF
        );
    }
}
