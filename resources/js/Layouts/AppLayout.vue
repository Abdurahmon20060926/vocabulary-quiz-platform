<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { index as exploreIndex } from '@/actions/App/Http/Controllers/ExploreController'

interface LayoutProps extends Record<string, unknown> {
  auth: {
    user: { id: number; name: string; avatar_url: string | null } | null
  }
  recentPlays: {
    quiz_ulid: string
    quiz_title: string
    quiz_status: string
    best_reached: number
    passed: boolean
  }[] | null
  filters?: { search?: string }
}

const page = usePage<LayoutProps>()
const user = computed(() => page.props.auth.user)
const recentPlays = computed(() => page.props.recentPlays ?? [])
const searchQuery = ref(page.props.filters?.search ?? '')

const showUserMenu = ref(false)

watch(() => page.props.filters?.search, search => {
  searchQuery.value = search ?? ''
})

function searchQuizzes() {
  const search = searchQuery.value.trim()

  router.get(exploreIndex.url({
    query: { search: search || undefined },
  }), {}, {
    preserveState: true,
    replace: true,
  })
}

function logout() {
  router.post('/logout')
}
</script>

<template>
  <div class="min-h-screen bg-gray-950 flex flex-col">
    <!-- Top Bar -->
    <header class="border-b border-gray-800 bg-gray-950/95 backdrop-blur sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 min-h-16 flex flex-wrap sm:flex-nowrap items-center justify-between gap-x-4 gap-y-2 py-2 sm:py-0">
        <!-- Logo + Search -->
        <Link href="/" class="flex items-center gap-2 shrink-0">
          <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.347.384a1 1 0 01-.746.334H8.355a1 1 0 01-.746-.334l-.347-.384z" />
            </svg>
          </div>
          <span class="font-bold text-lg text-white tracking-tight hidden sm:block">QuizVault</span>
        </Link>

        <form
          role="search"
          class="order-3 basis-full sm:order-2 sm:flex-1 min-w-0 sm:max-w-xl flex items-center gap-2"
          @submit.prevent="searchQuizzes"
        >
          <label for="topbar-search" class="sr-only">Search quizzes</label>
          <input
            id="topbar-search"
            v-model="searchQuery"
            type="search"
            placeholder="Search quizzes..."
            class="min-w-0 flex-1 px-3 py-2 rounded-lg bg-gray-900 border border-gray-700 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 transition-colors"
          />
          <button
            type="submit"
            aria-label="Search"
            class="shrink-0 flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-800 hover:bg-gray-700 text-gray-200 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <span class="hidden sm:inline text-sm">Search</span>
          </button>
        </form>

        <!-- Right: Nav items -->
        <div class="order-2 sm:order-3 ml-auto flex items-center gap-3">
          <Link
            v-if="user"
            href="/studio"
            class="hidden sm:flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium text-gray-300 hover:text-white hover:bg-gray-800 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            Creator Studio
          </Link>

          <a
            v-if="!user"
            href="/auth/google/redirect"
            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-500 text-white transition-colors"
          >
            <svg class="w-4 h-4" viewBox="0 0 24 24">
              <path fill="currentColor" d="M12.545 10.239v3.821h5.445c-.712 2.315-2.647 3.972-5.445 3.972a6.033 6.033 0 110-12.064c1.498 0 2.866.549 3.921 1.453l2.814-2.814A9.969 9.969 0 0012.545 2C7.021 2 2.543 6.477 2.543 12s4.478 10 10.002 10c8.396 0 10.249-7.85 9.426-11.748l-9.426-.013z"/>
            </svg>
            Sign in
          </a>

          <!-- User avatar menu -->
          <div v-if="user" class="relative">
            <button
              @click="showUserMenu = !showUserMenu"
              class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-gray-800 transition-colors"
            >
              <img
                v-if="user.avatar_url"
                :src="user.avatar_url"
                :alt="user.name"
                class="w-8 h-8 rounded-full object-cover"
              />
              <div v-else class="w-8 h-8 rounded-full bg-brand-600 flex items-center justify-center text-sm font-bold text-white">
                {{ user.name[0] }}
              </div>
            </button>

            <div
              v-if="showUserMenu"
              @click.outside="showUserMenu = false"
              class="absolute right-0 top-full mt-2 w-48 rounded-xl bg-gray-900 border border-gray-700 shadow-xl py-1 z-50"
            >
              <div class="px-3 py-2 border-b border-gray-700">
                <p class="text-sm font-medium text-white truncate">{{ user.name }}</p>
              </div>
              <Link href="/plays" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 transition-colors" @click="showUserMenu = false">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                My Plays
              </Link>
              <Link href="/studio" class="sm:hidden flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 transition-colors" @click="showUserMenu = false">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Creator Studio
              </Link>
              <button
                @click="logout(); showUserMenu = false"
                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-gray-300 hover:text-white hover:bg-gray-800 transition-colors text-left"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Sign out
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>

    <div class="flex flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 gap-8">
      <!-- Left Sidebar -->
      <aside class="hidden lg:flex flex-col w-64 shrink-0 gap-4">
        <!-- Recent plays for logged-in users -->
        <div v-if="user" class="bg-gray-900 rounded-xl border border-gray-800 p-4">
          <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Recent Plays</h3>
          <div v-if="recentPlays.length" class="space-y-2">
            <Link
              v-for="play in recentPlays"
              :key="play.quiz_ulid"
              :href="play.quiz_status === 'published' ? `/q/${play.quiz_ulid}` : '#'"
              class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800 transition-colors group"
            >
              <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"
                :class="play.passed ? 'bg-green-900/50 text-green-400' : 'bg-gray-800 text-gray-400'">
                <svg v-if="play.passed" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <p class="text-xs font-medium text-gray-200 truncate group-hover:text-white">
                  {{ play.quiz_status !== 'published' ? '(Unavailable)' : play.quiz_title }}
                </p>
                <p class="text-xs text-gray-500">{{ play.passed ? '✓ Passed' : `${play.best_reached} reached` }}</p>
              </div>
            </Link>
          </div>
          <p v-else class="text-xs text-gray-500">No plays yet.</p>
          <Link href="/plays" class="mt-3 block text-xs text-brand-400 hover:text-brand-300 transition-colors font-medium">
            Show all →
          </Link>
        </div>

        <!-- Sign-in card for guests -->
        <div v-else class="bg-gradient-to-br from-brand-900/30 to-gray-900 rounded-xl border border-brand-800/50 p-4">
          <div class="w-10 h-10 rounded-xl bg-brand-600/20 flex items-center justify-center mb-3">
            <svg class="w-5 h-5 text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <p class="text-sm font-medium text-gray-200 mb-1">Track your progress</p>
          <p class="text-xs text-gray-400 mb-3">Sign in to save your scores, vote on quizzes, and create your own.</p>
          <a
            href="/auth/google/redirect"
            class="flex items-center justify-center gap-2 w-full px-3 py-2 rounded-lg text-sm font-medium bg-brand-600 hover:bg-brand-500 text-white transition-colors"
          >
            Sign in with Google
          </a>
        </div>
      </aside>

      <!-- Main content -->
      <main class="flex-1 min-w-0">
        <slot />
      </main>
    </div>

    <!-- Click outside overlay -->
    <div v-if="showUserMenu" class="fixed inset-0 z-40" @click="showUserMenu = false" />
  </div>
</template>
