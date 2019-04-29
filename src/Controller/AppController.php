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
    public function index(MedicationRepository $medicationRepository, EventRepository $eventRepository, SeizureRepository $seizureRepository, DiaryentryRepository $diaryentryRepository, UserInterface $user)
    {
        /**
         * Anfallsdaten für die Diagramme aufbereiten
         */
        $seizure_data = $seizureRepository->getDiagramSeizureData($user);
        foreach ($seizure_data as $key => $value) {
            $seizureDiagramMonth[] = strftime("%B %Y", strtotime($key."-01"));
            $seizureDiagramCount[] = $value;
        }
        $seizureDiagramMonth = array_reverse($seizureDiagramMonth);
        $seizureDiagramCount = array_reverse($seizureDiagramCount);
        $seizureMonthJSON = json_encode($seizureDiagramMonth);
        $seizureValueJSON = json_encode($seizureDiagramCount);

        /**
         * Ereignisdaten für die Diagramme aufbereiten
         */
        $event_data = $eventRepository->getDiagramEventData($user);
        foreach ($event_data as $key => $value) {
            $eventDiagramCount[] = $value;
        }
        $eventDiagramCount = array_reverse($eventDiagramCount);
        $eventValueJSON = json_encode($eventDiagramCount);

        /**
         * Tagebuchdaten für die Diagramme aufbereiten
         */
        $diary_data = $diaryentryRepository->getDiagramDiaryData($user);
        foreach ($diary_data as $key => $value) {
            $diaryDiagramCount[] = $value;
        }
        $diaryDiagramCount = array_reverse($diaryDiagramCount);
        $diaryValueJSON = json_encode($diaryDiagramCount);



        return $this->render('app/dashboard.html.twig', [
            'medication_count' => $medicationRepository->countFindAllFromUser($user),
            'seizure_count' => $seizureRepository->countFindAllFromUser($user),
            'seizure_month' => $seizureMonthJSON,
            'seizure_data' => $seizureValueJSON,
            'diaryentry_count' => $diaryentryRepository->countFindAllFromUser($user),
            'diaryentry_data' => $diaryValueJSON,
            'event_count' => $eventRepository->countFindAllFromUser($user),
            'event_data' => $eventValueJSON
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