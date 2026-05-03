<?php

namespace App\Services;

use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\File;
use League\CommonMark\CommonMarkConverter;
use Spatie\YamlFrontMatter\YamlFrontMatter;
use Symfony\Component\Yaml\Yaml;

class ContentIngestionService
{
    public function syncFromContentDirectory(string $path): int
    {
        $count = 0;
        $courseDirs = File::directories($path);

        foreach ($courseDirs as $courseDir) {
            $metaFile = $courseDir . '/course.yml';
            if (! File::exists($metaFile)) {
                continue;
            }

            $meta = Yaml::parseFile($metaFile);
            $course = Course::updateOrCreate(
                ['slug' => $meta['slug']],
                [
                    'title' => $meta['title'],
                    'description' => $meta['description'] ?? null,
                    'level' => $meta['level'] ?? null,
                    'icon' => $meta['icon'] ?? null,
                    'color' => $meta['color'] ?? null,
                    'estimated_hours' => $meta['estimated_hours'] ?? null,
                    'order_index' => $meta['order_index'] ?? 0,
                    'is_published' => $meta['is_published'] ?? true,
                ]
            );

            $chapterDirs = collect(File::directories($courseDir))->sortBy(fn ($d) => basename($d));

            foreach ($chapterDirs as $chapterIndex => $chapterDir) {
                $chapterMetaFile = $chapterDir . '/chapter.yml';
                $chapterMeta = File::exists($chapterMetaFile) ? Yaml::parseFile($chapterMetaFile) : [];

                $chapter = Chapter::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $chapterMeta['title'] ?? basename($chapterDir)],
                    ['order_index' => $chapterMeta['order_index'] ?? $chapterIndex]
                );

                $lessonFiles = collect(File::files($chapterDir))
                    ->filter(fn ($f) => $f->getExtension() === 'md')
                    ->sortBy(fn ($f) => $f->getFilename());

                foreach ($lessonFiles as $file) {
                    $document = YamlFrontMatter::parse(File::get($file->getPathname()));
                    $converter = new CommonMarkConverter();
                    $html = $converter->convert($document->body())->getContent();

                    Lesson::updateOrCreate(
                        ['chapter_id' => $chapter->id, 'slug' => $document->matter('slug')],
                        [
                            'title' => $document->matter('title'),
                            'content_html' => $html,
                            'code_example' => $document->matter('code_example'),
                            'code_language' => $document->matter('code_language', 'plaintext'),
                            'video_url' => $document->matter('video_url'),
                            'order_index' => $document->matter('order', 0),
                            'estimated_minutes' => $document->matter('estimated_minutes'),
                        ]
                    );
                    $count++;
                }
            }
        }

        return $count;
    }
}
