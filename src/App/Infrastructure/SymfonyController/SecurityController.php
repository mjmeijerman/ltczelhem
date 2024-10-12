<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyController;

use Assert\AssertionFailedException;
use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Events\NewPasswordWasSet;
use LtcZelhem\App\Domain\Events\PasswordResetWasRequested;
use LtcZelhem\App\Domain\Security\PasswordToken;
use LtcZelhem\App\Domain\Security\UserCredentialRepository;
use LtcZelhem\App\Domain\Security\UserCredentials;
use LtcZelhem\App\Domain\Security\UserRole;
use LtcZelhem\App\Domain\Utilities\Clock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/login', name: 'loginRoute', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('redirectToCorrectLoginPage');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(): void
    {
    }

    #[Route('/security/set-password/{passwordToken}', name: 'setPasswordForm', methods: ['GET'])]
    public function setPasswordForm(string $passwordToken, UserCredentialRepository $userCredentialRepository, Clock $clock, TranslatorInterface $translator): Response
    {
        try {
            $passwordToken = new PasswordToken($passwordToken);
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', $translator->trans('security.invalid_token'));

            return $this->redirectToRoute('getNewCredentials');
        }

        $user = $userCredentialRepository->findByPasswordToken(
            $passwordToken,
            $clock
        );

        if (!$user) {
            $this->addFlash('error', $translator->trans('security.invalid_token'));

            return $this->redirectToRoute('getNewCredentials');
        }

        if ($translator instanceof LocaleAwareInterface) {
            $translator->setLocale($user->locale()->value);
        }

        return $this->render('security/set_password.html.twig');
    }

    #[Route('/security/set-password/{passwordToken}', name: 'setPassword', methods: ['POST'])]
    public function setPassword(Request $request, string $passwordToken, UserCredentialRepository $userCredentialRepository, Clock $clock, TranslatorInterface $translator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordHasherInterface $userPasswordHasher, MessageBusInterface $messageBus): Response
    {
        try {
            $passwordToken = new PasswordToken($passwordToken);
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', $translator->trans('security.invalid_token'));

            return $this->redirectToRoute('getNewCredentials');
        }

        $user = $userCredentialRepository->findByPasswordToken(
            $passwordToken,
            $clock
        );

        if (!$user) {
            $this->addFlash('error', $translator->trans('security.invalid_token'));

            return $this->redirectToRoute('getNewCredentials');
        }

        if ($translator instanceof LocaleAwareInterface) {
            $translator->setLocale($user->locale()->value);
        }

        try {
            $postedCsrfToken = $request->request->get('_csrf_token');
            if (!is_string($postedCsrfToken)) {
                throw new \LogicException('Expected a string csrf token in the post data');
            }

            $token = new CsrfToken('new-password', $postedCsrfToken);
            if (!$csrfTokenManager->isTokenValid($token)) {
                throw new \LogicException('Invalid csrf token');
            }
        } catch (\LogicException) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('setPasswordForm', ['passwordToken' => $passwordToken]);
        }

        if ($request->request->get('password_again') !== $request->request->get('password')) {
            $this->addFlash('error', $translator->trans('security.passwords_not_equal'));

            return $this->redirectToRoute('setPasswordForm', ['passwordToken' => $passwordToken]);
        }

        $password = $request->request->get('password');
        if (!is_string($password)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('setPasswordForm', ['passwordToken' => $passwordToken]);
        }

        $user->setPassword($password, $userPasswordHasher);
        $userCredentialRepository->update($user);

        $messageBus->dispatch(new NewPasswordWasSet($user->emailAddress, $user->fullName));
        $this->addFlash('success', $translator->trans('security.password_set'));

        return $this->redirectToRoute('loginRoute');
    }

    #[IsGranted('ROLE_USER')]
    #[Route('/login-redirect/', name: 'redirectToCorrectLoginPage', methods: ['GET'])]
    public function redirectToCorrectLoginPage(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof UserCredentials) {
            throw new \LogicException('Expected a logged in user, but none was found');
        }

        if ($user->roles->count() > 1) {
            die('select role');
//            return $this->redirectToRoute('selectRole');
        }

        $role = $user->roles->first();
        switch ($role->value) {
            case UserRole::ROLE_SUPER_ADMIN->value:
                die('superAdminDashboard');
//                return $this->redirectToRoute('superAdminDashboard');
            case UserRole::ROLE_ADMIN->value:
                die('adminDashboard');
//                return $this->redirectToRoute('adminDashboard');
            case UserRole::ROLE_TRAINER->value:
                die('trainerDashboard');
//                return $this->redirectToRoute('trainerDashboard');
            default:
                throw new \LogicException('Unknown role');
        }
    }

    #[Route('/security/getNewCredentials/', name: 'getNewCredentials', methods: ['GET'])]
    public function newCredentials(Request $request): Response
    {
        return $this->render('security/reset_password.html.twig');
    }

    #[Route('/security/getNewCredentials/', name: 'requestNewCredentials', methods: ['POST'])]
    public function requestNewCredentials(Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, UserCredentialRepository $userCredentialRepository, Clock $clock, MessageBusInterface $messageBus): Response
    {
        try {
            $postedCsrfToken = $request->request->get('_csrf_token');
            if (!is_string($postedCsrfToken)) {
                throw new \LogicException('Expected a string csrf token in the post data');
            }

            $token = new CsrfToken('request-new-password', $postedCsrfToken);
            if (!$csrfTokenManager->isTokenValid($token)) {
                throw new \LogicException('Invalid csrf token');
            }
        } catch (\LogicException) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('getNewCredentials');
        }

        $emailAddress = $request->request->get('emailAddress');
        if (!is_string($emailAddress)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('getNewCredentials');
        }

        try {
            $emailAddress = new EmailAddress($emailAddress);
        } catch (AssertionFailedException) {
            $this->addFlash('error', $translator->trans('security.invalid_email_address'));

            return $this->redirectToRoute('getNewCredentials');
        }

        try {
            $user = $userCredentialRepository->loadUserByEmailAddress($emailAddress);
        } catch (UserNotFoundException) {
            $this->addFlash('success', $translator->trans('security.password_reset_requested'));

            return $this->redirectToRoute('homepage');
        }

        $passwordToken = PasswordToken::generate();
        $passwordTokenExpiresAt = $clock->now()->modify('+1 day');
        $user->setNewPasswordToken($passwordToken, $passwordTokenExpiresAt);
        $userCredentialRepository->update($user);

        $messageBus->dispatch(
            new PasswordResetWasRequested(
                $user->emailAddress,
                $user->fullName,
                $passwordToken,
                $passwordTokenExpiresAt,
                $user->locale()
            )
        );
        $this->addFlash('success', $translator->trans('security.password_reset_requested'));

        return $this->redirectToRoute('homepage');
    }
}
