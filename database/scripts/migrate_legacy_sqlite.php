<?php

/**
 * Legacy SQLite → PostgreSQL User Data Migration Script
 *
 * Migrates users and their lesson progress from the legacy lms.sqlite database
 * into the new Laravel/PostgreSQL schema.
 *
 * Usage (run from the repository root after moving lms_temp to root):
 *   php database/scripts/migrate_legacy_sqlite.php [--sqlite=/path/to/lms.sqlite]
 *
 * The script maps:
 *   legacy.users              → users
 *   legacy.user_progress      → user_progress  (lessons matched by slug via topics→chapters)
 *
 * It does NOT migrate legacy languages/topics/lessons because those are managed
 * through the new content:sync pipeline (markdown files → ContentIngestionService).
 *
 * Run after:
 *   php artisan migrate
 *   php artisan content:sync   (or php artisan db:seed --class=ContentSeeder)
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// ─── Bootstrap the Laravel application ──────────────────────────────────────
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ─── Resolve the SQLite path ─────────────────────────────────────────────────
$sqlitePath = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--sqlite=')) {
        $sqlitePath = substr($arg, strlen('--sqlite='));
        break;
    }
}
if ($sqlitePath === null) {
    // Default: legacy/data/lms.sqlite relative to repo root
    $sqlitePath = base_path('../legacy/data/lms.sqlite');
    if (! file_exists($sqlitePath)) {
        $sqlitePath = base_path('legacy/data/lms.sqlite');
    }
}

if (! file_exists($sqlitePath)) {
    fwrite(STDERR, "Error: SQLite file not found at {$sqlitePath}\n");
    fwrite(STDERR, "Usage: php database/scripts/migrate_legacy_sqlite.php [--sqlite=/absolute/path/to/lms.sqlite]\n");
    exit(1);
}

echo "Connecting to legacy SQLite database: {$sqlitePath}\n";

// ─── Open legacy SQLite ───────────────────────────────────────────────────────
$sqlite = new PDO('sqlite:' . $sqlitePath, '', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

// ─── 1. Migrate users ─────────────────────────────────────────────────────────
echo "\n[1/2] Migrating users...\n";

$legacyUsers = $sqlite->query(
    "SELECT id, email, name, password, role, created_at FROM users"
)->fetchAll();

$userIdMap = []; // legacy_id => new_id
$migratedUsers = 0;
$skippedUsers = 0;

foreach ($legacyUsers as $lu) {
    $exists = DB::table('users')->where('email', $lu['email'])->first();

    if ($exists) {
        $userIdMap[$lu['id']] = $exists->id;
        $skippedUsers++;
        echo "  SKIP (already exists): {$lu['email']}\n";
        continue;
    }

    $newId = DB::table('users')->insertGetId([
        'name'              => $lu['name'] ?: $lu['email'],
        'email'             => $lu['email'],
        'password'          => $lu['password'], // already bcrypt-hashed
        'role'              => $lu['role'] ?? 'student',
        'email_verified_at' => now(),
        'created_at'        => $lu['created_at'] ?? now(),
        'updated_at'        => $lu['created_at'] ?? now(),
    ]);

    $userIdMap[$lu['id']] = $newId;
    $migratedUsers++;
    echo "  MIGRATED: {$lu['email']} (legacy id={$lu['id']} → new id={$newId})\n";
}

echo "  Done: {$migratedUsers} migrated, {$skippedUsers} skipped.\n";

// ─── 2. Migrate user_progress ─────────────────────────────────────────────────
echo "\n[2/2] Migrating user_progress...\n";

// Build a map: legacy lesson_slug → new lesson_id
// Legacy: lessons.topic_id → topics.slug → chapters.title (same slug convention)
// New: chapters keyed by title, lessons keyed by slug
$legacyLessons = $sqlite->query(
    "SELECT l.id, l.slug, l.topic_id, t.slug AS topic_slug, lg.slug AS lang_slug
     FROM lessons l
     JOIN topics t ON l.topic_id = t.id
     JOIN languages lg ON t.language_id = lg.id"
)->fetchAll();

$legacyLessonMap = []; // legacy_lesson_id → new_lesson_id
foreach ($legacyLessons as $ll) {
    // Match by lesson slug within the scope of the chapter slug
    // New schema: lesson slug is scoped to chapter_id (UNIQUE chapter_id, slug)
    $newLesson = DB::table('lessons')
        ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
        ->join('courses', 'chapters.course_id', '=', 'courses.id')
        ->where('lessons.slug', $ll['slug'])
        ->where('courses.slug', $ll['lang_slug'])
        ->select('lessons.id')
        ->first();

    if ($newLesson) {
        $legacyLessonMap[$ll['id']] = $newLesson->id;
    }
}

$legacyProgress = $sqlite->query(
    "SELECT user_id, lesson_id, last_accessed_at, completed_at,
            quiz_best_score
     FROM user_progress
     WHERE lesson_id IS NOT NULL"
)->fetchAll();

$migratedProgress = 0;
$skippedProgress = 0;

foreach ($legacyProgress as $lp) {
    $newUserId   = $userIdMap[$lp['user_id']] ?? null;
    $newLessonId = $legacyLessonMap[$lp['lesson_id']] ?? null;

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

    DB::table('user_progress')->insert([
        'user_id'         => $newUserId,
        'lesson_id'       => $newLessonId,
        'completed_at'    => $lp['completed_at'] ?: null,
        'score'           => $lp['quiz_best_score'] > 0 ? $lp['quiz_best_score'] : null,
        'last_accessed_at'=> $lp['last_accessed_at'] ?? now(),
        'created_at'      => $lp['last_accessed_at'] ?? now(),
        'updated_at'      => $lp['last_accessed_at'] ?? now(),
    ]);

    $migratedProgress++;
}

echo "  Done: {$migratedProgress} migrated, {$skippedProgress} skipped (unmapped/duplicate).\n";

echo "\nMigration complete.\n";
echo "  Users:    {$migratedUsers} migrated, {$skippedUsers} skipped\n";
echo "  Progress: {$migratedProgress} migrated, {$skippedProgress} skipped\n";
