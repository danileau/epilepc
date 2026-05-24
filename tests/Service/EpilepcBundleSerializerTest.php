<?php

namespace App\Tests\Service;

use App\Entity\Diaryentry;
use App\Entity\Event;
use App\Entity\Medication;
use App\Entity\Seizure;
use App\Entity\Seizuretype;
use App\Entity\User;
use App\Service\EpilepcBundleSerializer;
use PHPUnit\Framework\TestCase;

/**
 * Golden-file test for the ciphra migration bundle serializer.
 *
 * The contract being asserted is schema v1.1 — the same shape that ciphra's
 * `frontend/src/lib/migration/epilepcMapping.ts` validates against, and that
 * `~/work/ciphra/api/fixtures/epilepc/typical.json` exemplifies. If this
 * test starts failing, either:
 *   (a) the contract changed → bump SCHEMA_VERSION in serializer + sync
 *       with ciphra side, OR
 *   (b) the serializer drifted → fix the serializer back to spec.
 *
 * Doesn't depend on the cross-repo ciphra fixture path. The expectations
 * are encoded inline here so the test runs hermetically.
 */
class EpilepcBundleSerializerTest extends TestCase
{
    private const DECOMMISSION = '2026-10-01T00:00:00Z';
    private const EXPORTED_AT = '2026-05-23T12:00:00Z';

    public function testTopLevelShape(): void
    {
        $bundle = $this->serializeMinimal();

        $expectedKeys = [
            'schema_version',
            'exported_at',
            'epilepc_decommission_at',
            'epilepc_user_id',
            'seizures',
            'events',
            'medications',
            'diary',
        ];
        $this->assertSame($expectedKeys, array_keys($bundle), 'Top-level keys must match v1.1 contract in this exact order.');

        $this->assertSame('1.1', $bundle['schema_version']);
        $this->assertSame(self::EXPORTED_AT, $bundle['exported_at']);
        $this->assertSame(self::DECOMMISSION, $bundle['epilepc_decommission_at']);
        $this->assertSame('42', $bundle['epilepc_user_id'], 'user_id must be a string, not int.');

        foreach (['seizures', 'events', 'medications', 'diary'] as $col) {
            $this->assertIsArray($bundle[$col], "$col must be a JSON array.");
        }
    }

    public function testSeizureShape(): void
    {
        $user = $this->makeUser(42);
        $type = $this->makeSeizuretype('Generalisierter tonisch-klonischer Anfall');
        $seizure = new Seizure();
        $this->setEntityId($seizure, 1000);
        $seizure->setUser($user);
        $seizure->setSeizuretype($type);
        $seizure->setTitle('Anfall vom 22.05.2026 08:15');
        $seizure->setDescription('Aura 30s before onset');
        $seizure->setTimestampWhen(new \DateTime('2026-05-22 08:15:00'));
        $seizure->setCreatedAt(new \DateTime('2026-05-22 08:15:00'));
        $user->addSeizure($seizure);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $this->assertCount(1, $bundle['seizures']);
        $row = $bundle['seizures'][0];

        $this->assertSame('1000', $row['epilepc_id'], 'epilepc_id must be stringified.');
        $this->assertSame('2026-05-22', $row['date']);
        $this->assertSame('08:15', $row['time']);
        $this->assertSame('Generalisierter tonisch-klonischer Anfall', $row['type_name']);
        // Schema v1.1 has no `title` slot for seizures — title + description
        // get folded into `notes` to preserve the user's free text.
        $this->assertSame("Anfall vom 22.05.2026 08:15\nAura 30s before onset", $row['notes']);
    }

    public function testSeizureWithoutTypeStaysNullable(): void
    {
        $user = $this->makeUser(7);
        $seizure = new Seizure();
        $this->setEntityId($seizure, 1);
        $seizure->setUser($user);
        $seizure->setTitle('');
        $seizure->setDescription('Just notes');
        $seizure->setTimestampWhen(new \DateTime('2026-05-22 14:00:00'));
        $seizure->setCreatedAt(new \DateTime('2026-05-22 14:00:00'));
        $user->addSeizure($seizure);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $this->assertNull($bundle['seizures'][0]['type_name']);
        $this->assertSame('Just notes', $bundle['seizures'][0]['notes']);
    }

    public function testEventShape(): void
    {
        $user = $this->makeUser(42);
        $event = new Event();
        $this->setEntityId($event, 2000);
        $event->setUser($user);
        $event->setName('EEG result');
        $event->setDescription('Normal findings');
        $event->setTimestampWhen(new \DateTime('2026-04-15 09:00:00'));
        $event->setCreatedAt(new \DateTime('2026-04-15 09:00:00'));
        $event->setModifiedAt(new \DateTime('2026-04-15 09:00:00'));
        $user->addEvent($event);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $this->assertCount(1, $bundle['events']);
        $row = $bundle['events'][0];
        // v1.1 keeps event title and notes distinct (CIPH-760), not concatenated.
        $this->assertSame('2000', $row['epilepc_id']);
        $this->assertSame('2026-04-15', $row['date']);
        $this->assertSame('EEG result', $row['title']);
        $this->assertSame('Normal findings', $row['notes']);
    }

    public function testMedicationOngoing(): void
    {
        // Sentinel `date_to` far in the future → ended_at should be NULL.
        $user = $this->makeUser(42);
        $med = new Medication();
        $this->setEntityId($med, 3000);
        $med->setUser($user);
        $med->setName('Topiramat');
        $med->setDosage('100mg');
        $med->setDescription('morgens, abends');
        $med->setEmergencyMed(false);
        $med->setDateFrom(new \DateTime('2025-01-31'));
        $med->setDateTo(new \DateTime('2099-12-31'));
        $med->setTimestampPrescription(new \DateTime('2025-01-31'));
        $med->setCreatedAt(new \DateTime('2025-01-31'));
        $med->setModifiedAt(new \DateTime('2025-01-31'));
        $user->addMedication($med);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $row = $bundle['medications'][0];
        $this->assertSame('3000', $row['epilepc_id']);
        $this->assertSame('Topiramat', $row['name']);
        $this->assertSame('100mg', $row['dose']);
        $this->assertSame('morgens, abends', $row['notes']);
        $this->assertFalse($row['as_needed']);
        $this->assertSame('2025-01-31', $row['started_at']);
        $this->assertNull($row['ended_at'], 'Date-to sentinel beyond 50y must become null (ongoing).');
    }

    public function testMedicationEnded(): void
    {
        $user = $this->makeUser(42);
        $med = new Medication();
        $this->setEntityId($med, 3001);
        $med->setUser($user);
        $med->setName('Levetiracetam');
        $med->setDosage('500mg');
        $med->setDescription('einmal täglich');
        $med->setEmergencyMed(true);
        $med->setDateFrom(new \DateTime('2024-06-01'));
        $med->setDateTo(new \DateTime('2025-02-15'));
        $med->setTimestampPrescription(new \DateTime('2024-06-01'));
        $med->setCreatedAt(new \DateTime('2024-06-01'));
        $med->setModifiedAt(new \DateTime('2024-06-01'));
        $user->addMedication($med);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $row = $bundle['medications'][0];
        $this->assertSame('500mg', $row['dose']);
        $this->assertTrue($row['as_needed'], 'emergency_med flag must serialize as as_needed=true.');
        $this->assertSame('2024-06-01', $row['started_at']);
        $this->assertSame('2025-02-15', $row['ended_at']);
    }

    public function testDiaryTitleAndContentMerged(): void
    {
        $user = $this->makeUser(42);
        $d = new Diaryentry();
        $this->setEntityId($d, 4000);
        $d->setUser($user);
        $d->setTitle('Morgenstimmung');
        $d->setContent('Gut geschlafen, fühle mich besser.');
        $d->setTimestampWhen(new \DateTime('2026-05-23 07:30:00'));
        $d->setCreatedAt(new \DateTime('2026-05-23 07:30:00'));
        $d->setModifiedAt(new \DateTime('2026-05-23 07:30:00'));
        $user->addDiaryentry($d);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $row = $bundle['diary'][0];
        $this->assertSame('4000', $row['epilepc_id']);
        $this->assertSame('2026-05-23', $row['date']);
        $this->assertSame('07:30', $row['time']);
        $this->assertSame(
            "Morgenstimmung\n\nGut geschlafen, fühle mich besser.",
            $row['text'],
            'Diary title prefixes the content with double-newline separator.'
        );
    }

    public function testDiaryWithoutTitleHasNoPrefix(): void
    {
        $user = $this->makeUser(42);
        $d = new Diaryentry();
        $this->setEntityId($d, 4001);
        $d->setUser($user);
        $d->setTitle('');
        $d->setContent('Stress, Kopfschmerzen.');
        $d->setTimestampWhen(new \DateTime('2026-05-23 18:00:00'));
        $d->setCreatedAt(new \DateTime('2026-05-23 18:00:00'));
        $d->setModifiedAt(new \DateTime('2026-05-23 18:00:00'));
        $user->addDiaryentry($d);

        $bundle = $this->serializer()->serialize($user, new \DateTimeImmutable(self::EXPORTED_AT));

        $this->assertSame('Stress, Kopfschmerzen.', $bundle['diary'][0]['text']);
    }

    public function testExportedAtIsIsoUtc(): void
    {
        // Round-trip through other timezones must collapse to UTC `Z` suffix.
        $user = $this->makeUser(1);
        $bundle = $this->serializer()->serialize(
            $user,
            new \DateTimeImmutable('2026-05-23T14:00:00+02:00')
        );
        $this->assertSame('2026-05-23T12:00:00Z', $bundle['exported_at']);
    }

    public function testBundleIsJsonEncodable(): void
    {
        // The migration controller does json_encode + emit. Make sure the
        // bundle has no unencodable values (closures, resources, etc.).
        $bundle = $this->serializeMinimal();
        $json = json_encode($bundle, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->assertNotFalse($json);
        $this->assertJson($json);
    }

    // ─── Test helpers ────────────────────────────────────────────────

    private function serializer(): EpilepcBundleSerializer
    {
        return new EpilepcBundleSerializer(self::DECOMMISSION);
    }

    /** Run the serializer against a User with id=42 and no children. */
    private function serializeMinimal(): array
    {
        return $this->serializer()->serialize(
            $this->makeUser(42),
            new \DateTimeImmutable(self::EXPORTED_AT)
        );
    }

    private function makeUser(int $id): User
    {
        $user = new User();
        $this->setEntityId($user, $id);
        $user->setEmail('demo@epilepc.test');
        $user->setFirstname('Demo');
        $user->setLastname('User');
        $user->setPassword('hashed');
        $user->setRoles([]);
        $user->setDeactivated(false);
        return $user;
    }

    private function makeSeizuretype(string $name): Seizuretype
    {
        $type = new Seizuretype();
        $type->setName($name);
        return $type;
    }

    /**
     * Doctrine entities don't expose setId() — IDs are normally assigned
     * by the DB on flush. For unit tests we reach through reflection.
     */
    private function setEntityId(object $entity, int $id): void
    {
        $ref = new \ReflectionClass($entity);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($entity, $id);
    }
}
