<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
defineProps<{ quiz: { title: string } }>()
</script>

<template>
  <div class="flex flex-col items-center justify-center min-h-[400px] text-center px-4">
    <div class="w-16 h-16 rounded-2xl bg-gray-800 flex items-center justify-center mb-6">
      <svg class="w-8 h-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-white mb-2">Quiz Unavailable</h1>
    <p class="text-gray-400 mb-6 max-w-sm">
      "{{ quiz.title }}" is no longer available. The creator may have unpublished it.
    </p>
    <Link href="/" class="px-6 py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-colors">
      Explore quizzes
    </Link>
  </div>
</template>
