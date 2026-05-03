<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    courses: { type: Array, default: () => [] },
    progress: { type: Object, default: () => ({}) },
    recentLessons: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">

                <!-- Welcome Banner -->
                <div class="overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-500 to-violet-600 p-8 text-white shadow">
                    <h3 class="text-2xl font-bold">Welcome back! 👋</h3>
                    <p class="mt-1 text-indigo-100">Continue where you left off or explore new courses.</p>
                    <Link :href="route('courses.index')" class="mt-4 inline-block rounded-lg bg-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/30 transition">
                        Browse All Courses →
                    </Link>
                </div>

                <!-- My Courses Progress -->
                <div v-if="courses.length > 0">
                    <h3 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">My Courses</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="course in courses"
                            :key="course.id"
                            :href="route('courses.show', course.slug)"
                            class="rounded-xl bg-white p-5 shadow-sm hover:shadow-md transition dark:bg-gray-800"
                        >
                            <div class="flex items-center gap-3 mb-3">
                                <span class="text-2xl">{{ course.icon || '📚' }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ course.title }}</span>
                            </div>
                            <template v-if="progress[course.id]?.total > 0">
                                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    <span>{{ progress[course.id].completed }} / {{ progress[course.id].total }} lessons</span>
                                    <span>{{ progress[course.id].pct }}%</span>
                                </div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div
                                        class="h-full rounded-full bg-indigo-500 transition-all"
                                        :style="{ width: progress[course.id].pct + '%' }"
                                    />
                                </div>
                            </template>
                            <p v-else class="text-xs text-gray-400">Not started yet</p>
                        </Link>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                    <div class="p-6 text-center">
                        <p class="text-gray-500 dark:text-gray-400">You haven't started any courses yet.</p>
                        <Link :href="route('courses.index')" class="mt-3 inline-block text-indigo-600 font-medium hover:underline dark:text-indigo-400">
                            Browse courses →
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

