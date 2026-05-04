<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    course: Object,
    lesson: Object,
    completedLessonIds: Array,
    prevLesson: Object,
    nextLesson: Object,
});

const isCompleted = computed(() => props.completedLessonIds.includes(props.lesson.id));
const completing = ref(false);

function markComplete() {
    if (completing.value || isCompleted.value) return;
    completing.value = true;
    router.post(route('progress.complete'), { lesson_id: props.lesson.id }, {
        preserveScroll: true,
        onSuccess: () => { completing.value = false; },
        onError: () => { completing.value = false; },
    });
}
</script>

<template>
    <Head :title="lesson.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 text-sm">
                <Link :href="route('courses.index')" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    Courses
                </Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <Link :href="route('courses.show', course.slug)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    {{ course.title }}
                </Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ lesson.title }}</span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex gap-8">

                    <!-- Sidebar -->
                    <aside class="hidden w-64 shrink-0 lg:block">
                        <div class="sticky top-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-100 p-4 dark:border-gray-700">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {{ course.title }}
                                </p>
                            </div>
                            <nav class="max-h-[75vh] overflow-y-auto py-2">
                                <div v-for="chapter in course.chapters" :key="chapter.id" class="mb-1">
                                    <p class="px-4 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
                                        {{ chapter.title }}
                                    </p>
                                    <Link
                                        v-for="l in chapter.lessons"
                                        :key="l.id"
                                        :href="route('lessons.show', { course: course.slug, lesson: l.slug })"
                                        class="flex items-center gap-2 px-4 py-2 text-sm transition"
                                        :class="l.id === lesson.id
                                            ? 'bg-indigo-50 font-medium text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50'"
                                    >
                                        <span v-if="completedLessonIds.includes(l.id)" class="text-green-500">✓</span>
                                        <span v-else class="w-4 shrink-0 text-center text-gray-300">○</span>
                                        {{ l.title }}
                                    </Link>
                                </div>
                            </nav>
                        </div>
                    </aside>

                    <!-- Main Content -->
                    <main class="min-w-0 flex-1">
                        <div class="rounded-2xl bg-white shadow-sm dark:bg-gray-800">
                            <div class="border-b border-gray-100 px-8 py-6 dark:border-gray-700">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                            {{ lesson.title }}
                                        </h1>
                                        <p v-if="lesson.estimated_minutes" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            ⏱ {{ lesson.estimated_minutes }} min read
                                        </p>
                                    </div>
                                    <span
                                        v-if="isCompleted"
                                        class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400"
                                    >
                                        ✓ Completed
                                    </span>
                                </div>
                            </div>

                            <div class="px-8 py-6">
                                <!-- Lesson Content -->
                                <div
                                    v-if="lesson.content_html"
                                    class="prose prose-indigo max-w-none dark:prose-invert"
                                    v-html="lesson.content_html"
                                />

                                <!-- Code Example -->
                                <div v-if="lesson.code_example" class="mt-6">
                                    <h3 class="mb-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        Code Example
                                    </h3>
                                    <pre class="overflow-x-auto rounded-xl bg-gray-900 p-5 text-sm text-gray-100"><code>{{ lesson.code_example }}</code></pre>
                                </div>
                            </div>

                            <!-- Footer Navigation -->
                            <div class="flex items-center justify-between border-t border-gray-100 px-8 py-5 dark:border-gray-700">
                                <Link
                                    v-if="prevLesson"
                                    :href="route('lessons.show', { course: course.slug, lesson: prevLesson.slug })"
                                    class="flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                                >
                                    ← Previous
                                </Link>
                                <span v-else />

                                <button
                                    v-if="!isCompleted"
                                    @click="markComplete"
                                    :disabled="completing"
                                    class="rounded-lg bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60 transition"
                                >
                                    {{ completing ? 'Saving…' : 'Mark as Complete ✓' }}
                                </button>
                                <span v-else class="text-sm font-medium text-green-600 dark:text-green-400">✓ Completed</span>

                                <Link
                                    v-if="nextLesson"
                                    :href="route('lessons.show', { course: course.slug, lesson: nextLesson.slug })"
                                    class="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                                >
                                    Next →
                                </Link>
                                <span v-else />
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
