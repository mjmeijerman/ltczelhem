<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyController;

use LtcZelhem\App\Application\SuperAdmin\SuperAdminListUserRepository;
use LtcZelhem\App\Domain\EmailAddress;
use LtcZelhem\App\Domain\Events\NewUserWasCreated;
use LtcZelhem\App\Domain\Security\PasswordToken;
use LtcZelhem\App\Domain\Security\Roles;
use LtcZelhem\App\Domain\Security\UserCredentialRepository;
use LtcZelhem\App\Domain\Security\UserCredentials;
use LtcZelhem\App\Domain\Security\UserRole;
use LtcZelhem\App\Domain\Utilities\Clock;
use LtcZelhem\App\Domain\Utilities\Locale;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class SuperAdminController extends AbstractController
{
    public function __construct(private readonly UserCredentialRepository $userCredentialRepository)
    {
    }

    #[Route('/super-admin/dashboard', name: 'superAdminDashboard', methods: ['GET'])]
    public function dashboard(SuperAdminListUserRepository $superAdminListUserRepository): Response
    {
        return $this->render(
            'super_admin/dashboard.html.twig',
            [
                'users' => $superAdminListUserRepository->findAll(),
                'roles' => UserRole::allAsStringArray(),
                'locales' => Locale::allAsStringArray(),
            ]
        );
    }

    #[Route('/super-admin/remove-user/{emailAddress}', name: 'superAdminRemoveUser', methods: ['POST'])]
    public function superAdminRemoveUser(string $emailAddress, Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, UserCredentialRepository $userCredentialRepository): Response
    {
        $token = new CsrfToken('delete_user', $request->request->get('_token'));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('superAdminDashboard');
        }

        $user = $this->getUser();
        if (!$user instanceof UserCredentials) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('superAdminDashboard');
        }

        if ($emailAddress === $user->emailAddress->toString()) {
            $this->addFlash('error', $translator->trans('super_admin.cannot_remove_yourself'));

            return $this->redirectToRoute('superAdminDashboard');
        }

        $userCredentialRepository->remove(new EmailAddress($emailAddress));
        $this->addFlash('success', $translator->trans('super_admin.user_removed'));

        return $this->redirectToRoute('superAdminDashboard');
    }

    #[Route('/super-admin/add-user', name: 'superAdminAddUser', methods: ['POST'])]
    public function superAdminAddUser(Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, UserCredentialRepository $userCredentialRepository, MessageBusInterface $messageBus, Clock $clock): Response
    {
        $token = new CsrfToken('add_user', $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('superAdminDashboard');
        }

        try {
            $emailAddress = new EmailAddress($request->request->get('emailAddress'));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $translator->trans('security.invalid_email_address'));

            return $this->redirectToRoute('superAdminDashboard');
        }

        $postedRoles = json_decode($request->request->get('roles'), true);
        $rolesArray = [];
        foreach ($postedRoles as $role) {
            try {
                $rolesArray[] = UserRole::from($role);
            } catch (\InvalidArgumentException) {
                $this->addFlash('error', $translator->trans('general.something_went_wrong'));

                return $this->redirectToRoute('superAdminDashboard');
            }
        }
        $roles = new Roles($rolesArray);
        try {
            $locale = Locale::from($request->request->get('locale'));
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('superAdminDashboard');
        }
        $fullName = $request->request->get('fullName');
        $createdAt = $clock->now();
        $passwordToken = PasswordToken::generate();
        $tokenExpiresAt = $clock->now()->modify('+1 day');

        $this->userCredentialRepository->insert(new UserCredentials(
            $roles,
            $emailAddress,
            null,
            $passwordToken,
            $tokenExpiresAt,
            $createdAt,
            $fullName,
            $locale
        ));

        $messageBus->dispatch(new NewUserWasCreated(
            $emailAddress,
            $fullName,
            $passwordToken,
            $tokenExpiresAt,
            $locale
        ));

        $this->addFlash('success', $translator->trans('super_admin.user_added'));

        return $this->redirectToRoute('superAdminDashboard');
    }
}
