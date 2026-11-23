<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface Quiz {
  ulid: string
  title: string
  description?: string
  mode: 'strict' | 'casual'
  seconds_per_question: number
  upvotes_count: number
  runs_count: number
  questions_count: number
  user: {
    name: string
    avatar_url?: string
  }
}

defineProps<{ quiz: Quiz }>()
</script>

<template>
  <Link
    :href="`/q/${quiz.ulid}`"
    class="group bg-gray-900 rounded-xl border border-gray-800 p-5 hover:border-gray-600 hover:bg-gray-850 transition-all duration-200 flex flex-col gap-3 cursor-pointer"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2 mb-1.5">
          <span
            class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold uppercase tracking-wide"
            :class="quiz.mode === 'strict' ? 'bg-orange-900/50 text-orange-300 border border-orange-700/50' : 'bg-blue-900/50 text-blue-300 border border-blue-700/50'"
          >
            {{ quiz.mode }}
          </span>
          <span class="text-xs text-gray-500">{{ quiz.seconds_per_question }}s / Q</span>
        </div>
        <h3 class="font-semibold text-gray-100 group-hover:text-white transition-colors leading-snug line-clamp-2">
          {{ quiz.title }}
        </h3>
      </div>
    </div>

    <div class="flex items-center gap-2 mt-auto">
      <img
        v-if="quiz.user?.avatar_url"
        :src="quiz.user.avatar_url"
        :alt="quiz.user.name"
        class="w-5 h-5 rounded-full object-cover"
      />
      <div v-else class="w-5 h-5 rounded-full bg-brand-600 flex items-center justify-center text-xs text-white font-bold">
        {{ quiz.user?.name?.[0] || '?' }}
      </div>
      <span class="text-xs text-gray-400 truncate">{{ quiz.user?.name }}</span>
    </div>

    <div class="flex items-center gap-4 text-xs text-gray-500 border-t border-gray-800 pt-3">
      <span class="flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ quiz.questions_count }} Qs
      </span>
      <span class="flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5" />
        </svg>
        {{ quiz.upvotes_count }}
      </span>
      <span class="flex items-center gap-1">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ quiz.runs_count }} plays
      </span>
    </div>
  </Link>
</template>
