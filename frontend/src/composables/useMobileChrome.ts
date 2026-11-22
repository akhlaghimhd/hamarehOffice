import { computed, ref } from 'vue'

/** تعداد دراور/شیت باز — برای مخفی کردن نوار پایین موبایل */
const openOverlays = ref(0)

export function useMobileChrome() {
  function lock() {
    openOverlays.value += 1
  }

  function unlock() {
    openOverlays.value = Math.max(0, openOverlays.value - 1)
  }

  function reset() {
    openOverlays.value = 0
  }

  const bottomNavVisible = computed(() => openOverlays.value === 0)

  return {
    openOverlays,
    bottomNavVisible,
    lock,
    unlock,
    reset,
  }
}
