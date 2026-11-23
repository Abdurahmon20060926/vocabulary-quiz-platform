<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface QuestionStat {
  id: number
  position: number
  prompt: string
  total_answers: number
  correct_rate: number | null
  avg_time_ms: number | null
  timeout_count: number
  wrong_count: number
  focus_lost_count: number
  most_picked_wrong: string | null
}

interface PlayerStat {
  user_id: number
  name: string
  avatar_url: string | null
  attempts: number
  best_reached: number
  passed: boolean
  first_passed_at: string | null
  last_played_at: string
}

interface GuestStats {
  attempts: number
  passes: number
  best_reached: number
}

const props = defineProps<{
  quiz: { id: number; ulid: string; title: string; mode: string; runs_count: number; passes_count: number; questions_count: number }
  questionStats: QuestionStat[]
  playerStats: PlayerStat[]
  guestStats: GuestStats
  uniquePlayers: number
  passRate: number | null
}>()

function formatDate(d: string | null) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString()
}

function formatMs(ms: number | null) {
  if (!ms) return '-'
  return (ms / 1000).toFixed(1) + 's'
}
</script>

<template>
  <div>
    <div class="flex items-center gap-4 mb-8">
      <Link href="/studio" class="text-gray-400 hover:text-white transition-colors">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
      </Link>
      <div>
        <h1 class="text-2xl font-bold text-white">Analytics</h1>
        <p class="text-gray-400 text-sm">{{ quiz.title }}</p>
      </div>
      <div v-if="quiz.mode === 'casual'" class="ml-auto text-xs px-2 py-1 rounded-md bg-yellow-900/50 text-yellow-300 border border-yellow-700/50">
        Self-reported
      </div>
    </div>

    <!-- Summary cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
      <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 text-center">
        <p class="text-2xl font-black text-white">{{ quiz.runs_count }}</p>
        <p class="text-xs text-gray-400 mt-1">Total Runs</p>
      </div>
      <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 text-center">
        <p class="text-2xl font-black text-white">{{ uniquePlayers }}</p>
        <p class="text-xs text-gray-400 mt-1">Unique Players</p>
      </div>
      <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 text-center">
        <p class="text-2xl font-black" :class="passRate !== null ? 'text-green-400' : 'text-gray-500'">
          {{ passRate !== null ? passRate + '%' : '-' }}
        </p>
        <p class="text-xs text-gray-400 mt-1">Pass Rate</p>
      </div>
      <div class="bg-gray-900 rounded-xl border border-gray-800 p-4 text-center">
        <p class="text-2xl font-black text-white">{{ quiz.passes_count }}</p>
        <p class="text-xs text-gray-400 mt-1">Passes</p>
      </div>
    </div>

    <!-- Per-question stats -->
    <div class="mb-8">
      <h2 class="font-semibold text-gray-200 mb-4">Per-Question Stats</h2>
      <div class="space-y-3">
        <div v-for="(q, i) in questionStats" :key="q.id" class="bg-gray-900 rounded-xl border border-gray-800 p-4">
          <div class="flex items-start gap-3">
            <span class="text-xs font-mono text-gray-500 mt-1 w-5 shrink-0">{{ i + 1 }}</span>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-200 mb-3">{{ q.prompt }}</p>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div>
                  <p class="text-gray-500 mb-0.5">Correct rate</p>
                  <p class="font-semibold" :class="(q.correct_rate ?? 0) >= 50 ? 'text-green-400' : 'text-red-400'">
                    {{ q.correct_rate !== null ? q.correct_rate + '%' : 'No data' }}
                  </p>
                </div>
                <div>
                  <p class="text-gray-500 mb-0.5">Avg time</p>
                  <p class="font-semibold text-gray-200">{{ formatMs(q.avg_time_ms) }}</p>
                </div>
                <div>
                  <p class="text-gray-500 mb-0.5">Wrong</p>
                  <p class="font-semibold text-gray-200">{{ q.wrong_count }} <span class="text-gray-500">/ {{ q.timeout_count }}T / {{ q.focus_lost_count }}FL</span></p>
                </div>
                <div v-if="q.most_picked_wrong">
                  <p class="text-gray-500 mb-0.5">Top wrong pick</p>
                  <p class="font-medium text-red-300 truncate">{{ q.most_picked_wrong }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Per-player stats -->
    <div>
      <h2 class="font-semibold text-gray-200 mb-4">Players</h2>
      <div class="bg-gray-900 rounded-xl border border-gray-800 overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="border-b border-gray-800 text-xs text-gray-400">
              <th class="text-left px-4 py-3 font-medium">Player</th>
              <th class="text-right px-4 py-3 font-medium">Attempts</th>
              <th class="text-right px-4 py-3 font-medium">Best</th>
              <th class="text-right px-4 py-3 font-medium hidden sm:table-cell">First Pass</th>
              <th class="text-right px-4 py-3 font-medium hidden sm:table-cell">Last Played</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in playerStats" :key="p.user_id" class="border-b border-gray-800/50 hover:bg-gray-800/30 transition-colors">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <img v-if="p.avatar_url" :src="p.avatar_url" class="w-6 h-6 rounded-full" />
                  <div v-else class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center text-xs text-white">{{ p.name[0] }}</div>
                  <span class="text-gray-200">{{ p.name }}</span>
                  <svg v-if="p.passed" class="w-3.5 h-3.5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-300">{{ p.attempts }}</td>
              <td class="px-4 py-3 text-right text-gray-300">{{ p.best_reached }}/{{ quiz.questions_count }}</td>
              <td class="px-4 py-3 text-right text-gray-400 hidden sm:table-cell">{{ formatDate(p.first_passed_at) }}</td>
              <td class="px-4 py-3 text-right text-gray-400 hidden sm:table-cell">{{ formatDate(p.last_played_at) }}</td>
            </tr>

            <!-- Guest row -->
            <tr v-if="guestStats.attempts > 0" class="border-b border-gray-800/50">
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-gray-700 flex items-center justify-center">
                    <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <span class="text-gray-400 italic">Guests</span>
                </div>
              </td>
              <td class="px-4 py-3 text-right text-gray-300">{{ guestStats.attempts }}</td>
              <td class="px-4 py-3 text-right text-gray-300">{{ guestStats.best_reached }}/{{ quiz.questions_count }}</td>
              <td class="px-4 py-3 text-right text-gray-400 hidden sm:table-cell">-</td>
              <td class="px-4 py-3 text-right text-gray-400 hidden sm:table-cell">-</td>
            </tr>

            <tr v-if="playerStats.length === 0 && guestStats.attempts === 0">
              <td colspan="5" class="px-4 py-8 text-center text-gray-500 text-sm">No players yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
