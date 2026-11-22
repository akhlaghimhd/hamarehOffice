<script setup lang="ts">
import { useTheme } from 'vuetify'
import { useRouter } from 'vue-router'
import { loginRequest, setAuth } from '@/utils/api'

import logo from '@images/logo.svg?raw'
import authV1MaskDark from '@images/pages/auth-v1-mask-dark.png'
import authV1MaskLight from '@images/pages/auth-v1-mask-light.png'
import authV1Tree2 from '@images/pages/auth-v1-tree-2.png'
import authV1Tree from '@images/pages/auth-v1-tree.png'

const router = useRouter()

const form = ref({
  email: 'admin@hamareh.local',
  password: 'password',
  remember: true,
})

const isPasswordVisible = ref(false)
const loading = ref(false)
const errorMessage = ref('')

const vuetifyTheme = useTheme()
const authThemeMask = computed(() =>
  vuetifyTheme.global.name.value === 'light' ? authV1MaskLight : authV1MaskDark,
)

async function onSubmit() {
  errorMessage.value = ''
  loading.value = true
  try {
    const data = await loginRequest(form.value.email, form.value.password)
    setAuth(data.token, data.user)
    await router.push('/dashboard')
  }
  catch (e: any) {
    errorMessage.value = e?.message || 'ورود ناموفق بود'
  }
  finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <VCard class="auth-card pa-4 pt-7" max-width="448">
      <VCardItem class="justify-center">
        <div class="d-flex flex-column align-center gap-2">
          <div class="d-flex" v-html="logo" />
          <h2 class="font-weight-medium text-2xl mb-0">هماره</h2>
          <span class="text-body-2 text-medium-emphasis">هوشمند آرمان هماره</span>
        </div>
      </VCardItem>

      <VCardText class="pt-4">
        <h4 class="text-h4 mb-1">ورود به سیستم</h4>
        <p class="mb-0">ایمیل و رمز عبور را وارد کنید</p>
      </VCardText>

      <VCardText>
        <VAlert v-if="errorMessage" type="error" variant="tonal" class="mb-4">
          {{ errorMessage }}
        </VAlert>

        <VForm @submit.prevent="onSubmit">
          <VRow>
            <VCol cols="12">
              <VTextField v-model="form.email" label="ایمیل" type="email" autocomplete="username" />
            </VCol>
            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="رمز عبور"
                :type="isPasswordVisible ? 'text' : 'password'"
                autocomplete="current-password"
                :append-inner-icon="isPasswordVisible ? 'ri-eye-off-line' : 'ri-eye-line'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
              />
              <div class="d-flex align-center justify-space-between flex-wrap my-6">
                <VCheckbox v-model="form.remember" label="مرا به خاطر بسپار" />
              </div>
              <VBtn block type="submit" :loading="loading">ورود</VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>

    <VImg class="auth-footer-start-tree d-none d-md-block" :src="authV1Tree" :width="250" />
    <VImg :src="authV1Tree2" class="auth-footer-end-tree d-none d-md-block" :width="350" />
    <VImg class="auth-footer-mask d-none d-md-block" :src="authThemeMask" />
  </div>
</template>

<style lang="scss">
@use "@core/scss/template/pages/page-auth";
</style>
