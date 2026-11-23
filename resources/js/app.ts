import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import AppLayout from './Layouts/AppLayout.vue'

const appName = (window as any).__APP_NAME__ || 'QuizVault'

createInertiaApp({
  title: (title) => title ? `${title} · ${appName}` : appName,
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue')
    )
    page.then((module: any) => {
      if (!module.default.layout) {
        module.default.layout = AppLayout
      }
    })
    return page
  },
  setup({ el, App, props, plugin }) {
    createApp({ render: () => h(App, props) })
      .use(plugin)
      .mount(el)
  },
  progress: {
    color: '#6366f1',
  },
})
