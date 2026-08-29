<template>
  <q-layout view="lHh Lpr lFf" class="bg-dark-surface">
    <!-- Header -->
    <q-header elevated class="header-glass">
      <q-toolbar class="q-px-lg" style="height: 64px;">
        <div class="flex items-center gap-3">
          <img src="/logo-unitepc.png" style="height: 38px; background: #ffffff; padding: 4px 8px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.3);" alt="UNITEPC Logo" />
          <div>
            <div class="text-weight-bold text-white" style="font-size: 1rem; line-height: 1.2;">
              Sistema de Asistencia
            </div>
            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); letter-spacing: 1px;">
              UNITEPC · {{ fechaHoy }}
            </div>
          </div>
        </div>

        <q-space />

        <!-- Día actual badge -->
        <q-badge
          :label="diaHoy"
          class="q-mr-md dia-badge"
          style="font-size: 0.75rem; padding: 6px 14px; border-radius: 20px;"
        />

        <!-- User info -->
        <div class="flex items-center gap-2 q-mr-md">
          <q-avatar size="36px" class="user-avatar">
            <q-icon name="person" size="20px" color="white" />
          </q-avatar>
          <div class="gt-xs">
            <div class="text-white text-weight-medium" style="font-size: 0.85rem; line-height: 1.2;">
              {{ user?.apellido }} {{ user?.nombre }}
            </div>
            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5);">CI: {{ user?.ci }}</div>
          </div>
        </div>

        <q-btn flat round icon="logout" color="white" @click="handleLogout" size="sm">
          <q-tooltip>Cerrar sesión</q-tooltip>
        </q-btn>
      </q-toolbar>
    </q-header>

    <!-- Main content -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'stores/auth'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const router = useRouter()
const authStore = useAuthStore()
const user = computed(() => authStore.user)

const diasSemana = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado']
const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']

const ahora = new Date()
const diaHoy = computed(() => diasSemana[ahora.getDay()].charAt(0).toUpperCase() + diasSemana[ahora.getDay()].slice(1))
const fechaHoy = computed(() => `${ahora.getDate()} de ${meses[ahora.getMonth()]} ${ahora.getFullYear()}`)

async function handleLogout() {
  $q.dialog({
    title: 'Cerrar sesión',
    message: '¿Deseas cerrar tu sesión?',
    cancel: true,
    ok: { label: 'Sí, salir', color: 'negative', flat: true },
    cancel: { label: 'Cancelar', flat: true },
    dark: true,
  }).onOk(() => {
    authStore.logout()
    router.push({ name: 'login' })
  })
}
</script>

<style lang="scss" scoped>
.bg-dark-surface {
  background: var(--color-surface);
}

.header-glass {
  background: rgba(15, 23, 42, 0.9) !important;
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.logo-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  background: linear-gradient(135deg, #1e40af, #06b6d4);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
}

.user-avatar {
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.dia-badge {
  background: rgba(59, 130, 246, 0.15) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(59, 130, 246, 0.3) !important;
  text-transform: capitalize !important;
}
</style>
