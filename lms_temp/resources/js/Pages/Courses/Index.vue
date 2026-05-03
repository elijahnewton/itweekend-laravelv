<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    courses: Array,
    progress: Object,
});

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const gradientMap = {
    'from-indigo-500 to-violet-600': 'linear-gradient(135deg,#6366f1,#7c3aed)',
    'from-blue-500 to-cyan-500': 'linear-gradient(135deg,#3b82f6,#06b6d4)',
    'from-violet-500 to-purple-600': 'linear-gradient(135deg,#8b5cf6,#9333ea)',
};

function getGradient(color) {
    return gradientMap[color] || 'linear-gradient(135deg,#6366f1,#7c3aed)';
}
</script>

<template>
    <Head title="Learn" />

    <component :is="isAuthenticated ? AuthenticatedLayout : GuestLayout">
        <template v-if="isAuthenticated" #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Learn
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                        Choose a Learning Track
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        Structured courses to take you from beginner to professional.
                    </p>
                </div>

                <div v-if="courses.length === 0" class="rounded-lg bg-blue-50 p-6 text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                    No learning tracks available yet. Check back soon!
                </div>

                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="course in courses"
                        :key="course.id"
                        :href="route('courses.show', course.slug)"
                        class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-md transition hover:-translate-y-1 hover:shadow-xl dark:bg-gray-800"
                    >
                        <!-- Gradient Header -->
                        <div
                            class="flex h-36 items-center justify-center text-5xl"
                            :style="{ background: getGradient(course.color) }"
                        >
                            {{ course.icon || '📚' }}
                        </div>

                        <!-- Card Body -->
                        <div class="flex flex-1 flex-col p-5">
                            <div class="mb-3 flex items-start justify-between">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ course.title }}
                                </h3>
                                <span class="ml-2 shrink-0 rounded-full border border-gray-200 bg-gray-50 px-2.5 py-0.5 text-xs font-medium text-gray-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ course.level }}
                                </span>
                            </div>

                            <p class="mb-4 flex-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ course.description }}
                            </p>

                            <div class="mb-4 flex gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>📂 {{ course.chapters_count }} chapters</span>
                                <span>📄 {{ course.lessons_count }} lessons</span>
                                <span v-if="course.estimated_hours">⏱ ~{{ course.estimated_hours }}h</span>
                            </div>

                            <!-- Progress Bar (authenticated) -->
                            <template v-if="isAuthenticated && progress[course.id]?.total > 0">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    <span>Progress</span>
                                    <span>{{ progress[course.id].pct }}%</span>
                                </div>
                                <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div
                                        class="h-full rounded-full bg-green-500 transition-all"
                                        :style="{ width: progress[course.id].pct + '%' }"
                                    />
                                </div>
                            </template>
                            <template v-else>
                                <div class="mt-auto rounded-lg bg-indigo-600 py-2 text-center text-sm font-semibold text-white transition group-hover:bg-indigo-700">
                                    Start Learning →
                                </div>
                            </template>
                        </div>
                    </Link>
                </div>
            </div>
        </div>
    </component>
</template>
