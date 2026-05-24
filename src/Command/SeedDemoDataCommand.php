<?php

namespace App\Command;

use App\Entity\Diaryentry;
use App\Entity\Event;
use App\Entity\Medication;
use App\Entity\Seizure;
use App\Entity\Seizuretype;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Seeds the epilepc dev database with realistic-shape user data so we can
 * exercise the ciphra migration end-to-end. Idempotent: re-running won't
 * duplicate users (matched by email) or seizure types (matched by name).
 *
 * Usage:
 *   bin/console app:seed-demo                       # default: 2 users, ~50 seizures each
 *   bin/console app:seed-demo --users=5
 *   bin/console app:seed-demo --users=1 --seizures=200 --events=10 --meds=5 --diary=40
 *   bin/console app:seed-demo --reset                # delete + recreate (drops user data!)
 */
class SeedDemoDataCommand extends Command
{
    protected static $defaultName = 'app:seed-demo';

    private const TYPES = ['Absence', 'Generalisierter tonisch-klonischer Anfall', 'Myoklonischer Anfall', 'Fokaler Anfall'];
    private const PASSWORD = 'demo1234';

    private const SEIZURE_NOTES = [
        'No warning', 'Aura 30s before onset', 'After missed dose', '',
        'During sleep', 'Triggered by flashing lights', 'Felt foggy afterwards',
    ];
    private const EVENT_TITLES = [
        'EEG result', 'Neurologe-Termin', 'Started new medication',
        'Hospital visit', 'Medication adjustment',
    ];
    private const DIARY_LINES = [
        'Felt strange after the morning walk.',
        'Schlecht geschlafen, Aura am Nachmittag.',
        'Gut geschlafen, fühle mich besser.',
        'Stress bei der Arbeit, Kopfschmerzen.',
        'Tag ohne besondere Vorkommnisse.',
    ];

    /** @var EntityManagerInterface */
    private $em;
    /** @var UserPasswordEncoderInterface */
    private $passwordEncoder;

    public function __construct(EntityManagerInterface $em, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct();
        $this->em = $em;
        $this->passwordEncoder = $passwordEncoder;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Seed dev database with demo users and content for migration testing')
            ->addOption('users', null, InputOption::VALUE_REQUIRED, 'How many demo users to create', '2')
            ->addOption('seizures', null, InputOption::VALUE_REQUIRED, 'Seizures per user', '50')
            ->addOption('events', null, InputOption::VALUE_REQUIRED, 'Events per user', '5')
            ->addOption('meds', null, InputOption::VALUE_REQUIRED, 'Medications per user', '3')
            ->addOption('diary', null, InputOption::VALUE_REQUIRED, 'Diary entries per user', '20')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Delete existing demo users (email demoN@epilepc.test) before re-seeding');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $userCount    = max(1, (int) $input->getOption('users'));
        $seizureCount = max(0, (int) $input->getOption('seizures'));
        $eventCount   = max(0, (int) $input->getOption('events'));
        $medCount     = max(0, (int) $input->getOption('meds'));
        $diaryCount   = max(0, (int) $input->getOption('diary'));
        $reset        = (bool) $input->getOption('reset');

        if ($reset) {
            $deleted = $this->deleteDemoUsers($io, $userCount);
            $io->note(sprintf('Reset: deleted %d existing demo user(s) and cascaded children.', $deleted));
        }

        $types = $this->ensureSeizureTypes();
        $io->writeln(sprintf('Seizure types ready: %d', count($types)));

        for ($i = 1; $i <= $userCount; $i++) {
            $email = sprintf('demo%d@epilepc.test', $i);
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                $io->writeln(sprintf('User %s already exists — skipping creation, adding content only.', $email));
            } else {
                $user = $this->createUser($email, sprintf('Demo%d', $i), 'User');
                $io->writeln(sprintf('Created user %s (password: %s)', $email, self::PASSWORD));
            }
            $this->createSeizures($user, $types, $seizureCount);
            $this->createEvents($user, $eventCount);
            $this->createMedications($user, $medCount);
            $this->createDiary($user, $diaryCount);
            $this->em->flush();
        }

        $io->success(sprintf(
            'Seeded %d user(s). Each got ~%d seizures, %d events, %d meds, %d diary entries.',
            $userCount,
            $seizureCount,
            $eventCount,
            $medCount,
            $diaryCount
        ));
        $io->writeln('Login: demo1@epilepc.test / ' . self::PASSWORD);

        return 0;
    }

    private function deleteDemoUsers(SymfonyStyle $io, int $upTo): int
    {
        $deleted = 0;
        for ($i = 1; $i <= max($upTo, 10); $i++) {
            $email = sprintf('demo%d@epilepc.test', $i);
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user) {
                // Children FK aren't ON DELETE CASCADE in the existing schema,
                // so remove them explicitly to keep the seeder honest.
                foreach ($user->getSeizures() as $s)     { $this->em->remove($s); }
                foreach ($user->getEvents() as $e)       { $this->em->remove($e); }
                foreach ($user->getMedications() as $m)  { $this->em->remove($m); }
                foreach ($user->getDiaryentries() as $d) { $this->em->remove($d); }
                $this->em->remove($user);
                $deleted++;
            }
        }
        $this->em->flush();
        return $deleted;
    }

    /** @return Seizuretype[] */
    private function ensureSeizureTypes(): array
    {
        $repo = $this->em->getRepository(Seizuretype::class);
        $result = [];
        foreach (self::TYPES as $name) {
            $type = $repo->findOneBy(['name' => $name]);
            if (!$type) {
                $type = new Seizuretype();
                $type->setName($name);
                $type->setDescription($name);
                $this->em->persist($type);
            }
            $result[] = $type;
        }
        $this->em->flush();
        return $result;
    }

    private function createUser(string $email, string $firstname, string $lastname): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setPassword($this->passwordEncoder->encodePassword($user, self::PASSWORD));
        $user->setDeactivated(false);
        $user->setRoles([]);
        $user->setDiagnose('Epilepsie');
        $user->agreeTerms();
        $this->em->persist($user);
        return $user;
    }

    /** @param Seizuretype[] $types */
    private function createSeizures(User $user, array $types, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $ts = $this->randomTimestamp(540);
            $type = $types[$i % count($types)];
            $s = new Seizure();
            $s->setUser($user);
            $s->setSeizuretype($type);
            $s->setTitle(sprintf('Anfall vom %s', $ts->format('d.m.Y H:i')));
            $s->setDescription(self::SEIZURE_NOTES[array_rand(self::SEIZURE_NOTES)]);
            $s->setTimestampWhen($ts);
            $s->setCreatedAt($ts);
            $s->setModifiedAt($ts);
            $this->em->persist($s);
        }
    }

    private function createEvents(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $ts = $this->randomTimestamp(540);
            $e = new Event();
            $e->setUser($user);
            $e->setName(self::EVENT_TITLES[array_rand(self::EVENT_TITLES)]);
            $e->setDescription('Detail aus epilepc');
            $e->setTimestampWhen($ts);
            $e->setCreatedAt($ts);
            $e->setModifiedAt($ts);
            $this->em->persist($e);
        }
    }

    private function createMedications(User $user, int $count): void
    {
        $names = ['Topiramat', 'Levetiracetam', 'Lamotrigin', 'Valproat', 'Carbamazepin'];
        for ($i = 0; $i < $count; $i++) {
            $from = $this->randomTimestamp(540);
            // Some ongoing, some ended.
            $ongoing = ($i % 3) !== 0;
            $to = $ongoing
                ? new \DateTime('2099-12-31')
                : (new \DateTime('@' . ($from->getTimestamp() + 60 * 86400)));
            $m = new Medication();
            $m->setUser($user);
            $m->setName($names[$i % count($names)]);
            $m->setDescription('morgens, abends');
            $m->setDosage(sprintf('%d mg', 50 * (1 + ($i % 4))));
            $m->setDateFrom($from);
            $m->setDateTo($to);
            $m->setTimestampPrescription($from);
            $m->setCreatedAt($from);
            $m->setModifiedAt($from);
            $m->setEmergencyMed($i === 0 && $count > 1);
            $this->em->persist($m);
        }
    }

    private function createDiary(User $user, int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $ts = $this->randomTimestamp(540);
            $d = new Diaryentry();
            $d->setUser($user);
            $d->setTitle('Tageseintrag');
            $d->setContent(self::DIARY_LINES[array_rand(self::DIARY_LINES)]);
            $d->setTimestampWhen($ts);
            $d->setCreatedAt($ts);
            $d->setModifiedAt($ts);
            $this->em->persist($d);
        }
    }

    private function randomTimestamp(int $daysBack): \DateTime
    {
        $end = time();
        $start = $end - ($daysBack * 86400);
        return (new \DateTime())->setTimestamp(random_int($start, $end));
    }
}
