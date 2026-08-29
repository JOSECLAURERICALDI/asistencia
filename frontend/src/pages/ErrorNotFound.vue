<template>
  <q-page class="flex flex-center">
    <div class="text-center">
      <div class="text-h1">404</div>
      <div class="text-h5 q-mb-md">Página no encontrada</div>
      <q-btn
        color="primary"
        label="Ir al inicio"
        to="/"
      />
    </div>
  </q-page>
</template>

<script>
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'ErrorNotFound'
})
</script>
