<?php

namespace App\Controller;

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
        $greet = '';

        if ($name) {
            $greet = sprintf('<h1>Hello %s!</h1>', htmlspecialchars($name));
        }

        return new Response(<<<EOF
<html>
    $greet
    <body>
        <img src="/images/under-construction.gif" />
    </body>
</html>
EOF
        );
    }
}
