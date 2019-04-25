<?php


namespace App\Controller;


use App\Repository\DiaryentryRepository;
use App\Repository\EventRepository;
use App\Repository\MedicationRepository;
use App\Repository\SeizureRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class AppController extends BaseController
{
    /**
     * @Route("/app", name="app_dashboard")
     * @IsGranted("ROLE_USER")
     */
    public function index(MedicationRepository $medicationRepository, EventRepository $eventRepository, SeizureRepository $seizureRepository, DiaryentryRepository $diaryentryRepository, UserInterface $user){
        return $this->render('app/dashboard.html.twig', [
            'medication_count' => $medicationRepository->countFindAllFromUser($user),
            'seizure_count' => $seizureRepository->countFindAllFromUser($user),
            'diaryentry_count' => $diaryentryRepository->countFindAllFromUser($user),
            'event_count' => $eventRepository->countFindAllFromUser($user)
        ]);
    }

    /**
     * @Route("/api/value_sum", name="api_value_sum")
     */
    public function valueSumApi()
    {

        //Todo
        $user = $this->getUser();
        return $this->json($user, 200, [], [
            'groups' => ['main']
        ]);
    }





}