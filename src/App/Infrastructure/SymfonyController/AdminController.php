<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyController;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use LtcZelhem\App\Application\Admin\AdminListUserRepository;
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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    public function __construct(private readonly UserCredentialRepository $userCredentialRepository)
    {
    }

    #[Route('/admin/dashboard', name: 'adminDashboard', methods: ['GET'])]
    public function dashboard(AdminListUserRepository $adminListUserRepository): Response
    {
        return $this->render(
            'admin/dashboard.html.twig',
            [
                'users' => $adminListUserRepository->findAll(),
                'roles' => UserRole::allAsStringArray(),
                'locales' => Locale::allAsStringArray(),
            ]
        );
    }

    #[Route('/admin/remove-user/{emailAddress}', name: 'adminRemoveUser', methods: ['POST'])]
    public function adminRemoveUser(string $emailAddress, Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, UserCredentialRepository $userCredentialRepository): Response
    {
        $token = new CsrfToken('delete_user', $request->request->getString('_token'));

        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        $user = $this->getUser();
        if (!$user instanceof UserCredentials) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        if ($emailAddress === $user->getEmailAddress()->toString()) {
            $this->addFlash('error', $translator->trans('admin.cannot_remove_yourself'));

            return $this->redirectToRoute('adminDashboard');
        }

        $userCredentialRepository->remove(new EmailAddress($emailAddress));
        // todo: event UserWasRemoved => send mail about removal
        $this->addFlash('success', $translator->trans('admin.user_removed'));

        return $this->redirectToRoute('adminDashboard');
    }

    #[Route('/admin/add-user', name: 'adminAddUser', methods: ['POST'])]
    public function adminAddUser(Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, UserCredentialRepository $userCredentialRepository, MessageBusInterface $messageBus, Clock $clock): Response
    {
        $token = new CsrfToken('add_user', $request->request->getString('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        try {
            $emailAddress = new EmailAddress($request->request->getString('emailAddress'));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $translator->trans('security.invalid_email_address'));

            return $this->redirectToRoute('adminDashboard');
        }

        $postedRoles = json_decode($request->request->getString('roles'), true);
        if (!is_array($postedRoles)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        $rolesArray = [];
        foreach ($postedRoles as $role) {
            try {
                $rolesArray[] = UserRole::from($role);
            } catch (\Throwable) {
                $this->addFlash('error', $translator->trans('general.something_went_wrong'));

                return $this->redirectToRoute('adminDashboard');
            }
        }
        $roles = new Roles($rolesArray);
        try {
            $locale = Locale::from($request->request->getString('locale'));
        } catch (\InvalidArgumentException) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }
        $fullName = $request->request->getString('fullName');
        $createdAt = $clock->now();
        $passwordToken = PasswordToken::generate();
        $tokenExpiresAt = $clock->now()->modify('+1 day');

        try {
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
        } catch (UniqueConstraintViolationException) {
            $this->addFlash('error', $translator->trans('admin.user_already_exists'));

            return $this->redirectToRoute('adminDashboard');
        }

        $messageBus->dispatch(new NewUserWasCreated(
            $emailAddress,
            $fullName,
            $passwordToken,
            $tokenExpiresAt,
            $locale
        ));

        $this->addFlash('success', $translator->trans('admin.user_added'));

        return $this->redirectToRoute('adminDashboard');
    }

    #[Route('/admin/edit-user', name: 'adminEditUser', methods: ['POST'])]
    public function adminEditUser(Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, UserCredentialRepository $userCredentialRepository, MessageBusInterface $messageBus, Clock $clock): Response
    {
        try {
            $oldEmailAddress = new EmailAddress($request->request->getString('oldEmailAddress'));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $translator->trans('security.invalid_email_address'));

            return $this->redirectToRoute('adminDashboard');
        }

        try {
            $userToBeChanged = $userCredentialRepository->loadUserByEmailAddress($oldEmailAddress);
        } catch (UserNotFoundException) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        $token = new CsrfToken('edit_user', $request->request->getString('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        try {
            $emailAddress = new EmailAddress($request->request->getString('emailAddress'));
        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $translator->trans('security.invalid_email_address'));

            return $this->redirectToRoute('adminDashboard');
        }

        $postedRoles = json_decode($request->request->getString('roles'), true);
        if (!is_array($postedRoles)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('adminDashboard');
        }

        $rolesArray = [];
        foreach ($postedRoles as $role) {
            try {
                $rolesArray[] = UserRole::from($role);
            } catch (\Throwable) {
                $this->addFlash('error', $translator->trans('general.something_went_wrong'));

                return $this->redirectToRoute('adminDashboard');
            }
        }
        $roles = new Roles($rolesArray);
        $fullName = $request->request->getString('fullName');

        $userToBeChanged->adminChangeUserDetails(
            $fullName,
            $emailAddress,
            $roles
        );

        $this->userCredentialRepository->update($userToBeChanged, $oldEmailAddress);

        $this->addFlash('success', $translator->trans('admin.user_edited'));

        return $this->redirectToRoute('adminDashboard');
    }
}
