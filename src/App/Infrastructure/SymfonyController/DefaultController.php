<?php
declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyController;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DefaultController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function homepage(): Response
    {
        return $this->render('default/homepage.html.twig');
    }
}