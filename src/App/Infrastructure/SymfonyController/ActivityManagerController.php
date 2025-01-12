<?php

declare(strict_types=1);

namespace LtcZelhem\App\Infrastructure\SymfonyController;

use LtcZelhem\App\Application\ActivityManager\PossibleActivityManagerRepository;
use LtcZelhem\App\Domain\Activity\Activity;
use LtcZelhem\App\Domain\Activity\ActivityId;
use LtcZelhem\App\Domain\Activity\ActivityManager;
use LtcZelhem\App\Domain\Activity\ActivityManagers;
use LtcZelhem\App\Domain\Activity\ActivityRepository;
use LtcZelhem\App\Domain\Activity\ActivityStatus;
use LtcZelhem\App\Domain\Utilities\Clock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
    public function dashboard(PossibleActivityManagerRepository $possibleActivityManagerRepository): Response
    {
        return $this->render(
            'activity_manager/dashboard.html.twig',
            [
                'possibleActivityManagers' => $possibleActivityManagerRepository->findAll(),
            ]
        );
    }

    #[Route('/activity-manager/create-new-activity', name: 'activityManagerCreateNewActivity', methods: ['POST'])]
    public function createNewActivity(Request $request, CsrfTokenManagerInterface $csrfTokenManager, TranslatorInterface $translator, Clock $clock, ActivityRepository $activityRepository): Response
    {
        $token = new CsrfToken('add_activity', $request->request->getString('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('activityManagerDashboard');
        }

        $name = $request->request->getString('activityName');
        if (empty($name)) {
            $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.name_cannot_be_empty'));
        }

        $descriptionNl = $request->request->getString('descriptionNl');
        if (empty($descriptionNl)) {
            $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.dutch_description_cannot_be_empty'));
        }

        $descriptionEn = $request->request->get('descriptionEn') ?: null;
        $startDate = $request->request->get('firstActivityDate') ?: null;
        if (!empty($startDate)) {
            try {
                $startDate = new \DateTimeImmutable($startDate);
            } catch (\DateMalformedStringException) {
                $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.start_date_must_be_valid'));
            }

            if ($startDate < $clock->now()) {
                $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.start_date_must_be_in_future'));
            }
        }

        $maxParticipants = $request->request->get('maxParticipants') ?: null;
        if ($maxParticipants !== null) {
            $maxParticipants = (int) $maxParticipants;
            if ($maxParticipants < 0) {
                $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.max_participants_must_be_positive'));
            }
        }

        $subscribeUntil = $request->request->get('subscribeUntil') ?: null;
        if (!empty($subscribeUntil)) {
            try {
                $subscribeUntil = new \DateTimeImmutable($subscribeUntil);
            } catch (\DateMalformedStringException) {
                $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.subscribe_until_must_be_valid'));
            }

            if ($subscribeUntil < $clock->now()) {
                $this->addFlash('error', $translator->trans('activity_manager.create_new_activity.subscribe_until_date_must_be_in_future'));
            }
        }

        $activityManagersArray = json_decode($request->request->getString('additionalManagers'), true);
        if (!is_array($activityManagersArray)) {
            $this->addFlash('error', $translator->trans('general.something_went_wrong'));

            return $this->redirectToRoute('activityManagerDashboard');
        }

        $activityManagers = [];
        foreach ($activityManagersArray as $activityManager) {
            $activityManagers[] = new ActivityManager($activityManager);
        }
        $activityManagers = new ActivityManagers($activityManagers);

        $activityRepository->create(
            new Activity(
                ActivityId::generate(),
                $name,
                $descriptionNl,
                $descriptionEn,
                $startDate,
                $maxParticipants,
                $subscribeUntil,
                $activityManagers,
                ActivityStatus::REGISTRATION_OPEN,
                $clock->now(),
                $clock->now()
            )
        );

        $this->addFlash('success', $translator->trans('activity_manager.create_new_activity.success'));

        return $this->redirectToRoute('activityManagerDashboard');
    }
}
