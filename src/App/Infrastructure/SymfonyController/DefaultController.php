<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyController;

use LtcZelhem\App\Domain\Security\UserCredentialRepository;
use LtcZelhem\App\Domain\Security\UserCredentials;
use LtcZelhem\App\Domain\Utilities\Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class DefaultController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/', name: 'homepage')]
    public function homepage(): Response
    {
        return $this->render('default/homepage.html.twig');
    }

    #[Route('/change-language/{language}', name: 'changeLanguage')]
    public function changeLanguage(Request $request, TranslatorInterface $translator, string $language, UserCredentialRepository $userCredentialRepository): Response
    {
        // todo: not working in production, why?
        $referer = $request->headers->get('referer');
        try {
            $locale = Locale::from($language);
        } catch (\Throwable) {
            $this->addFlash('error', $translator->trans('language_not_found', ['%language%' => $language]));

            if ($referer) {
                return $this->redirect($referer);
            }

            return $this->redirectToRoute('homepage');
        }

        $request->getSession()->set('_locale', $language);
        $this->addFlash('success', $translator->trans('language_changed', ['%language%' => $language], locale: $language));

        $user = $this->getUser();
        if ($user instanceof UserCredentials) {
            $user->changeLocale($locale);
            $userCredentialRepository->update($user);
        }


        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('homepage');
    }
}
