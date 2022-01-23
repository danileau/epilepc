<?php


namespace App\Controller;


use App\Entity\User;
use App\Repository\DiaryentryRepository;
use App\Repository\EventRepository;
use App\Repository\MedicationRepository;
use App\Repository\SeizureRepository;
use App\Repository\UserRepository;
use DateTime;
use DateTimeImmutable;
use Knp\Snappy\Pdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class AppController extends AbstractController
{

    /**
     * @Route("/app", name="app_dashboard")
     * @IsGranted("ROLE_USER")
     * Dashboard mit allen queries für die Counts und Diagramme
     */
    public function index(Request $request, MedicationRepository $medicationRepository, EventRepository $eventRepository, SeizureRepository $seizureRepository, DiaryentryRepository $diaryentryRepository, UserInterface $user, UserRepository $usr)
    {
        /**
         * Anfallsdaten für die Diagramme aufbereiten
         */
        $seizure_data = $seizureRepository->getDiagramSeizureData($user);
        foreach ($seizure_data as $key => $value) {
            $seizureDiagramMonth[] = $usr->translateMonth(strftime("%B", strtotime($key."-01")), $request->getLocale())." ".strftime("%Y", strtotime($key."-01"));
            $seizureDiagramCount[] = $value;
        }
        $seizureDiagramMonth = array_reverse($seizureDiagramMonth);
        $seizureDiagramCount = array_reverse($seizureDiagramCount);
        $seizureMonthJSON = json_encode($seizureDiagramMonth);
        $seizureValueJSON = json_encode($seizureDiagramCount);


        $seizure_m_data = $seizureRepository->getDiagramSeizureMonthData($user);
        foreach ($seizure_m_data as $key => $value) {
            $seizure_m_DiagramMonth[] = $key;;
            $seizure_m_DiagramCount[] = $value;
        }
        $seizure_m_MonthJSON = json_encode($seizure_m_DiagramMonth);
        $seizure_m_ValueJSON = json_encode($seizure_m_DiagramCount);


        /**
         * Ereignisdaten für die Diagramme aufbereiten
         */
        $event_data = $eventRepository->getDiagramEventData($user);
        foreach ($event_data as $key => $value) {
            $eventDiagramCount[] = $value;
        }
        $eventDiagramCount = array_reverse($eventDiagramCount);
        $eventValueJSON = json_encode($eventDiagramCount);

        $event_m_data = $eventRepository->getDiagramEventMonthData($user);
        foreach ($event_m_data as $key => $value) {
            $event_m_DiagramCount[] = $value;
        }
        $event_m_ValueJSON = json_encode($event_m_DiagramCount);
        /**
         * Tagebuchdaten für die Diagramme aufbereiten
         */
        $diary_data = $diaryentryRepository->getDiagramDiaryData($user);
        foreach ($diary_data as $key => $value) {
            $diaryDiagramCount[] = $value;
        }

        $diaryDiagramCount = array_reverse($diaryDiagramCount);
        $diaryValueJSON = json_encode($diaryDiagramCount);

        $diary_m_data = $diaryentryRepository->getDiagramDiaryMonthData($user);
        foreach ($diary_m_data as $key => $value) {
            $diary_m_DiagramCount[] = $value;
        }

        $diary_m_ValueJSON = json_encode($diary_m_DiagramCount);

        /**
         * Medikamentendaten für die Diagramme aufbereiten
         */
        $medication_data = $medicationRepository->getDiagramMedicationData($user);
        foreach ($medication_data as $key => $value) {
            $medicationDiagramCount[] = $value;
        }
        $medicationDiagramCount = array_reverse($medicationDiagramCount);
        $medicationValueJSON = json_encode($medicationDiagramCount);
        $medication_m_data = $medicationRepository->getDiagramMedicationMonthData($user);
        foreach ($medication_m_data as $key => $value) {
            $medication_m_DiagramCount[] = $value;
        }
        $medication_m_ValueJSON = json_encode($medication_m_DiagramCount);


        /**
         * Twig Template mit allen Variablen rendern
         */
        return $this->render('app/dashboard.html.twig', [
            'medication_count' => $medicationRepository->countFindAllFromUser($user),
            'medication_data' => $medicationValueJSON,
            'medication_m_data' => $medication_m_ValueJSON,
            'medication_emergency_count' => $medicationRepository->countFindAllEmergencyFromUser($user),
            'seizure_count' => $seizureRepository->countFindAllFromUser($user),
            'seizure_month' => $seizureMonthJSON,
            'seizure_data' => $seizureValueJSON,
            'seizure_m_month' => $seizure_m_MonthJSON,
            'seizure_m_data' => $seizure_m_ValueJSON,
            //'seizure_emeds_count' => $seizureRepository->countFindAllEMedsFromUser($user),
           // 'seizure_emeds_month' => $seizureEMedsMonthJSON,
           // 'seizure_emeds_data' => $seizureEMedsValueJSON,
            'diaryentry_count' => $diaryentryRepository->countFindAllFromUser($user),
            'diaryentry_data' => $diaryValueJSON,
            'diaryentry_m_data' => $diary_m_ValueJSON,
            'event_count' => $eventRepository->countFindAllFromUser($user),
            'event_data' => $eventValueJSON,
            'event_m_data' => $event_m_ValueJSON
        ]);
    }


    /**
     * @Route("/app/overview", name="app_overview")
     * Übersicht mit allen Werten generieren
     */
    public function overview(Request $request,MedicationRepository $medicationRepository, EventRepository $eventRepository, SeizureRepository $seizureRepository, DiaryentryRepository $diaryentryRepository, UserRepository $usr, UserInterface $user)
    {
        /** @var $user User */
        $diagnose = $user->getDiagnose();
        /**
         * Anfallsdaten für die Übersicht aufbereiten
         */
        $seizure_data = $seizureRepository->getDiagramSeizureData($user);
        foreach ($seizure_data as $key => $value) {
            $seizureDiagramMonth[] = $usr->translateMonth(strftime("%B", strtotime($key."-01")), $request->getLocale())." ".strftime("%Y", strtotime($key."-01"));
            $seizureDiagramCount[] = $value;

        }

        $seizureDiagramMonth = array_reverse($seizureDiagramMonth);
        $seizureDiagramCount = array_reverse($seizureDiagramCount);
        $seizureMonthJSON = json_encode($seizureDiagramMonth);
        $seizureMonthJSON12Month= json_encode(array_slice($seizureDiagramMonth,12));

        $seizureValueJSON = json_encode($seizureDiagramCount);
        $seizureValueJSON12Month = json_encode(array_slice($seizureDiagramCount,12));
        $seizures = $seizureRepository->findAllFromUser($user);

        /**
         * Ereignisdaten für die Diagramme aufbereiten
         */
        $event_data = $eventRepository->getDiagramEventData($user);
        foreach ($event_data as $key => $value) {
            $eventDiagramCount[] = $value;
        }
        $eventDiagramCount = array_reverse($eventDiagramCount);
        $eventValueJSON = json_encode($eventDiagramCount);
        $eventValueJSON12Month = json_encode(array_slice($eventDiagramCount,12));
        $events = $eventRepository->findAllFromUser($user);

        /**
         * Tagebuchdaten für die Diagramme aufbereiten
         */
        $diary_data = $diaryentryRepository->getDiagramDiaryData($user);
        foreach ($diary_data as $key => $value) {
            $diaryDiagramCount[] = $value;
        }
        $diaryDiagramCount = array_reverse($diaryDiagramCount);
        $diaryValueJSON = json_encode($diaryDiagramCount);
        $diaryValueJSON12Month = json_encode(array_slice($diaryDiagramCount,12));
        $diaryentrys = $diaryentryRepository->findAllFromUser($user);

        /**
         * Medikamentendaten für die Diagramme aufbereiten
         */
        $medication_data = $medicationRepository->getDiagramMedicationData($user);
        foreach ($medication_data as $key => $value) {
            $medicationDiagramCount[] = $value;
        }
        $medicationDiagramCount = array_reverse($medicationDiagramCount);
        $medicationValueJSON = json_encode($medicationDiagramCount);
        $medicationValueJSON12Month = json_encode(array_slice($medicationDiagramCount,12));
        $medications = $medicationRepository->findAllFromUser($user);

        /*
         * Twig Template mit allen Variablen rendern
         */
        return $this->render('app/overview.html.twig', [
            'medication_count' => $medicationRepository->countFindAllFromUser($user),
            'medication_data' => $medicationValueJSON,
            'medication_data_1' => $medicationValueJSON12Month,
            'medications' => $medications,
            'seizure_count' => $seizureRepository->countFindAllFromUser($user),
            'seizure_month' => $seizureMonthJSON,
            'seizure_month_1' => $seizureMonthJSON12Month,
            'seizure_data' => $seizureValueJSON,
            'seizure_data_1' => $seizureValueJSON12Month,
            'seizures' => $seizures,
            'diaryentry_count' => $diaryentryRepository->countFindAllFromUser($user),
            'diaryentry_data' => $diaryValueJSON,
            'diaryentry_data_1' => $diaryValueJSON12Month,
            'diaryentrys' => $diaryentrys,
            'event_count' => $eventRepository->countFindAllFromUser($user),
            'event_data' => $eventValueJSON,
            'event_data_1' => $eventValueJSON12Month,
            'events' => $events,
            'date' => date("d.m.Y"),
            'diagnose' => $diagnose
        ]);
    }


    /**
     * @Route("/app/overview/pdf", name="app_overview_pdf")
     * PDF generieren
     */
    public function pdfAction(MedicationRepository $medicationRepository, EventRepository $eventRepository, SeizureRepository $seizureRepository, DiaryentryRepository $diaryentryRepository, UserInterface $user, Pdf $snappy)
    {
        /** @var $user User */
        $diagnose = $user->getDiagnose();
        /**
         * Anfallsdaten für die Übersicht aufbereiten
         */
        $seizure_data = $seizureRepository->getDiagramSeizureData($user);
        foreach ($seizure_data as $key => $value) {
            $seizureDiagramMonth[] = strftime("%B %Y", strtotime($key."-01"));
            $seizureDiagramCount[] = $value;
        }
        $seizureDiagramMonth = array_reverse($seizureDiagramMonth);
        $seizureDiagramCount = array_reverse($seizureDiagramCount);
        $seizureMonthJSON = json_encode($seizureDiagramMonth);
        $seizureMonthJSON12Month= json_encode(array_slice($seizureDiagramMonth,12));
        $seizureValueJSON = json_encode($seizureDiagramCount);
        $seizureValueJSON12Month = json_encode(array_slice($seizureDiagramCount,12));
        $seizures = $seizureRepository->findAllFromUser($user);

        /**
         * Ereignisdaten für die Diagramme aufbereiten
         */
        $event_data = $eventRepository->getDiagramEventData($user);
        foreach ($event_data as $key => $value) {
            $eventDiagramCount[] = $value;
        }
        $eventDiagramCount = array_reverse($eventDiagramCount);
        $eventValueJSON = json_encode($eventDiagramCount);
        $eventValueJSON12Month = json_encode(array_slice($eventDiagramCount,12));
        $events = $eventRepository->findAllFromUser($user);

        /**
         * Tagebuchdaten für die Diagramme aufbereiten
         */
        $diary_data = $diaryentryRepository->getDiagramDiaryData($user);
        foreach ($diary_data as $key => $value) {
            $diaryDiagramCount[] = $value;
        }
        $diaryDiagramCount = array_reverse($diaryDiagramCount);
        $diaryValueJSON = json_encode($diaryDiagramCount);
        $diaryValueJSON12Month = json_encode(array_slice($diaryDiagramCount,12));
        $diaryentrys = $diaryentryRepository->findAllFromUser($user);

        /**
         * Medikamentendaten für die Diagramme aufbereiten
         */
        $medication_data = $medicationRepository->getDiagramMedicationData($user);
        foreach ($medication_data as $key => $value) {
            $medicationDiagramCount[] = $value;
        }
        $medicationDiagramCount = array_reverse($medicationDiagramCount);
        $medicationValueJSON = json_encode($medicationDiagramCount);
        $medicationValueJSON12Month = json_encode(array_slice($medicationDiagramCount,12));
        $medications = $medicationRepository->findAllFromUser($user);

        /**
         * PDF Twig Template mit Variablen rendern
         */
        return $this->render('app/pdfGenerator.html.twig', [
            'medication_count' => $medicationRepository->countFindAllFromUser($user),
            'medication_data' => $medicationValueJSON,
            'medication_data_1' => $medicationValueJSON12Month,
            'medications' => $medications,
            'seizure_count' => $seizureRepository->countFindAllFromUser($user),
            'seizure_month' => $seizureMonthJSON,
            'seizure_month_1' => $seizureMonthJSON12Month,
            'seizure_data' => $seizureValueJSON,
            'seizure_data_1' => $seizureValueJSON12Month,
            'seizures' => $seizures,
            //'diaryentry_count' => $diaryentryRepository->countFindAllFromUser($user),
            //'diaryentry_data' => $diaryValueJSON,
            //'diaryentrys' => $diaryentrys,
            'event_count' => $eventRepository->countFindAllFromUser($user),
            'event_data' => $eventValueJSON,
            'event_data_1' => $eventValueJSON12Month,
            'events' => $events,
            'date' => date("d.m.Y"),
            'diagnose' => $diagnose
        ]);

    }



}
