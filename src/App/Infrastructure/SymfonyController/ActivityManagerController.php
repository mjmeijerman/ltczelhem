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

#[IsGranted('ROLE_ACTIVITY_MANAGER')]
final class ActivityManagerController extends AbstractController
{
    public function __construct()
    {
    }

    #[Route('/activity-manager/dashboard', name: 'activityManagerDashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render(
            'activity_manager/dashboard.html.twig'
        );
    }
}
