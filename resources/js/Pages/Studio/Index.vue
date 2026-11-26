<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

interface StudioQuiz {
  id: number
  ulid: string
  title: string | null
  mode: 'strict' | 'casual' | null
  status: string
  is_listed: boolean
  questions_count: number
  seconds_per_question: number | null
  runs_count: number
  upvotes_count: number
  published_at: string | null
  updated_at: string
}

const props = defineProps<{ quizzes: StudioQuiz[] }>()

const quizzes = ref(props.quizzes)
const loading = ref<string | null>(null)
const errorMsg = ref<string | null>(null)

function formatDate(d: string | null) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString()
}

async function deleteQuiz(quiz: StudioQuiz) {
  if (!confirm(`Delete "${quiz.title}"? This cannot be undone.`)) return
  loading.value = quiz.ulid + ':delete'
  try {
    await axios.delete(`/studio/quizzes/${quiz.ulid}`)
    quizzes.value = quizzes.value.filter(q => q.ulid !== quiz.ulid)
  } catch (err: any) {
    errorMsg.value = err.response?.data?.error || 'Failed to delete.'
  }
  loading.value = null
}

async function unpublish(quiz: StudioQuiz) {
  if (!confirm(`Unpublish "${quiz.title}"? Players won't be able to play it.`)) return
  loading.value = quiz.ulid + ':unpublish'
  try {
    await axios.post(`/studio/quizzes/${quiz.ulid}/unpublish`)
    const q = quizzes.value.find(q => q.ulid === quiz.ulid)
    if (q) { q.status = 'unpublished'; q.is_listed = false }
  } catch (err: any) {
    errorMsg.value = err.response?.data?.error || 'Failed to unpublish.'
  }
  loading.value = null
}

async function duplicate(quiz: StudioQuiz) {
  loading.value = quiz.ulid + ':duplicate'
  try {
    const res = await axios.post(`/studio/quizzes/${quiz.ulid}/duplicate`)
    router.visit(res.data.redirect)
  } catch (err: any) {
    errorMsg.value = err.response?.data?.error || 'Failed to duplicate.'
    loading.value = null
  }
}

const showPublishModal = ref(false)
const publishTarget = ref<StudioQuiz | null>(null)
const publishIsListed = ref(true)

function openPublishModal(quiz: StudioQuiz) {
  publishTarget.value = quiz
  publishIsListed.value = true
  showPublishModal.value = true
}

async function confirmPublish() {
  if (!publishTarget.value) return
  const quiz = publishTarget.value
  loading.value = quiz.ulid + ':publish'
  showPublishModal.value = false
  try {
    await axios.post(`/studio/quizzes/${quiz.ulid}/publish`, { is_listed: publishIsListed.value })
    const q = quizzes.value.find(q => q.ulid === quiz.ulid)
    if (q) { q.status = 'published'; q.is_listed = publishIsListed.value; q.published_at = new Date().toISOString() }
  } catch (err: any) {
    const errors = err.response?.data?.errors
    errorMsg.value = errors ? Object.values(errors).flat().join('. ') : 'Failed to publish.'
  }
  loading.value = null
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-3xl font-bold text-white mb-1">Creator Studio</h1>
        <p class="text-gray-400">Create and manage your quizzes.</p>
      </div>
      <Link
        href="/studio/quizzes/create"
        class="flex items-center gap-2 px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-colors"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        New Quiz
      </Link>
    </div>

    <div v-if="errorMsg" class="mb-6 p-3 bg-red-900/30 border border-red-700/50 rounded-lg text-sm text-red-300 flex items-center justify-between">
      <span>{{ errorMsg }}</span>
      <button @click="errorMsg = null" class="ml-4 text-red-400 hover:text-red-200">✕</button>
    </div>

    <div v-if="quizzes.length" class="space-y-3">
      <div
        v-for="quiz in quizzes"
        :key="quiz.ulid"
        class="bg-gray-900 rounded-xl border border-gray-800 p-5"
      >
        <div class="flex items-start gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
              <span class="text-xs px-2 py-0.5 rounded-md border"
                :class="{
                  'bg-green-900/50 text-green-300 border-green-700/50': quiz.status === 'published',
                  'bg-yellow-900/50 text-yellow-300 border-yellow-700/50': quiz.status === 'draft',
                  'bg-gray-700/50 text-gray-400 border-gray-600/50': quiz.status === 'unpublished',
                }">
                {{ quiz.status }}
              </span>
              <span v-if="quiz.status === 'published'" class="text-xs text-gray-500">
                {{ quiz.is_listed ? 'Listed' : 'Unlisted' }}
              </span>
              <span class="text-xs px-2 py-0.5 rounded-md" :class="quiz.mode === 'strict' ? 'bg-orange-900/50 text-orange-300' : 'bg-blue-900/50 text-blue-300'">
                {{ quiz.mode }}
              </span>
            </div>

            <h3 class="font-semibold text-gray-100 mb-1">{{ quiz.title || 'Untitled draft' }}</h3>
            <div class="flex items-center gap-4 text-xs text-gray-500 flex-wrap">
              <span>{{ quiz.mode ? `${quiz.mode} · ${quiz.seconds_per_question ?? 'Timer not set'}${quiz.seconds_per_question ? 's' : ''}` : 'Mode not set' }}</span>
              <span>{{ quiz.questions_count }} questions</span>
              <span>{{ quiz.runs_count }} plays</span>
              <span>{{ quiz.upvotes_count }} votes</span>
              <span v-if="quiz.published_at">Published {{ formatDate(quiz.published_at) }}</span>
              <span v-else>Updated {{ formatDate(quiz.updated_at) }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 flex-wrap shrink-0">
            <!-- Edit (draft only) -->
            <Link
              v-if="quiz.status === 'draft'"
              :href="`/studio/quizzes/${quiz.ulid}/edit`"
              class="px-3 py-1.5 text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors border border-gray-700"
            >
              Edit
            </Link>

            <!-- View (published) -->
            <Link
              v-if="quiz.status === 'published'"
              :href="`/q/${quiz.ulid}`"
              class="px-3 py-1.5 text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors border border-gray-700"
            >
              View
            </Link>

            <!-- Stats -->
            <Link
              :href="`/studio/quizzes/${quiz.ulid}/stats`"
              class="px-3 py-1.5 text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors border border-gray-700"
            >
              Stats
            </Link>

            <!-- Publish (draft) -->
            <button
              v-if="quiz.status === 'draft'"
              @click="openPublishModal(quiz)"
              :disabled="!!loading"
              class="px-3 py-1.5 text-xs font-medium bg-green-700 hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50"
            >
              Publish
            </button>

            <!-- Publish again (unpublished) -->
            <button
              v-if="quiz.status === 'unpublished'"
              @click="openPublishModal(quiz)"
              :disabled="!!loading"
              class="px-3 py-1.5 text-xs font-medium bg-green-700 hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50"
            >
              Republish
            </button>

            <!-- Unpublish (published) -->
            <button
              v-if="quiz.status === 'published'"
              @click="unpublish(quiz)"
              :disabled="!!loading"
              class="px-3 py-1.5 text-xs font-medium bg-yellow-700/80 hover:bg-yellow-700 text-yellow-100 rounded-lg transition-colors disabled:opacity-50"
            >
              Unpublish
            </button>

            <!-- Duplicate -->
            <button
              @click="duplicate(quiz)"
              :disabled="!!loading"
              class="px-3 py-1.5 text-xs font-medium bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg transition-colors border border-gray-700 disabled:opacity-50"
            >
              Duplicate
            </button>

            <!-- Delete (draft only) -->
            <button
              v-if="quiz.status === 'draft'"
              @click="deleteQuiz(quiz)"
              :disabled="!!loading"
              class="px-3 py-1.5 text-xs font-medium bg-red-900/40 hover:bg-red-900/70 text-red-400 hover:text-red-300 rounded-lg transition-colors border border-red-800/50 disabled:opacity-50"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center py-20 text-center">
      <div class="w-16 h-16 rounded-2xl bg-gray-800 flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
        </svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-200 mb-2">No quizzes yet</h3>
      <p class="text-gray-400 text-sm mb-6">Create your first quiz to get started.</p>
      <Link href="/studio/quizzes/create" class="px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-medium rounded-xl transition-colors">
        Create a quiz
      </Link>
    </div>

    <!-- Publish Modal -->
    <div v-if="showPublishModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70" @click="showPublishModal = false" />
      <div class="relative bg-gray-900 rounded-2xl border border-gray-700 p-6 w-full max-w-sm">
        <h3 class="text-lg font-bold text-white mb-2">Publish Quiz</h3>
        <p class="text-sm text-gray-400 mb-5">Who can discover "{{ publishTarget?.title }}"?</p>

        <div class="space-y-3 mb-6">
          <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
            :class="publishIsListed ? 'border-brand-500 bg-brand-900/20' : 'border-gray-700 bg-gray-800/50 hover:border-gray-600'">
            <input type="radio" v-model="publishIsListed" :value="true" class="mt-0.5 accent-brand-500" />
            <div>
              <p class="font-medium text-gray-200 text-sm">Listed</p>
              <p class="text-xs text-gray-400">Appears on Explore for everyone to find.</p>
            </div>
          </label>
          <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer transition-colors"
            :class="!publishIsListed ? 'border-brand-500 bg-brand-900/20' : 'border-gray-700 bg-gray-800/50 hover:border-gray-600'">
            <input type="radio" v-model="publishIsListed" :value="false" class="mt-0.5 accent-brand-500" />
            <div>
              <p class="font-medium text-gray-200 text-sm">Unlisted</p>
              <p class="text-xs text-gray-400">Only accessible via direct link.</p>
            </div>
          </label>
        </div>

        <div class="flex gap-3">
          <button @click="showPublishModal = false" class="flex-1 py-2.5 bg-gray-800 hover:bg-gray-700 text-gray-300 font-medium rounded-xl transition-colors">
            Cancel
          </button>
          <button @click="confirmPublish" class="flex-1 py-2.5 bg-green-700 hover:bg-green-600 text-white font-semibold rounded-xl transition-colors">
            Publish
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
