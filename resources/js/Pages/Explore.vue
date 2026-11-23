<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import QuizCard from '@/Components/QuizCard.vue'

interface Quiz {
  ulid: string
  title: string
  description?: string
  mode: 'strict' | 'casual'
  seconds_per_question: number
  upvotes_count: number
  runs_count: number
  questions_count: number
  user: { name: string; avatar_url?: string }
}

interface PaginatedQuizzes {
  data: Quiz[]
  links: { url: string | null; label: string; active: boolean }[]
  meta: { current_page: number; last_page: number; total: number }
}

const props = defineProps<{
  quizzes: PaginatedQuizzes
  filters: { sort: string; mode: string; search: string }
}>()

const search = ref(props.filters.search || '')
const sort = ref(props.filters.sort || 'popular')
const mode = ref(props.filters.mode || 'all')

let searchTimer: ReturnType<typeof setTimeout>

function applyFilters() {
  router.get('/', {
    sort: sort.value,
    mode: mode.value,
    search: search.value || undefined,
  }, {
    preserveState: true,
    replace: true,
  })
}

watch(sort, applyFilters)
watch(mode, applyFilters)

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(applyFilters, 400)
}
</script>

<template>
  <div>
    <!-- Page header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-white mb-2">Explore Quizzes</h1>
      <p class="text-gray-400">Test your vocabulary with time-pressured, sudden-death quizzes.</p>
    </div>

    <!-- Filters -->
    <div class="flex flex-col sm:flex-row gap-3 mb-8">
      <!-- Search -->
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input
          v-model="search"
          @input="onSearchInput"
          type="text"
          placeholder="Search quizzes..."
          class="w-full pl-9 pr-4 py-2.5 rounded-lg bg-gray-900 border border-gray-700 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 transition-colors"
        />
      </div>

      <!-- Sort -->
      <select
        v-model="sort"
        class="px-3 py-2.5 rounded-lg bg-gray-900 border border-gray-700 text-sm text-gray-100 focus:outline-none focus:border-brand-500 cursor-pointer"
      >
        <option value="popular">Most Popular</option>
        <option value="played">Most Played</option>
        <option value="newest">Newest</option>
      </select>

      <!-- Mode filter -->
      <div class="flex rounded-lg border border-gray-700 overflow-hidden">
        <button
          v-for="m in ['all', 'strict', 'casual']"
          :key="m"
          @click="mode = m"
          class="px-3 py-2.5 text-sm font-medium transition-colors capitalize"
          :class="mode === m ? 'bg-brand-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
        >
          {{ m }}
        </button>
      </div>
    </div>

    <!-- Quiz grid -->
    <div v-if="quizzes.data.length" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
      <QuizCard v-for="quiz in quizzes.data" :key="quiz.ulid" :quiz="quiz" />
    </div>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <div class="w-16 h-16 rounded-2xl bg-gray-800 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-200 mb-2">No quizzes found</h3>
      <p class="text-gray-400 text-sm">
        {{ search ? `No results for "${search}"` : 'No quizzes have been published yet.' }}
      </p>
    </div>

    <!-- Pagination -->
    <div v-if="quizzes.meta?.last_page > 1" class="flex justify-center mt-8 gap-1">
      <template v-for="link in quizzes.links" :key="link.label">
        <a
          v-if="link.url"
          :href="link.url"
          class="px-3 py-1.5 rounded-lg text-sm transition-colors"
          :class="link.active ? 'bg-brand-600 text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
          v-html="link.label"
        />
        <span v-else class="px-3 py-1.5 text-sm text-gray-600" v-html="link.label" />
      </template>
    </div>
  </div>
</template>
