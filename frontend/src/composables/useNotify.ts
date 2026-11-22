import { reactive } from 'vue'

type NotifyColor = 'success' | 'error' | 'warning' | 'info'

const state = reactive({
  show: false,
  message: '',
  color: 'info' as NotifyColor,
  timeout: 3500,
})

export function useNotify() {
  function notify(message: string, color: NotifyColor = 'info', timeout = 3500) {
    state.message = message
    state.color = color
    state.timeout = timeout
    state.show = true
  }

  return {
    state,
    notify,
    success: (m: string) => notify(m, 'success'),
    error: (m: string) => notify(m, 'error'),
    warning: (m: string) => notify(m, 'warning'),
    info: (m: string) => notify(m, 'info'),
  }
}
