<script setup lang="ts">
const basic = ref(false)
const confirm = ref(false)
const formDlg = ref(false)
const name = ref('')
</script>

<template>
  <div>
    <VBtn
      variant="text"
      color="secondary"
      size="small"
      class="px-0 mb-4"
      prepend-icon="ri-arrow-right-line"
      :to="{ path: '/ui-lab' }"
    >
      الگوهای UI
    </VBtn>

    <VCard title="دیالوگ">
      <VCardText class="d-flex flex-wrap gap-4">
        <VBtn @click="basic = true">ساده</VBtn>
        <VBtn color="error" variant="tonal" @click="confirm = true">تأیید حذف</VBtn>
        <VBtn color="primary" variant="tonal" @click="formDlg = true">با فرم</VBtn>
      </VCardText>
    </VCard>

    <VDialog v-model="basic" max-width="480">
      <VCard title="عنوان دیالوگ">
        <VCardText>
          متن نمونه برای نمایش محتوا داخل دیالوگ Materio.
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" variant="text" @click="basic = false">بستن</VBtn>
          <VBtn color="primary" @click="basic = false">تأیید</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="confirm" max-width="420">
      <VCard>
        <VCardText class="pt-6 text-center">
          <VAvatar color="error" variant="tonal" size="56" class="mb-4">
            <VIcon icon="ri-error-warning-line" size="28" />
          </VAvatar>
          <div class="text-h6 mb-2">حذف شود؟</div>
          <div class="text-body-2 text-medium-emphasis">این عمل قابل بازگشت نیست.</div>
        </VCardText>
        <VCardActions class="justify-center pb-6">
          <VBtn color="secondary" variant="outlined" @click="confirm = false">انصراف</VBtn>
          <VBtn color="error" @click="confirm = false">حذف</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <VDialog v-model="formDlg" max-width="520">
      <VCard title="فرم در دیالوگ">
        <VCardText>
          <VTextField v-model="name" label="نام" class="mb-4" />
          <VTextarea label="توضیح" rows="3" />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn color="secondary" variant="outlined" @click="formDlg = false">انصراف</VBtn>
          <VBtn color="primary" @click="formDlg = false">ذخیره</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </div>
</template>
