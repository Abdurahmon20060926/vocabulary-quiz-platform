<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface Stat {
  attempts: number
  best_reached: number
  passed: boolean
  first_passed_at: string | null
  last_played_at: string
  quiz: {
    ulid: string
    title: string
    mode: string
    status: string
    questions_count: number
  } | null
}

interface PaginatedStats {
  data: Stat[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number }
}

defineProps<{ stats: PaginatedStats }>()

function formatDate(d: string | null) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString()
}
</script>

<template>
  <div>
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-white mb-2">My Plays</h1>
      <p class="text-gray-400">Every quiz you've attempted, most recent first.</p>
    </div>

    <div v-if="stats.data.length" class="space-y-3">
      <div
        v-for="stat in stats.data"
        :key="stat.quiz?.ulid || Math.random()"
        class="bg-gray-900 rounded-xl border border-gray-800 p-5"
      >
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <span
                v-if="stat.quiz && stat.quiz.status === 'published'"
                class="text-xs px-2 py-0.5 rounded-md"
                :class="stat.quiz.mode === 'strict' ? 'bg-orange-900/50 text-orange-300' : 'bg-blue-900/50 text-blue-300'"
              >
                {{ stat.quiz.mode }}
              </span>
              <span v-if="stat.passed" class="inline-flex items-center gap-1 text-xs text-green-400 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Passed
              </span>
            </div>

            <h3 class="font-semibold text-gray-100">
              <span v-if="!stat.quiz || stat.quiz.status !== 'published'" class="text-gray-500 italic">
                Unavailable
              </span>
              <Link v-else :href="`/q/${stat.quiz.ulid}`" class="hover:text-white transition-colors">
                {{ stat.quiz.title }}
              </Link>
            </h3>

            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 flex-wrap">
              <span>{{ stat.attempts }} attempt{{ stat.attempts !== 1 ? 's' : '' }}</span>
              <span>Best: {{ stat.best_reached }}/{{ stat.quiz?.questions_count ?? '?' }}</span>
              <span v-if="stat.first_passed_at">First passed {{ formatDate(stat.first_passed_at) }}</span>
              <span>Last played {{ formatDate(stat.last_played_at) }}</span>
            </div>
          </div>

          <Link
            v-if="stat.quiz && stat.quiz.status === 'published'"
            :href="`/q/${stat.quiz.ulid}`"
            class="shrink-0 px-4 py-2 text-sm font-medium bg-brand-600/20 hover:bg-brand-600/40 text-brand-300 rounded-lg border border-brand-700/50 transition-colors"
          >
            Play again
          </Link>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <div class="w-16 h-16 rounded-2xl bg-gray-800 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-200 mb-2">No plays yet</h3>
      <p class="text-gray-400 text-sm mb-6">Start playing quizzes to track your progress here.</p>
      <Link href="/" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-medium rounded-xl transition-colors">
        Explore quizzes
      </Link>
    </div>

    <!-- Pagination -->
    <div v-if="stats.meta?.last_page > 1" class="flex justify-center mt-8 gap-1">
      <template v-for="link in stats.links" :key="link.label">
        <a v-if="link.url" :href="link.url" class="px-3 py-1.5 rounded-lg text-sm transition-colors" :class="link.active ? 'bg-brand-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'" v-html="link.label" />
        <span v-else class="px-3 py-1.5 text-sm text-gray-600" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
