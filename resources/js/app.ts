import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import AppLayout from './Layouts/AppLayout.vue'

const appName = (window as any).__APP_NAME__ || 'QuizVault'
type PageWithLayout = DefineComponent & { layout?: typeof AppLayout }
const pages = import.meta.glob<{ default: PageWithLayout }>('./Pages/**/*.vue')

createInertiaApp({
  title: (title) => title ? `${title} · ${appName}` : appName,
  resolve: (name) => resolvePageComponent(
      `./Pages/${name}.vue`,
      pages
    ).then(({ default: component }) => {
      component.layout ??= AppLayout

      return component
    }),
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
  progress: {
    color: '#6366f1',
  },
})
