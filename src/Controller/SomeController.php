<?php

declare(strict_types=1);

namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SomeController extends AbstractController
{
    #[Route('/')]
    public function number(): Response
    {
        return $this->render('base.html.twig');
    }
}
