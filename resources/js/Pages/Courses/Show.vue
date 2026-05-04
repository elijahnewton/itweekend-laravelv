<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    course: Object,
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const openChapters = ref(
    props.course.chapters.reduce((acc, ch) => ({ ...acc, [ch.id]: true }), {})
);

function toggleChapter(id) {
    openChapters.value[id] = !openChapters.value[id];
}

const gradientMap = {
    'from-indigo-500 to-violet-600': 'linear-gradient(135deg,#6366f1,#7c3aed)',
    'from-blue-500 to-cyan-500': 'linear-gradient(135deg,#3b82f6,#06b6d4)',
    'from-violet-500 to-purple-600': 'linear-gradient(135deg,#8b5cf6,#9333ea)',
};

const gradient = computed(() => gradientMap[props.course.color] || 'linear-gradient(135deg,#6366f1,#7c3aed)');
</script>

<template>
    <Head :title="course.title" />

    <component :is="isAuthenticated ? AuthenticatedLayout : GuestLayout">
        <template v-if="isAuthenticated" #header>
            <div class="flex items-center gap-3">
                <Link :href="route('courses.index')" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    ← All Courses
                </Link>
                <span class="text-gray-300 dark:text-gray-600">/</span>
                <span class="text-gray-800 dark:text-gray-200 font-semibold">{{ course.title }}</span>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">

                <!-- Course Hero -->
                <div class="mb-8 overflow-hidden rounded-2xl shadow-lg">
                    <div
                        class="flex items-center gap-6 p-8"
                        :style="{ background: gradient }"
                    >
                        <div class="text-6xl">{{ course.icon || '📚' }}</div>
                        <div class="text-white">
                            <h1 class="text-3xl font-bold">{{ course.title }}</h1>
                            <p class="mt-1 text-white/80">{{ course.description }}</p>
                            <div class="mt-3 flex gap-4 text-sm text-white/70">
                                <span v-if="course.level">{{ course.level }}</span>
                                <span v-if="course.estimated_hours">~{{ course.estimated_hours }}h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chapter Accordion -->
                <div class="space-y-4">
                    <div
                        v-for="chapter in course.chapters"
                        :key="chapter.id"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                    >
                        <button
                            @click="toggleChapter(chapter.id)"
                            class="flex w-full items-center justify-between px-5 py-4 text-left"
                        >
                            <span class="font-semibold text-gray-800 dark:text-white">
                                {{ chapter.title }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ chapter.lessons.length }} lessons
                                <span class="ml-2">{{ openChapters[chapter.id] ? '▲' : '▼' }}</span>
                            </span>
                        </button>

                        <div v-show="openChapters[chapter.id]" class="border-t border-gray-100 dark:border-gray-700">
                            <Link
                                v-for="lesson in chapter.lessons"
                                :key="lesson.id"
                                :href="route('lessons.show', { course: course.slug, lesson: lesson.slug })"
                                class="flex items-center justify-between px-5 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-700/50 transition"
                            >
                                <span class="text-gray-700 dark:text-gray-300">{{ lesson.title }}</span>
                                <span v-if="lesson.estimated_minutes" class="text-xs text-gray-400">
                                    {{ lesson.estimated_minutes }}min
                                </span>
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="!isAuthenticated" class="mt-8 rounded-xl bg-indigo-50 p-6 text-center dark:bg-indigo-900/20">
                    <p class="text-gray-700 dark:text-gray-300">
                        <Link :href="route('login')" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">Log in</Link>
                        or
                        <Link :href="route('register')" class="font-semibold text-indigo-600 hover:underline dark:text-indigo-400">register</Link>
                        to track your progress.
                    </p>
                </div>
            </div>
        </div>
    </component>
</template>
