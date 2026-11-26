<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import axios from 'axios'

interface QuizData {
  id: number
  ulid: string
  title: string | null
  description?: string | null
  mode: 'strict' | 'casual' | null
  seconds_per_question: number | null
  status: string
  is_listed: boolean
  upvotes_count: number
  runs_count: number
  questions_count: number
  pass_rate: number | null
  avg_reached: number | null
  user: { id: number; name: string; avatar_url?: string }
}

interface UserStats {
  attempts: number
  best_reached: number
  passed: boolean
  first_passed_at: string | null
}

interface Suggestion {
  ulid: string
  title: string
  mode: string
  seconds_per_question: number
  upvotes_count: number
  runs_count: number
  questions_count: number
  user: { name: string; avatar_url?: string }
}

interface PageProps extends Record<string, unknown> {
  auth: {
    user: { id: number; name: string; avatar_url: string | null } | null
  }
}

const props = defineProps<{
  quiz: QuizData
  userStats: UserStats | null
  hasVoted: boolean
  isCreator: boolean
  suggestions: Suggestion[]
}>()

const page = usePage<PageProps>()
const user = computed(() => page.props.auth.user)

// Game state
type GameState = 'lobby' | 'countdown' | 'playing' | 'checking' | 'result'
const gameState = ref<GameState>('lobby')
const countdownValue = ref(3)
const runId = ref<string | null>(null)

// Current displayed question (strict) or casual question object
const currentQuestion = ref<any>(null)

// Casual mode state
const casualQuestions = ref<any[]>([])
const casualQuestionIndex = ref(0)
const casualAnswerLog = ref<any[]>([])
const casualSalt = ref<string>('')

// Results
const result = ref<{ verdict: string; failure_reason?: string; message?: string; reached: number; total: number } | null>(null)
const selectedDisplayIndex = ref<number | null>(null)
const failedDisplayIndex = ref<number | null>(null)
const errorMsg = ref<string | null>(null)

// Timer
const timeLeft = ref(0)
const timerInterval = ref<ReturnType<typeof setInterval> | null>(null)
const timerStartedAt = ref(0)
const isTimerRunning = ref(false)
const timerPaused = ref(false)

// Voting
const voted = ref(props.hasVoted)
const upvotesCount = ref(props.quiz.upvotes_count)
const voteLoading = ref(false)

// Sound
const isMuted = ref(false)
try { isMuted.value = localStorage.getItem('quizvault_muted') === 'true' } catch {}

function toggleMute() {
  isMuted.value = !isMuted.value
  try { localStorage.setItem('quizvault_muted', String(isMuted.value)) } catch {}
  if (isMuted.value) stopAllSounds()
}

// Sound manager — wired to placeholder files
let gameLoopAudio: HTMLAudioElement | null = null
let failAudio: HTMLAudioElement | null = null
let winAudio: HTMLAudioElement | null = null

function initSounds() {
  try {
    gameLoopAudio = new Audio('/sounds/game-loop.wav')
    gameLoopAudio.loop = true
    gameLoopAudio.volume = 0.3
    failAudio = new Audio('/sounds/fail.wav')
    failAudio.volume = 0.5
    winAudio = new Audio('/sounds/win.wav')
    winAudio.volume = 0.5
  } catch {}
}

function playSound(type: 'game-loop' | 'fail' | 'win') {
  if (isMuted.value) return
  try {
    if (type === 'game-loop' && gameLoopAudio) {
      gameLoopAudio.currentTime = 0
      gameLoopAudio.play().catch(() => {})
    } else if (type === 'fail' && failAudio) {
      failAudio.currentTime = 0
      failAudio.play().catch(() => {})
    } else if (type === 'win' && winAudio) {
      winAudio.currentTime = 0
      winAudio.play().catch(() => {})
    }
  } catch {}
}

function stopAllSounds() {
  try { gameLoopAudio?.pause(); if (gameLoopAudio) gameLoopAudio.currentTime = 0 } catch {}
}

// Attempts tracking (local optimistic)
const localAttempts = ref(props.userStats?.attempts ?? 0)
const localBestReached = ref(props.userStats?.best_reached ?? 0)

// Confetti
let confettiModule: any = null

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('visibilitychange', handleVisibilityChange)
  import('canvas-confetti').then(m => { confettiModule = m.default }).catch(() => {})
  initSounds()
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('visibilitychange', handleVisibilityChange)
  stopTimer()
  stopAllSounds()
})

function handleKeydown(e: KeyboardEvent) {
  // Prevent keyboard shortcuts when typing in inputs
  if ((e.target as HTMLElement)?.tagName === 'INPUT') return

  if (gameState.value === 'lobby' || gameState.value === 'result') {
    if (e.key === 'Enter') startGame()
    return
  }

  if (gameState.value === 'playing' && currentQuestion.value) {
    const keyMap: Record<string, number> = { 'w': 0, 'a': 1, 's': 2, 'd': 3 }
    const idx = keyMap[e.key.toLowerCase()]
    if (idx !== undefined) submitAnswer(idx)
  }
}

function handleVisibilityChange() {
  // Only fail if timer is actively running during a question (not during checking, countdown, or result)
  if (document.hidden && gameState.value === 'playing' && isTimerRunning.value && !timerPaused.value) {
    submitFailure('focus_lost')
  }
}

async function startGame() {
  if (!props.quiz.mode || props.quiz.seconds_per_question === null) {
    errorMsg.value = 'Set the quiz mode and timer before starting a test play.'
    return
  }

  gameState.value = 'countdown'
  countdownValue.value = 3
  errorMsg.value = null
  result.value = null
  selectedDisplayIndex.value = null
  failedDisplayIndex.value = null

  const timer = setInterval(() => {
    countdownValue.value--
    if (countdownValue.value <= 0) {
      clearInterval(timer)
      initiateRun()
    }
  }, 1000)
}

async function initiateRun() {
  try {
    const res = await axios.post(`/q/${props.quiz.ulid}/runs`)
    runId.value = res.data.run_id

    // Set/refresh guest token from header if present
    const guestToken = res.headers['x-guest-token']
    if (guestToken) {
      document.cookie = `guest_token=${guestToken}; max-age=${60*60*24*365}; path=/; SameSite=Lax`
    }

    if (props.quiz.mode === 'strict') {
      currentQuestion.value = res.data.question
      gameState.value = 'playing'
      startTimer()
      playSound('game-loop')
    } else {
      // Casual: store salt, precompute hashes, shuffle
      casualSalt.value = res.data.salt
      const rawQuestions = res.data.questions as any[]

      // Precompute hashes for instant answer-checking
      const withHashes = await Promise.all(rawQuestions.map(async (q) => ({
        ...q,
        _hash: q.answer_hash,
        displayOptions: shuffleOptions(q.options),
      })))

      casualQuestions.value = shuffleArray(withHashes)
      casualAnswerLog.value = []
      casualQuestionIndex.value = 0
      currentQuestion.value = buildCasualQuestion()
      gameState.value = 'playing'
      startTimer()
      playSound('game-loop')
    }
  } catch (err: any) {
    gameState.value = 'lobby'
    errorMsg.value = err.response?.data?.message || err.response?.data?.error || 'Failed to start. Please try again.'
  }
}

function shuffleArray<T>(arr: T[]): T[] {
  const a = [...arr]
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]]
  }
  return a
}

function shuffleOptions(options: string[]): { text: string; originalIndex: number }[] {
  const indexed = options.map((text, originalIndex) => ({ text, originalIndex }))
  return shuffleArray(indexed)
}

function buildCasualQuestion() {
  const q = casualQuestions.value[casualQuestionIndex.value]
  return {
    id: q.id,
    prompt: q.prompt,
    options: q.displayOptions.map((o: any) => o.text),
    index: casualQuestionIndex.value,
    total: casualQuestions.value.length,
    _displayOptions: q.displayOptions,
    _hash: q._hash,
  }
}

/** Compute sha256(salt:questionId:optionText) using SubtleCrypto */
async function computeHash(salt: string, questionId: number, optionText: string): Promise<string> {
  const msg = `${salt}:${questionId}:${optionText}`
  const enc = new TextEncoder()
  const buf = await crypto.subtle.digest('SHA-256', enc.encode(msg))
  return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2, '0')).join('')
}

function startTimer() {
  stopTimer()
  const secondsPerQuestion = props.quiz.seconds_per_question
  if (secondsPerQuestion === null) return

  timeLeft.value = secondsPerQuestion
  timerStartedAt.value = Date.now()
  timerPaused.value = false
  isTimerRunning.value = true

  timerInterval.value = setInterval(() => {
    if (timerPaused.value) return
    const elapsed = (Date.now() - timerStartedAt.value) / 1000
    const remaining = secondsPerQuestion - elapsed
    timeLeft.value = Math.max(0, remaining)

    if (timeLeft.value <= 0) {
      stopTimer()
      // For strict: send timeout to server. For casual: handle locally.
      submitFailure('timeout')
    }
  }, 50)
}

function pauseTimer() {
  timerPaused.value = true
}

function stopTimer() {
  isTimerRunning.value = false
  timerPaused.value = false
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
}

function getElapsedMs() {
  return Date.now() - timerStartedAt.value
}

async function submitAnswer(displayIndex: number) {
  if (gameState.value !== 'playing') return
  if (selectedDisplayIndex.value !== null) return

  selectedDisplayIndex.value = displayIndex
  const elapsedMs = getElapsedMs()

  if (props.quiz.mode === 'strict') {
    // Pause timer during network check (PRD §6.2: timer pauses during network round trip)
    pauseTimer()
    gameState.value = 'checking'

    try {
      const res = await axios.post(`/runs/${runId.value}/answers`, {
        displayed_index: displayIndex,
      })

      const data = res.data
      selectedDisplayIndex.value = null

      if (data.verdict === 'correct' && data.next_question) {
        currentQuestion.value = data.next_question
        gameState.value = 'playing'
        startTimer() // fresh timer for next question
      } else {
        handleRunEnd(data)
      }
    } catch (error) {
      showSubmissionError(error)
    }
  } else {
    // Casual: check hash locally via SubtleCrypto — instant, no checking state
    const q = currentQuestion.value
    const displayedOptions = q._displayOptions
    const chosenOriginalIndex = displayedOptions[displayIndex]?.originalIndex
    const chosenText = displayedOptions[displayIndex]?.text ?? ''

    pauseTimer()

    let computedHash: string
    try {
      computedHash = await computeHash(casualSalt.value, q.id, chosenText)
    } catch {
      selectedDisplayIndex.value = null
      showResultError('The answer could not be verified. Start a new run to continue.')
      return
    }

    const isCorrect = computedHash === q._hash

    stopTimer()

    casualAnswerLog.value.push({
      question_id: q.id,
      chosen_option_index: chosenOriginalIndex,
      is_correct: isCorrect,
      time_ms: elapsedMs,
    })

    if (!isCorrect) {
      failedDisplayIndex.value = displayIndex
      // Brief visual feedback, then submit
      await new Promise(r => setTimeout(r, 600))
      await submitCasualRun()
    } else {
      selectedDisplayIndex.value = null
      casualQuestionIndex.value++
      if (casualQuestionIndex.value >= casualQuestions.value.length) {
        await submitCasualRun()
      } else {
        currentQuestion.value = buildCasualQuestion()
        startTimer()
      }
    }
  }
}

async function submitFailure(reason: string) {
  stopTimer()
  const elapsedMs = getElapsedMs()

  if (props.quiz.mode === 'strict') {
    gameState.value = 'checking'
    try {
      const res = await axios.post(`/runs/${runId.value}/answers`, { reason })
      handleRunEnd(res.data)
    } catch (error) {
      showSubmissionError(error)
    }
  } else {
    casualAnswerLog.value.push({
      question_id: currentQuestion.value?.id ?? null,
      chosen_option_index: null,
      reason,
      time_ms: elapsedMs,
    })
    await submitCasualRun()
  }
}

function completeCasualAnswerLog() {
  const loggedQuestionIds = new Set(casualAnswerLog.value.map(answer => answer.question_id))

  for (const question of casualQuestions.value) {
    if (!loggedQuestionIds.has(question.id)) {
      casualAnswerLog.value.push({
        question_id: question.id,
        chosen_option_index: null,
        reason: null,
        time_ms: 0,
      })
    }
  }
}

async function submitCasualRun() {
  gameState.value = 'checking'
  completeCasualAnswerLog()

  try {
    const res = await axios.post(`/runs/${runId.value}/submit`, {
      answers: casualAnswerLog.value.map(({ is_correct, ...answer }) => answer),
    })
    handleRunEnd(res.data)
  } catch (error) {
    showSubmissionError(error)
  }
}

function showResultError(message: string) {
  stopTimer()
  gameState.value = 'result'
  result.value = {
    verdict: 'error',
    message,
    reached: currentQuestion.value?.index ?? 0,
    total: props.quiz.questions_count,
  }
}

function showSubmissionError(error: unknown) {
  let message = 'We could not confirm whether this run was saved. Return to the quiz to refresh your results.'

  if (axios.isAxiosError(error)) {
    const responseMessage = error.response?.data?.error ?? error.response?.data?.message
    if (typeof responseMessage === 'string') {
      message = responseMessage
    }
  }

  showResultError(message)
}

function handleRunEnd(data: any) {
  if (data.verdict === 'abandoned') {
    showResultError('This quiz is no longer available, so this run was not scored.')
    return
  }

  result.value = data
  localAttempts.value++
  if (data.reached > localBestReached.value) {
    localBestReached.value = data.reached
  }

  if (data.verdict === 'passed' && confettiModule) {
    playSound('win')
    stopAllSounds()
    confettiModule({
      particleCount: 160,
      spread: 80,
      origin: { y: 0.6 },
      colors: ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b'],
    })
  }

  gameState.value = 'result'
  // Keep selectedDisplayIndex for red highlight on failure, but not for pass
  if (data.verdict === 'passed') {
    selectedDisplayIndex.value = null
    failedDisplayIndex.value = null
  } else {
    stopAllSounds()
    playSound('fail')
  }
}

async function toggleVote() {
  if (!user.value) {
    window.location.href = '/auth/google/redirect'
    return
  }
  if (props.isCreator) return
  if (voteLoading.value) return

  voteLoading.value = true
  try {
    const res = await axios.post(`/q/${props.quiz.ulid}/vote`)
    voted.value = res.data.voted
    upvotesCount.value = res.data.upvotes_count
  } catch {}
  voteLoading.value = false
}

const timerPercent = computed(() => {
  const secondsPerQuestion = props.quiz.seconds_per_question
  return secondsPerQuestion
    ? Math.min(100, (timeLeft.value / secondsPerQuestion) * 100)
    : 0
})

const timerColor = computed(() => {
  if (timerPercent.value > 50) return '#10b981'
  if (timerPercent.value > 25) return '#f59e0b'
  return '#ef4444'
})
</script>

<template>
  <div class="max-w-3xl mx-auto">

    <!-- ═══ LOBBY ═══ -->
    <div v-if="gameState === 'lobby'">
      <div class="bg-gray-900 rounded-2xl border border-gray-800 p-6 mb-6">
        <div class="flex items-start gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-3 mb-3 flex-wrap">
              <span
                class="inline-flex items-center px-3 py-1 rounded-lg text-sm font-semibold uppercase tracking-wide"
                :class="quiz.mode === 'strict'
                  ? 'bg-orange-900/50 text-orange-300 border border-orange-700/50'
                  : quiz.mode === 'casual'
                    ? 'bg-blue-900/50 text-blue-300 border border-blue-700/50'
                    : 'bg-gray-800 text-gray-400 border border-gray-700'"
              >
                {{ quiz.mode ?? 'Mode not set' }}
              </span>
              <span class="text-sm text-gray-400">{{ quiz.questions_count }} questions · {{ quiz.seconds_per_question ? `${quiz.seconds_per_question}s each` : 'Timer not set' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white mb-3 leading-tight">{{ quiz.title || 'Untitled draft' }}</h1>
            <p v-if="quiz.description" class="text-gray-400 text-sm leading-relaxed mb-4">{{ quiz.description }}</p>
            <div class="flex items-center gap-2">
              <img v-if="quiz.user?.avatar_url" :src="quiz.user.avatar_url" :alt="quiz.user.name" class="w-6 h-6 rounded-full" />
              <div v-else class="w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center text-xs text-white font-bold">
                {{ quiz.user?.name?.[0] }}
              </div>
              <span class="text-sm text-gray-400">by <span class="text-gray-200">{{ quiz.user?.name }}</span></span>
            </div>
          </div>
        </div>

        <!-- Stats row -->
        <div class="flex items-center gap-4 mt-5 pt-4 border-t border-gray-800 text-sm flex-wrap">
          <div class="flex items-center gap-1.5 text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ quiz.runs_count }} plays</span>
          </div>

          <div v-if="quiz.pass_rate !== null" class="flex items-center gap-1.5 text-gray-400">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>{{ quiz.pass_rate }}% pass rate</span>
          </div>
          <div v-else-if="quiz.runs_count > 0" class="text-gray-500 text-xs italic">Not enough plays yet</div>

          <div v-if="quiz.avg_reached !== null" class="text-gray-400 text-xs">
            Avg reached {{ quiz.avg_reached }} / {{ quiz.questions_count }}
          </div>

          <!-- Vote button -->
          <button
            v-if="!isCreator"
            @click="toggleVote"
            class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm transition-colors border"
            :class="voted
              ? 'bg-brand-700 text-brand-200 border-brand-600'
              : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700 border-gray-700'"
            :disabled="voteLoading"
            :title="user ? (voted ? 'Remove vote' : 'Upvote') : 'Sign in to vote'"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
            {{ upvotesCount }}
          </button>
          <div v-else class="ml-auto text-xs text-gray-500">{{ upvotesCount }} votes</div>
        </div>

        <!-- Personal stats -->
        <div v-if="userStats" class="mt-4 p-3 bg-gray-800/50 rounded-xl border border-gray-700">
          <div class="flex items-center gap-6 text-sm flex-wrap">
            <div><span class="text-gray-400">Attempts: </span><span class="text-gray-200 font-semibold">{{ userStats.attempts }}</span></div>
            <div><span class="text-gray-400">Best: </span><span class="text-gray-200 font-semibold">{{ userStats.best_reached }}/{{ quiz.questions_count }}</span></div>
            <div v-if="userStats.passed" class="flex items-center gap-1 text-green-400 font-medium">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              Passed!
            </div>
          </div>
        </div>

        <!-- Error msg -->
        <div v-if="errorMsg" class="mt-4 p-3 bg-red-900/30 border border-red-700/50 rounded-lg text-sm text-red-300">
          {{ errorMsg }}
        </div>

        <!-- Start button -->
        <button
          @click="startGame"
          class="mt-5 w-full py-4 px-6 bg-brand-600 hover:bg-brand-500 text-white font-bold text-lg rounded-xl transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
          Start Quiz
          <span class="hidden sm:inline text-brand-200 text-sm font-normal ml-2">(Enter)</span>
        </button>
      </div>

      <!-- Suggestions -->
      <div v-if="suggestions.length" class="mt-8">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">More Quizzes</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Link
            v-for="s in suggestions"
            :key="s.ulid"
            :href="`/q/${s.ulid}`"
            class="flex items-center gap-3 p-4 bg-gray-900 rounded-xl border border-gray-800 hover:border-gray-600 transition-colors group"
          >
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-200 group-hover:text-white truncate">{{ s.title }}</p>
              <p class="text-xs text-gray-500 mt-0.5">{{ s.questions_count }} Qs · {{ s.seconds_per_question }}s · by {{ s.user?.name }}</p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-md shrink-0"
              :class="s.mode === 'strict' ? 'bg-orange-900/50 text-orange-300' : 'bg-blue-900/50 text-blue-300'">
              {{ s.mode }}
            </span>
          </Link>
        </div>
      </div>
    </div>

    <!-- ═══ COUNTDOWN ═══ -->
    <div v-if="gameState === 'countdown'" class="flex items-center justify-center min-h-[400px]">
      <div class="text-center">
        <div class="text-9xl font-black leading-none transition-all duration-300"
          :class="countdownValue > 0 ? 'text-brand-400 scale-110' : 'text-green-400 scale-125'">
          {{ countdownValue > 0 ? countdownValue : 'Go!' }}
        </div>
        <p class="text-gray-400 mt-6 text-lg">Get ready…</p>
      </div>
    </div>

    <!-- ═══ PLAYING / CHECKING ═══ -->
    <div v-if="gameState === 'playing' || gameState === 'checking'">
      <div v-if="currentQuestion">
        <!-- Progress bar + timer -->
        <div class="mb-5">
          <div class="flex items-center justify-between mb-2 text-sm">
            <span class="text-gray-400">Question {{ (currentQuestion.index ?? 0) + 1 }} of {{ currentQuestion.total }}</span>
            <div class="flex items-center gap-2">
              <!-- Mute toggle -->
              <button @click="toggleMute" class="text-gray-600 hover:text-gray-400 transition-colors" :title="isMuted ? 'Unmute' : 'Mute'">
                <svg v-if="isMuted" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6v12m-3.536-9.536a5 5 0 000 7.072M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/></svg>
              </button>
              <span class="font-mono font-bold text-xl tabular-nums" :style="{ color: timerColor }">
                {{ timerPaused ? '…' : (Math.ceil(timeLeft) + 's') }}
              </span>
            </div>
          </div>

          <!-- Timer bar -->
          <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
            <div
              class="h-full rounded-full transition-colors duration-300"
              :style="{ width: timerPercent + '%', backgroundColor: timerColor, transition: 'width 50ms linear' }"
            />
          </div>

          <!-- Question dots -->
          <div class="flex gap-1 mt-2">
            <div
              v-for="i in currentQuestion.total"
              :key="i"
              class="h-1 flex-1 rounded-full transition-colors"
              :class="i - 1 < (currentQuestion.index ?? 0) ? 'bg-brand-500'
                     : i - 1 === (currentQuestion.index ?? 0) ? 'bg-brand-300'
                     : 'bg-gray-800'"
            />
          </div>
        </div>

        <!-- Question prompt -->
        <div class="bg-gray-900 rounded-2xl border border-gray-800 p-6 mb-5">
          <p class="text-xl sm:text-2xl font-semibold text-white leading-snug text-center">
            {{ currentQuestion.prompt }}
          </p>
        </div>

        <!-- Answer options -->
        <div class="relative">
          <!-- Checking overlay (strict only) -->
          <div v-if="gameState === 'checking'" class="absolute inset-0 flex items-center justify-center bg-gray-950/80 rounded-2xl z-10 backdrop-blur-sm">
            <div class="flex items-center gap-3 text-brand-300">
              <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
              </svg>
              <span class="font-medium text-sm">Checking…</span>
            </div>
          </div>

          <!--
            DESKTOP WASD layout: W on top (centered), A S D in a row below
            Only when device has hover+fine pointer (mouse)
          -->
          <div class="hidden" :class="{ 'wasd-layout': true }">
            <!-- This div is a placeholder; actual WASD handled via CSS media query -->
          </div>

          <!-- WASD layout wrapper - shown only on fine-pointer devices via CSS -->
          <div class="option-grid-wasd">
            <!-- W: index 0, centered on top -->
            <div class="wasd-top">
              <OptionBtn
                :text="currentQuestion.options[0]"
                :displayIndex="0"
                :selected="selectedDisplayIndex ?? undefined"
                :failed="failedDisplayIndex ?? undefined"
                :disabled="gameState === 'checking'"
                hint="W"
                @select="submitAnswer"
              />
            </div>
            <!-- A S D: row below -->
            <div class="wasd-row">
              <OptionBtn
                v-for="i in [1, 2, 3]"
                :key="i"
                :text="currentQuestion.options[i]"
                :displayIndex="i"
                :selected="selectedDisplayIndex ?? undefined"
                :failed="failedDisplayIndex ?? undefined"
                :disabled="gameState === 'checking'"
                :hint="['A','S','D'][i-1]"
                @select="submitAnswer"
              />
            </div>
          </div>

          <!-- Touch / 2×2 grid: shown on touch/coarse-pointer devices -->
          <div class="option-grid-touch">
            <OptionBtn
              v-for="(opt, i) in currentQuestion.options"
              :key="i"
              :text="opt"
              :displayIndex="i"
              :selected="selectedDisplayIndex ?? undefined"
              :failed="failedDisplayIndex ?? undefined"
              :disabled="gameState === 'checking'"
              @select="submitAnswer"
            />
          </div>
        </div>

        <!-- Casual self-reported label -->
        <p v-if="quiz.mode === 'casual'" class="mt-3 text-center text-xs text-gray-600 italic">
          Casual mode — results are self-reported
        </p>
      </div>
    </div>

    <!-- ═══ RESULT ═══ -->
    <div v-if="gameState === 'result' && result" class="flex flex-col items-center justify-center min-h-[420px] text-center px-4">
      <!-- PASS -->
      <div v-if="result.verdict === 'passed'" class="mb-8">
        <div class="text-7xl mb-5">🎉</div>
        <h2 class="text-3xl font-black text-white mb-3">Perfect!</h2>
        <div class="text-6xl font-black text-brand-400 mb-4">{{ result.total }}/{{ result.total }}</div>
        <p class="text-gray-400">
          Attempt #{{ localAttempts }}
          <span v-if="user"> · Best: {{ localBestReached }}/{{ result.total }}</span>
        </p>
      </div>

      <!-- FAIL -->
      <div v-else-if="result.verdict !== 'error'" class="mb-8 w-full max-w-sm">
        <div class="text-6xl mb-4">
          {{ result.failure_reason === 'timeout' ? '⏱️' : result.failure_reason === 'focus_lost' ? '👀' : '❌' }}
        </div>
        <h2 class="text-2xl font-bold text-white mb-2">
          {{ result.failure_reason === 'timeout' ? "Time's up!" : result.failure_reason === 'focus_lost' ? 'Tab switched!' : 'Wrong answer!' }}
        </h2>
        <div class="text-5xl font-black text-gray-200 mb-4">{{ result.reached }}/{{ result.total }}</div>
        <p class="text-gray-400 text-sm mb-1">Reached {{ result.reached }} of {{ result.total }}</p>
        <p v-if="user" class="text-gray-500 text-sm">
          Attempt #{{ localAttempts }} · Best: {{ localBestReached }}/{{ result.total }}
        </p>
        <p class="text-xs text-gray-600 mt-3 italic">The correct answer is never revealed.</p>

        <!-- Show what was picked if wrong answer (casual) -->
        <div v-if="result.failure_reason === 'wrong_answer' && failedDisplayIndex !== null && currentQuestion" class="mt-4 p-3 rounded-xl border border-red-700/50 bg-red-900/20">
          <p class="text-xs text-red-400 mb-1 font-medium">Your answer:</p>
          <p class="text-sm text-red-300">{{ currentQuestion.options[failedDisplayIndex] }}</p>
        </div>
      </div>

      <div v-else class="mb-8 w-full max-w-sm">
        <div class="text-6xl mb-4">⚠️</div>
        <h2 class="text-2xl font-bold text-white mb-2">Run result unavailable</h2>
        <p class="text-gray-400 text-sm">{{ result.message }}</p>
      </div>

      <div class="flex gap-3 flex-wrap justify-center">
        <button
          @click="startGame"
          class="px-7 py-3 bg-brand-600 hover:bg-brand-500 text-white font-semibold rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500"
        >
          {{ result.verdict === 'error' ? 'Start a new run' : 'Retry' }}
          <span class="hidden sm:inline text-brand-200 text-sm font-normal ml-1">(Enter)</span>
        </button>
        <Link
          :href="`/q/${quiz.ulid}`"
          class="px-7 py-3 bg-gray-800 hover:bg-gray-700 text-gray-200 font-semibold rounded-xl transition-colors"
        >
          Back to quiz
        </Link>
      </div>
    </div>

  </div>
</template>

<script lang="ts">
import { defineComponent, h } from 'vue'

// Inline OptionBtn component
const OptionBtn = defineComponent({
  name: 'OptionBtn',
  props: {
    text: String,
    displayIndex: Number,
    selected: { type: Number, default: null },
    failed: { type: Number, default: null },
    disabled: Boolean,
    hint: { type: String, default: null },
  },
  emits: ['select'],
  setup(props, { emit }) {
    return () => {
      const isSelected = props.selected === props.displayIndex
      const isFailed = props.failed === props.displayIndex
      let cls = 'relative w-full text-left p-4 rounded-xl border-2 font-medium text-sm transition-all duration-150 cursor-pointer focus:outline-none focus:ring-2 focus:ring-brand-500/50 '

      if (isFailed) {
        cls += 'border-red-500 bg-red-900/30 text-red-200 ring-2 ring-red-500/40 '
      } else if (isSelected) {
        cls += 'border-brand-500 bg-brand-900/20 text-brand-100 '
      } else if (props.disabled) {
        cls += 'border-gray-700 bg-gray-900 text-gray-400 opacity-60 cursor-default '
      } else {
        cls += 'border-gray-700 bg-gray-900 text-gray-200 hover:border-gray-500 hover:bg-gray-800 active:scale-[0.98] '
      }

      const children = []
      if (props.hint) {
        children.push(h('span', {
          class: 'absolute top-2 left-2.5 text-xs font-bold text-gray-500 font-mono select-none'
        }, props.hint))
      }
      children.push(h('span', {
        class: props.hint ? 'ml-6 block' : 'block'
      }, props.text ?? ''))

      return h('button', {
        class: cls,
        disabled: props.disabled,
        onClick: () => emit('select', props.displayIndex),
      }, children)
    }
  }
})

export default { components: { OptionBtn } }
</script>

<style scoped>
/* WASD layout: show on fine-pointer devices (mouse), hide on touch */
@media (hover: hover) and (pointer: fine) {
  .option-grid-wasd { display: flex; flex-direction: column; gap: 0.75rem; }
  .option-grid-touch { display: none; }
  .wasd-top { display: flex; justify-content: center; }
  .wasd-top > * { width: 60%; }
  .wasd-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
}

/* Touch layout: 2×2 grid on coarse-pointer devices */
@media not all and ((hover: hover) and (pointer: fine)) {
  .option-grid-wasd { display: none; }
  .option-grid-touch { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
}
</style>
