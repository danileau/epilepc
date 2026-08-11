-- =====================================================================
-- seed-migration-test-user.sql — throwaway account for an end-to-end
-- migration rehearsal (INC-001).
--
-- Creates ONE new user plus realistic content. Touches nothing that
-- already exists: no UPDATE, no DELETE, every insert scoped to the new
-- user's id. Safe to run on production; cleanup at the bottom.
--
--   Login:    migtest@epilepc.ch
--   Password: MigTest!2026#ciphra
--
-- Run:
--   mysql -u<user> -p <db> < scripts/seed-migration-test-user.sql
--
-- WHY PLAINTEXT WORKS. Several of these columns are @Encrypted. The
-- bundle appends a '<ENC>' marker when it encrypts and only decrypts a
-- value that carries it (DoctrineEncryptSubscriber::ENCRYPTION_MARKER),
-- so plaintext written by hand is passed through untouched on read.
-- Verified against the export endpoint before this file was committed.
-- Consequence: do NOT edit this account through the web UI and then
-- re-export — a save would encrypt these fields, which is fine, but it
-- stops being a pure fixture.
--
-- The password hash is argon2id, generated with
-- `bin/console security:encode-password`. It is a disposable test
-- credential for an account that is meant to be deleted afterwards.
-- =====================================================================

START TRANSACTION;

-- --- the account -----------------------------------------------------
INSERT INTO `user`
    (email, password, firstname, lastname, deactivated, roles, agreed_terms_at, diagnose)
VALUES (
    'migtest@epilepc.ch',
    '$argon2id$v=19$m=65536,t=4,p=1$zaFEvfWjZouz2gVf0naFiQ$OF+maZulYtSeeU6dY2+XnEmODnlmqcOpxdFDmwTb/wA',
    'Migrations', 'Test', 0, '[]', NOW(), 'Epilepsie (Testkonto INC-001)'
);
SET @uid = LAST_INSERT_ID();

-- Look seizure types up by name rather than hardcoding ids — they differ
-- between environments. NULL is allowed and maps to 'unknown' in ciphra,
-- so a missing type degrades gracefully instead of failing the insert.
SET @t_focal   = (SELECT id FROM seizuretype WHERE name LIKE 'Fokal%'          LIMIT 1);
SET @t_gen     = (SELECT id FROM seizuretype WHERE name LIKE 'Generalisiert%'  LIMIT 1);
SET @t_abs     = (SELECT id FROM seizuretype WHERE name LIKE 'Absence%'        LIMIT 1);
SET @t_myo     = (SELECT id FROM seizuretype WHERE name LIKE 'Myoklon%'        LIMIT 1);

-- --- seizures: all four types + one untyped (-> 'unknown' in ciphra) --
INSERT INTO seizure (user_id, title, description, timestamp_when, created_at, modified_at, seizuretype_id) VALUES
 (@uid, 'Anfall morgens',        'Aura ca. 30s vorher',            NOW() - INTERVAL 210 DAY + INTERVAL 8 HOUR,  NOW(), NOW(), @t_focal),
 (@uid, 'Anfall nachts',         'Im Schlaf, Zeuge anwesend',      NOW() - INTERVAL 198 DAY + INTERVAL 2 HOUR,  NOW(), NOW(), @t_gen),
 (@uid, 'Kurze Absence',         '',                               NOW() - INTERVAL 181 DAY + INTERVAL 11 HOUR, NOW(), NOW(), @t_abs),
 (@uid, 'Zuckungen Arm links',   'Nach schlechtem Schlaf',         NOW() - INTERVAL 167 DAY + INTERVAL 7 HOUR,  NOW(), NOW(), @t_myo),
 (@uid, 'Anfall im Buero',       'Stress, Dosis vergessen',        NOW() - INTERVAL 156 DAY + INTERVAL 14 HOUR, NOW(), NOW(), @t_focal),
 (@uid, 'Anfall',                'Kein Warnzeichen',               NOW() - INTERVAL 142 DAY + INTERVAL 9 HOUR,  NOW(), NOW(), @t_gen),
 (@uid, 'Absence beim Essen',    NULL,                             NOW() - INTERVAL 133 DAY + INTERVAL 12 HOUR, NOW(), NOW(), @t_abs),
 (@uid, 'Anfall morgens',        'Danach sehr muede',              NOW() - INTERVAL 121 DAY + INTERVAL 6 HOUR,  NOW(), NOW(), @t_focal),
 (@uid, 'Naechtlicher Anfall',   'Zungenbiss',                     NOW() - INTERVAL 110 DAY + INTERVAL 3 HOUR,  NOW(), NOW(), @t_gen),
 (@uid, 'Myoklonien',            'Beim Aufwachen',                 NOW() - INTERVAL 98  DAY + INTERVAL 7 HOUR,  NOW(), NOW(), @t_myo),
 (@uid, 'Anfall',                'Flackerlicht im Zug',            NOW() - INTERVAL 87  DAY + INTERVAL 17 HOUR, NOW(), NOW(), @t_focal),
 (@uid, 'Absence',               '',                               NOW() - INTERVAL 76  DAY + INTERVAL 10 HOUR, NOW(), NOW(), @t_abs),
 (@uid, 'Anfall nach Fieber',    'Erkaeltung, 38.5 Grad',          NOW() - INTERVAL 64  DAY + INTERVAL 20 HOUR, NOW(), NOW(), @t_gen),
 (@uid, 'Kurze Zuckungen',       NULL,                             NOW() - INTERVAL 55  DAY + INTERVAL 8 HOUR,  NOW(), NOW(), @t_myo),
 (@uid, 'Anfall morgens',        'Aura, konnte hinsetzen',         NOW() - INTERVAL 43  DAY + INTERVAL 7 HOUR,  NOW(), NOW(), @t_focal),
 (@uid, 'Anfall',                'Nach Nachtschicht',              NOW() - INTERVAL 31  DAY + INTERVAL 23 HOUR, NOW(), NOW(), @t_gen),
 (@uid, 'Absence im Gespraech',  'Ca. 10 Sekunden',                NOW() - INTERVAL 22  DAY + INTERVAL 15 HOUR, NOW(), NOW(), @t_abs),
 (@uid, 'Unklares Ereignis',     'Typ unklar - Testfall unknown',  NOW() - INTERVAL 14  DAY + INTERVAL 13 HOUR, NOW(), NOW(), NULL),
 (@uid, 'Anfall morgens',        'Wieder mit Aura',                NOW() - INTERVAL 8   DAY + INTERVAL 6 HOUR,  NOW(), NOW(), @t_focal),
 (@uid, 'Myoklonien',            'Beide Arme',                     NOW() - INTERVAL 3   DAY + INTERVAL 7 HOUR,  NOW(), NOW(), @t_myo);

-- --- events ----------------------------------------------------------
INSERT INTO event (user_id, name, description, timestamp_when, created_at, modified_at) VALUES
 (@uid, 'EEG Kontrolle',       'Befund unauffaellig',            NOW() - INTERVAL 190 DAY, NOW(), NOW()),
 (@uid, 'Neurologie-Termin',   'Dosis angepasst',                NOW() - INTERVAL 120 DAY, NOW(), NOW()),
 (@uid, 'MRI',                 'Kein neuer Befund',              NOW() - INTERVAL 60  DAY, NOW(), NOW()),
 (@uid, 'Medikament gewechselt','Umstellung auf Levetiracetam',  NOW() - INTERVAL 30  DAY, NOW(), NOW());

-- --- medications: laufend / beendet / Notfall ------------------------
-- date_to weit in der Zukunft = "laufend"; ciphra mappt das auf ended_at NULL
-- (ONGOING_THRESHOLD_SECONDS in EpilepcBundleSerializer).
INSERT INTO medication (user_id, name, description, dosage, date_from, date_to, timestamp_prescription, created_at, modified_at, emergency_med) VALUES
 (@uid, 'Levetiracetam', 'morgens und abends', '500mg', NOW() - INTERVAL 200 DAY, '2099-12-31 00:00:00',    NOW() - INTERVAL 200 DAY, NOW(), NOW(), 0),
 (@uid, 'Lamotrigin',    'ausgeschlichen',     '100mg', NOW() - INTERVAL 300 DAY, NOW() - INTERVAL 90 DAY,  NOW() - INTERVAL 300 DAY, NOW(), NOW(), 0),
 (@uid, 'Midazolam',     'nur im Notfall',     '10mg',  NOW() - INTERVAL 200 DAY, '2099-12-31 00:00:00',    NOW() - INTERVAL 200 DAY, NOW(), NOW(), 1);

-- --- diary: landet in ciphra als PRIVAT --------------------------------
INSERT INTO diaryentry (user_id, title, content, timestamp_when, created_at, modified_at) VALUES
 (@uid, 'Schlecht geschlafen', 'Unruhige Nacht, morgens Aura gespuert.',        NOW() - INTERVAL 180 DAY, NOW(), NOW()),
 (@uid, 'Guter Tag',           'Keine Auffaelligkeiten, Sport gemacht.',        NOW() - INTERVAL 150 DAY, NOW(), NOW()),
 (@uid, 'Stress',              'Viel Arbeit, Kopfschmerzen am Abend.',          NOW() - INTERVAL 128 DAY, NOW(), NOW()),
 (@uid, 'Arzttermin',          'Neue Dosierung besprochen, zuversichtlich.',    NOW() - INTERVAL 119 DAY, NOW(), NOW()),
 (@uid, 'Muede',               'Den ganzen Tag erschoepft.',                    NOW() - INTERVAL 95  DAY, NOW(), NOW()),
 (@uid, 'Ferien',              'Eine Woche weg, keine Vorkommnisse.',           NOW() - INTERVAL 70  DAY, NOW(), NOW()),
 (@uid, 'Ruecksprache',        'Blutwerte kontrolliert, alles im Rahmen.',      NOW() - INTERVAL 52  DAY, NOW(), NOW()),
 (@uid, 'Schlechter Tag',      'Nach dem Anfall lange gebraucht.',              NOW() - INTERVAL 30  DAY, NOW(), NOW()),
 (@uid, 'Besser',              'Seit der Umstellung deutlich ruhiger.',         NOW() - INTERVAL 12  DAY, NOW(), NOW()),
 (@uid, 'Notiz',               'Aura-Muster wird erkennbar.',                   NOW() - INTERVAL 4   DAY, NOW(), NOW());

COMMIT;

-- --- Kontrolle -------------------------------------------------------
SELECT u.id, u.email, u.migrated_at,
       (SELECT COUNT(*) FROM seizure     WHERE user_id = u.id) AS seizures,
       (SELECT COUNT(*) FROM `event`     WHERE user_id = u.id) AS events,
       (SELECT COUNT(*) FROM medication  WHERE user_id = u.id) AS meds,
       (SELECT COUNT(*) FROM diaryentry  WHERE user_id = u.id) AS diary
  FROM `user` u WHERE u.email = 'migtest@epilepc.ch';

-- =====================================================================
-- AUFRAEUMEN nach dem Test — alles in einem Rutsch:
--
--   SET @uid = (SELECT id FROM `user` WHERE email = 'migtest@epilepc.ch');
--   DELETE FROM seizure         WHERE user_id = @uid;
--   DELETE FROM `event`         WHERE user_id = @uid;
--   DELETE FROM medication      WHERE user_id = @uid;
--   DELETE FROM diaryentry      WHERE user_id = @uid;
--   DELETE FROM migration_token WHERE user_id = @uid;
--   DELETE FROM `user`          WHERE id      = @uid;
--
-- Das ciphra-Konto, das beim Test entsteht, loeschst du dort separat
-- ueber Einstellungen -> Konto loeschen.
-- =====================================================================
