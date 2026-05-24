<?php

namespace App\Service;

use App\Entity\Diaryentry;
use App\Entity\Event;
use App\Entity\Medication;
use App\Entity\Seizure;
use App\Entity\User;

/**
 * Serialises a User aggregate (seizures, events, medications, diary) into
 * the schema-v1.1 JSON bundle ciphra's browser-side migration page
 * expects. Pure mapping — no I/O, no persistence. Easy to unit-test.
 *
 * Contract lives in ~/work/ciphra/api/fixtures/epilepc/typical.json and
 * ~/work/ciphra/frontend/src/lib/migration/epilepcMapping.ts.
 *
 * Two known data-loss points, agreed for alpha:
 *   - Seizure.title  → folded into notes as "title\n description"
 *   - Medication.timestamp_prescription → dropped (no slot in v1.1)
 */
class EpilepcBundleSerializer
{
    public const SCHEMA_VERSION = '1.1';

    /**
     * Seconds: if Medication.date_to is more than this many seconds in the
     * future, treat as "ongoing" → emit ended_at=null. Epilepc.date_to is
     * non-nullable; users frequently put a sentinel like 2099-12-31.
     */
    private const ONGOING_THRESHOLD_SECONDS = 50 * 365 * 24 * 60 * 60;

    private $decommissionAt;

    public function __construct(string $decommissionAt = '2026-10-01T00:00:00Z')
    {
        $this->decommissionAt = $decommissionAt;
    }

    /**
     * Build the bundle. Caller is responsible for json_encode + emitting.
     *
     * @return array<string,mixed>
     */
    public function serialize(User $user, \DateTimeInterface $exportedAt): array
    {
        return [
            'schema_version'           => self::SCHEMA_VERSION,
            'exported_at'              => $this->formatIsoUtc($exportedAt),
            'epilepc_decommission_at'  => $this->decommissionAt,
            'epilepc_user_id'          => (string) $user->getId(),
            'seizures'                 => $this->serializeSeizures($user->getSeizures()->toArray()),
            'events'                   => $this->serializeEvents($user->getEvents()->toArray()),
            'medications'              => $this->serializeMedications($user->getMedications()->toArray()),
            'diary'                    => $this->serializeDiary($user->getDiaryentries()->toArray()),
        ];
    }

    /**
     * @param Seizure[] $seizures
     * @return array<int,array<string,mixed>>
     */
    private function serializeSeizures(array $seizures): array
    {
        $out = [];
        foreach ($seizures as $s) {
            $ts = $s->getTimestampWhen();
            $row = [
                'epilepc_id' => (string) $s->getId(),
                'date'       => $ts ? $ts->format('Y-m-d') : '',
                'time'       => $ts ? $ts->format('H:i') : null,
                'type_name'  => $s->getSeizuretype() ? $s->getSeizuretype()->getName() : null,
                'notes'      => $this->mergeTitleAndNotes($s->getTitle(), $s->getDescription()),
            ];
            $out[] = $row;
        }
        return $out;
    }

    /**
     * @param Event[] $events
     * @return array<int,array<string,mixed>>
     */
    private function serializeEvents(array $events): array
    {
        $out = [];
        foreach ($events as $e) {
            $ts = $e->getTimestampWhen();
            $out[] = [
                'epilepc_id' => (string) $e->getId(),
                'date'       => $ts ? $ts->format('Y-m-d') : '',
                'title'      => (string) $e->getName(),
                'notes'      => (string) ($e->getDescription() ?? ''),
            ];
        }
        return $out;
    }

    /**
     * @param Medication[] $meds
     * @return array<int,array<string,mixed>>
     */
    private function serializeMedications(array $meds): array
    {
        $out = [];
        $now = new \DateTimeImmutable();
        foreach ($meds as $m) {
            $from = $m->getDateFrom();
            $to   = $m->getDateTo();
            $endedAt = null;
            if ($to !== null) {
                $deltaSeconds = $to->getTimestamp() - $now->getTimestamp();
                if ($deltaSeconds <= self::ONGOING_THRESHOLD_SECONDS) {
                    $endedAt = $to->format('Y-m-d');
                }
            }
            $out[] = [
                'epilepc_id' => (string) $m->getId(),
                'name'       => (string) $m->getName(),
                'dose'       => $m->getDosage() !== null ? (string) $m->getDosage() : null,
                'notes'      => $m->getDescription() !== null ? (string) $m->getDescription() : null,
                'as_needed'  => (bool) $m->getEmergencyMed(),
                'started_at' => $from ? $from->format('Y-m-d') : null,
                'ended_at'   => $endedAt,
            ];
        }
        return $out;
    }

    /**
     * @param Diaryentry[] $entries
     * @return array<int,array<string,mixed>>
     */
    private function serializeDiary(array $entries): array
    {
        $out = [];
        foreach ($entries as $d) {
            $ts = $d->getTimestampWhen();
            $title = (string) ($d->getTitle() ?? '');
            $content = (string) ($d->getContent() ?? '');
            $text = trim($title) !== ''
                ? rtrim($title) . "\n\n" . $content
                : $content;
            $out[] = [
                'epilepc_id' => (string) $d->getId(),
                'date'       => $ts ? $ts->format('Y-m-d') : '',
                'time'       => $ts ? $ts->format('H:i') : null,
                'text'       => $text,
            ];
        }
        return $out;
    }

    private function mergeTitleAndNotes(?string $title, ?string $description): ?string
    {
        $title = $title !== null ? trim($title) : '';
        $description = $description !== null ? trim($description) : '';
        if ($title === '' && $description === '') {
            return null;
        }
        if ($title === '') {
            return $description;
        }
        if ($description === '') {
            return $title;
        }
        return $title . "\n" . $description;
    }

    private function formatIsoUtc(\DateTimeInterface $dt): string
    {
        $utc = (new \DateTimeImmutable('@' . $dt->getTimestamp()))->setTimezone(new \DateTimeZone('UTC'));
        return $utc->format('Y-m-d\TH:i:s\Z');
    }
}
