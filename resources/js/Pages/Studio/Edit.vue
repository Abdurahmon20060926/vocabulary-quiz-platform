<script setup lang="ts">
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'

interface Quiz {
  id: number
  ulid: string
  title: string
  description?: string
  mode: string
  seconds_per_question: number
  status: string
}

interface Question {
  id?: number
  position: number
  prompt: string
  options: string[]
  correct_option_index: number
}

const props = defineProps<{
  quiz: Quiz | null
  questions: Question[]
}>()

// Quiz fields
const title = ref(props.quiz?.title || '')
const description = ref(props.quiz?.description || '')
const mode = ref(props.quiz?.mode || 'casual')
const secondsPerQuestion = ref(props.quiz?.seconds_per_question || 10)

const questions = ref<Question[]>(props.questions || [])
const quizUlid = ref<string | null>(props.quiz?.ulid || null)

const saving = ref(false)
const errorMsg = ref<string | null>(null)
const successMsg = ref<string | null>(null)

// Question editor state
const editingQuestion = ref<Question | null>(null)
const editingIndex = ref<number | null>(null)
const isNewQuestion = ref(false)

function newBlankQuestion(): Question {
  return {
    prompt: '',
    options: ['', '', '', ''],
    correct_option_index: 0,
    position: questions.value.length,
  }
}

function openNewQuestion() {
  editingQuestion.value = newBlankQuestion()
  editingIndex.value = null
  isNewQuestion.value = true
}

function editQuestion(q: Question, index: number) {
  editingQuestion.value = { ...q, options: [...q.options] }
  editingIndex.value = index
  isNewQuestion.value = false
}

function cancelEdit() {
  editingQuestion.value = null
  editingIndex.value = null
}

async function saveQuiz() {
  if (!title.value.trim()) {
    errorMsg.value = 'Title is required.'
    return
  }
  saving.value = true
  errorMsg.value = null

  try {
    if (!quizUlid.value) {
      // Create
      const res = await axios.post('/studio/quizzes', {
        title: title.value,
        description: description.value || null,
        mode: mode.value,
        seconds_per_question: secondsPerQuestion.value,
      })
      // redirect happens via Inertia
    } else {
      await axios.put(`/studio/quizzes/${quizUlid.value}`, {
        title: title.value,
        description: description.value || null,
        mode: mode.value,
        seconds_per_question: secondsPerQuestion.value,
      })
      successMsg.value = 'Saved!'
      setTimeout(() => { successMsg.value = null }, 2000)
    }
  } catch (err: any) {
    errorMsg.value = err.response?.data?.message || 'Failed to save.'
  }
  saving.value = false
}

async function saveQuestion() {
  if (!editingQuestion.value) return
  if (!quizUlid.value) {
    // Save quiz first
    await saveQuizIfNew()
    if (!quizUlid.value) return
  }

  const q = editingQuestion.value
  if (!q.prompt.trim()) {
    alert('Question prompt is required.')
    return
  }
  for (const opt of q.options) {
    if (!opt.trim()) {
      alert('All 4 options are required.')
      return
    }
  }

  try {
    if (isNewQuestion.value) {
      const res = await axios.post(`/studio/quizzes/${quizUlid.value}/questions`, {
        prompt: q.prompt,
        options: q.options,
        correct_option_index: q.correct_option_index,
        position: questions.value.length,
      })
      questions.value.push(res.data.question)
    } else if (editingIndex.value !== null) {
      const res = await axios.put(`/studio/quizzes/${quizUlid.value}/questions/${q.id}`, {
        prompt: q.prompt,
        options: q.options,
        correct_option_index: q.correct_option_index,
        position: q.position,
      })
      questions.value[editingIndex.value] = res.data.question
    }
    cancelEdit()
  } catch (err: any) {
    alert(err.response?.data?.message || 'Failed to save question.')
  }
}

async function deleteQuestion(q: Question, index: number) {
  if (!quizUlid.value || !q.id) return
  if (!confirm('Delete this question?')) return

  try {
    await axios.delete(`/studio/quizzes/${quizUlid.value}/questions/${q.id}`)
    questions.value.splice(index, 1)
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to delete question.')
  }
}

async function saveQuizIfNew() {
  if (quizUlid.value) return
  saving.value = true
  try {
    const res = await axios.post('/studio/quizzes', {
      title: title.value || 'Untitled Quiz',
      description: description.value || null,
      mode: mode.value,
      seconds_per_question: secondsPerQuestion.value,
    })
    // The route redirects to edit page - we need to handle this differently
    // Actually let's use Inertia post
    router.post('/studio/quizzes', {
      title: title.value || 'Untitled Quiz',
      description: description.value || null,
      mode: mode.value,
      seconds_per_question: secondsPerQuestion.value,
    })
  } catch {}
  saving.value = false
}

const isReadOnly = computed(() => props.quiz?.status && props.quiz.status !== 'draft')
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
        <h1 class="text-2xl font-bold text-white">{{ quiz ? 'Edit Quiz' : 'New Quiz' }}</h1>
        <p v-if="isReadOnly" class="text-xs text-yellow-400 mt-0.5">Published quizzes are immutable. Duplicate to edit.</p>
      </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
      <!-- Quiz settings -->
      <div class="lg:col-span-2">
        <div class="bg-gray-900 rounded-xl border border-gray-800 p-5 sticky top-24">
          <h2 class="font-semibold text-gray-200 mb-4 text-sm uppercase tracking-wider">Quiz Settings</h2>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-400 mb-1.5">Title <span class="text-red-400">*</span></label>
              <input
                v-model="title"
                :disabled="isReadOnly"
                type="text"
                maxlength="80"
                placeholder="Enter quiz title..."
                class="w-full px-3 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 disabled:opacity-60 transition-colors"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-400 mb-1.5">Description</label>
              <textarea
                v-model="description"
                :disabled="isReadOnly"
                maxlength="500"
                rows="3"
                placeholder="Describe your quiz..."
                class="w-full px-3 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 disabled:opacity-60 resize-none transition-colors"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-400 mb-1.5">Mode</label>
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-for="m in ['casual', 'strict']"
                  :key="m"
                  @click="!isReadOnly && (mode = m)"
                  class="py-2 px-3 rounded-lg text-sm font-medium border transition-colors capitalize"
                  :class="[
                    mode === m ? 'border-brand-500 bg-brand-900/30 text-brand-300' : 'border-gray-700 bg-gray-800 text-gray-400 hover:border-gray-600',
                    isReadOnly ? 'opacity-60 cursor-default' : 'cursor-pointer',
                  ]"
                >
                  {{ m }}
                </button>
              </div>
              <p class="text-xs text-gray-500 mt-1.5">
                {{ mode === 'strict' ? 'Server-authoritative: server controls timing and answer verification.' : 'Client-checked: fast and fluid, results labeled self-reported.' }}
              </p>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-400 mb-1.5">Seconds per question: <span class="text-gray-200 font-semibold">{{ secondsPerQuestion }}s</span></label>
              <input
                v-model.number="secondsPerQuestion"
                :disabled="isReadOnly"
                type="range"
                min="2"
                max="10"
                class="w-full accent-brand-500 disabled:opacity-60"
              />
              <div class="flex justify-between text-xs text-gray-500 mt-0.5">
                <span>2s</span>
                <span>10s</span>
              </div>
            </div>
          </div>

          <div v-if="errorMsg" class="mt-4 p-3 bg-red-900/30 border border-red-700/50 rounded-lg text-xs text-red-300">
            {{ errorMsg }}
          </div>
          <div v-if="successMsg" class="mt-4 p-3 bg-green-900/30 border border-green-700/50 rounded-lg text-xs text-green-300">
            {{ successMsg }}
          </div>

          <div v-if="!isReadOnly" class="mt-5 flex gap-2">
            <button
              @click="saveQuiz"
              :disabled="saving"
              class="flex-1 py-2.5 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-colors disabled:opacity-60 text-sm"
            >
              {{ saving ? 'Saving...' : (quiz ? 'Save' : 'Create Quiz') }}
            </button>
          </div>

          <div v-if="isReadOnly" class="mt-5">
            <p class="text-xs text-gray-500 text-center mb-3">Published quizzes cannot be edited.</p>
            <Link :href="`/studio/quizzes/${quiz?.ulid}/stats`" class="block text-center text-sm text-brand-400 hover:text-brand-300 transition-colors">
              View analytics →
            </Link>
          </div>
        </div>
      </div>

      <!-- Questions -->
      <div class="lg:col-span-3">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-200">Questions <span class="text-gray-500 font-normal">({{ questions.length }}/15)</span></h2>
          <button
            v-if="!isReadOnly"
            @click="openNewQuestion"
            :disabled="questions.length >= 15"
            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium bg-brand-600/20 hover:bg-brand-600/40 text-brand-300 rounded-lg border border-brand-700/50 transition-colors disabled:opacity-50"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Add question
          </button>
        </div>

        <!-- Question editor -->
        <div v-if="editingQuestion" class="bg-gray-900 rounded-xl border border-brand-700/50 p-5 mb-4">
          <h3 class="font-medium text-gray-200 mb-4 text-sm">{{ isNewQuestion ? 'New Question' : 'Edit Question' }}</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-400 mb-1.5">Prompt <span class="text-red-400">*</span></label>
              <textarea
                v-model="editingQuestion.prompt"
                rows="2"
                maxlength="200"
                placeholder="Enter question..."
                class="w-full px-3 py-2.5 rounded-lg bg-gray-800 border border-gray-700 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:border-brand-500 resize-none"
              />
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-400 mb-2">Options (pick the correct one)</label>
              <div class="space-y-2">
                <div v-for="(opt, i) in editingQuestion.options" :key="i" class="flex items-center gap-2">
                  <button
                    @click="editingQuestion!.correct_option_index = i"
                    class="w-6 h-6 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors"
                    :class="editingQuestion.correct_option_index === i ? 'border-green-500 bg-green-500' : 'border-gray-600 hover:border-gray-400'"
                  >
                    <svg v-if="editingQuestion.correct_option_index === i" class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                    </svg>
                  </button>
                  <input
                    v-model="editingQuestion.options[i]"
                    type="text"
                    maxlength="80"
                    :placeholder="`Option ${i + 1}`"
                    class="flex-1 px-3 py-2 rounded-lg bg-gray-800 border text-sm text-gray-100 placeholder-gray-500 focus:outline-none transition-colors"
                    :class="editingQuestion.correct_option_index === i ? 'border-green-600/50 bg-green-900/10' : 'border-gray-700 focus:border-brand-500'"
                  />
                </div>
              </div>
            </div>
          </div>

          <div class="flex gap-2 mt-4">
            <button @click="saveQuestion" class="px-4 py-2 bg-brand-600 hover:bg-brand-500 text-white text-sm font-medium rounded-lg transition-colors">Save</button>
            <button @click="cancelEdit" class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 text-sm font-medium rounded-lg transition-colors">Cancel</button>
          </div>
        </div>

        <!-- Questions list -->
        <div class="space-y-2">
          <div
            v-for="(q, index) in questions"
            :key="q.id ?? index"
            class="bg-gray-900 rounded-xl border border-gray-800 p-4"
          >
            <div class="flex items-start gap-3">
              <span class="text-xs font-mono text-gray-500 mt-1 w-6 shrink-0 text-right">{{ index + 1 }}</span>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-200 mb-2 font-medium">{{ q.prompt }}</p>
                <div class="grid grid-cols-2 gap-1.5">
                  <div
                    v-for="(opt, oi) in q.options"
                    :key="oi"
                    class="text-xs px-2.5 py-1.5 rounded-lg"
                    :class="oi === q.correct_option_index ? 'bg-green-900/30 text-green-300 border border-green-700/50' : 'bg-gray-800 text-gray-400'"
                  >
                    {{ opt }}
                    <span v-if="oi === q.correct_option_index" class="text-green-500 ml-1">✓</span>
                  </div>
                </div>
              </div>
              <div v-if="!isReadOnly" class="flex items-center gap-1.5 shrink-0">
                <button @click="editQuestion(q, index)" class="p-1.5 text-gray-500 hover:text-gray-200 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                </button>
                <button @click="deleteQuestion(q, index)" class="p-1.5 text-gray-500 hover:text-red-400 transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div v-if="questions.length === 0 && !editingQuestion" class="flex flex-col items-center justify-center py-12 bg-gray-900 rounded-xl border border-dashed border-gray-700 text-center">
            <p class="text-gray-500 text-sm mb-3">No questions yet.</p>
            <button v-if="!isReadOnly" @click="openNewQuestion" class="text-sm text-brand-400 hover:text-brand-300 transition-colors">
              Add your first question
            </button>
          </div>
        </div>

        <div v-if="questions.length > 0" class="mt-4 p-3 rounded-lg text-xs text-center"
          :class="questions.length >= 5 ? 'bg-green-900/20 text-green-400 border border-green-700/30' : 'bg-yellow-900/20 text-yellow-400 border border-yellow-700/30'">
          {{ questions.length >= 5 ? `✓ ${questions.length} questions — ready to publish (5–15 required)` : `${questions.length}/5 questions minimum to publish` }}
        </div>
      </div>
    </div>
  </div>
</template>
