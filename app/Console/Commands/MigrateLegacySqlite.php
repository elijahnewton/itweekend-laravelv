<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacySqlite extends Command
{
    protected $signature = 'legacy:migrate
                            {--sqlite= : Absolute path to the legacy lms.sqlite file}
                            {--dry-run : Preview what would be migrated without writing to the database}';

    protected $description = 'Migrate users and progress from the legacy lms.sqlite database to PostgreSQL';

    public function handle(): int
    {
        $sqlitePath = $this->option('sqlite');
        $dryRun = (bool) $this->option('dry-run');

        if ($sqlitePath === null) {
            $candidates = [
                base_path('../legacy/data/lms.sqlite'),
                base_path('legacy/data/lms.sqlite'),
            ];
            foreach ($candidates as $candidate) {
                if (file_exists($candidate)) {
                    $sqlitePath = $candidate;
                    break;
                }
            }
        }

        if ($sqlitePath === null || ! file_exists($sqlitePath)) {
            $this->error('Legacy SQLite file not found. Provide the path with --sqlite=/path/to/lms.sqlite');
            return Command::FAILURE;
        }

        $this->info("Reading legacy database: {$sqlitePath}");
        if ($dryRun) {
            $this->warn('DRY-RUN mode: no changes will be written.');
        }

        $sqlite = new \PDO('sqlite:' . $sqlitePath, '', '', [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        // ── 1. Users ──────────────────────────────────────────────────────────
        $this->line('');
        $this->info('[1/2] Migrating users…');

        $legacyUsers = $sqlite->query(
            'SELECT id, email, name, password, role, created_at FROM users'
        )->fetchAll();

        $userIdMap = [];
        $migratedUsers = $skippedUsers = 0;

        foreach ($legacyUsers as $lu) {
            $existing = DB::table('users')->where('email', $lu['email'])->first();

            if ($existing) {
                $userIdMap[$lu['id']] = $existing->id;
                $skippedUsers++;
                $this->line("  SKIP (exists): {$lu['email']}");
                continue;
            }

            if (! $dryRun) {
                $newId = DB::table('users')->insertGetId([
                    'name'               => $lu['name'] ?: $lu['email'],
                    'email'              => $lu['email'],
                    'password'           => $lu['password'],
                    'role'               => $lu['role'] ?? 'student',
                    'email_verified_at'  => now(),
                    'created_at'         => $lu['created_at'] ?? now(),
                    'updated_at'         => $lu['created_at'] ?? now(),
                ]);
                $userIdMap[$lu['id']] = $newId;
            } else {
                $userIdMap[$lu['id']] = 0; // placeholder for dry-run
            }

            $migratedUsers++;
            $this->line("  MIGRATE: {$lu['email']}");
        }

        $this->info("  → {$migratedUsers} migrated, {$skippedUsers} skipped.");

        // ── 2. User progress ──────────────────────────────────────────────────
        $this->line('');
        $this->info('[2/2] Migrating user_progress…');

        $legacyLessons = $sqlite->query(
            'SELECT l.id, l.slug, t.slug AS topic_slug, lg.slug AS lang_slug
             FROM lessons l
             JOIN topics t ON l.topic_id = t.id
             JOIN languages lg ON t.language_id = lg.id'
        )->fetchAll();

        $lessonMap = [];
        foreach ($legacyLessons as $ll) {
            $newLesson = DB::table('lessons')
                ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->join('courses', 'chapters.course_id', '=', 'courses.id')
                ->where('lessons.slug', $ll['slug'])
                ->where('courses.slug', $ll['lang_slug'])
                ->select('lessons.id')
                ->first();

            if ($newLesson) {
                $lessonMap[$ll['id']] = $newLesson->id;
            }
        }

        $legacyProgress = $sqlite->query(
            'SELECT user_id, lesson_id, last_accessed_at, completed_at, quiz_best_score
             FROM user_progress WHERE lesson_id IS NOT NULL'
        )->fetchAll();

        $migratedProgress = $skippedProgress = 0;

        foreach ($legacyProgress as $lp) {
            $newUserId   = $userIdMap[$lp['user_id']] ?? null;
            $newLessonId = $lessonMap[$lp['lesson_id']] ?? null;

            if (! $newUserId || ! $newLessonId) {
                $skippedProgress++;
                continue;
            }

            $exists = DB::table('user_progress')
                ->where('user_id', $newUserId)
                ->where('lesson_id', $newLessonId)
                ->exists();

            if ($exists) {
                $skippedProgress++;
                continue;
            }

            if (! $dryRun) {
                DB::table('user_progress')->insert([
                    'user_id'          => $newUserId,
                    'lesson_id'        => $newLessonId,
                    'completed_at'     => $lp['completed_at'] ?: null,
                    'score'            => $lp['quiz_best_score'] > 0 ? $lp['quiz_best_score'] : null,
                    'last_accessed_at' => $lp['last_accessed_at'] ?? now(),
                    'created_at'       => $lp['last_accessed_at'] ?? now(),
                    'updated_at'       => $lp['last_accessed_at'] ?? now(),
                ]);
            }

            $migratedProgress++;
        }

        $this->info("  → {$migratedProgress} migrated, {$skippedProgress} skipped.");

        $this->line('');
        $this->info('Migration complete.');
        $this->table(
            ['Entity', 'Migrated', 'Skipped'],
            [
                ['Users',    $migratedUsers,    $skippedUsers],
                ['Progress', $migratedProgress, $skippedProgress],
            ]
        );

        return Command::SUCCESS;
    }
}
