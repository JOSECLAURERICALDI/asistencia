<template>
  <div class="auth-page">
    <div class="auth-card fade-in-up">
      <!-- Partículas decorativas -->
      <div class="particle p1"></div>
      <div class="particle p2"></div>
      <div class="particle p3"></div>

      <!-- Logo -->
      <div class="text-center q-mb-lg">
        <img src="/logo-unitepc.png" style="max-height: 56px; background: #ffffff; padding: 6px 16px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);" alt="UNITEPC Logo" />
      </div>

      <h1>Sistema de Asistencia</h1>
      <p class="subtitle">Ingresa tu Cédula de Identidad (CI) para continuar</p>

      <!-- Formulario -->
      <q-form @submit="handleLogin" class="q-gutter-md">
        <div>
          <label class="field-label">Número de Cédula de Identidad (CI)</label>
          <q-input
            v-model="form.ci"
            placeholder="Ej: 3456789"
            outlined
            dark
            autofocus
            :rules="[val => !!val || 'El número de CI es requerido']"
            :disable="loading"
            bg-color="surface-dark"
          >
            <template #prepend>
              <q-icon name="badge" color="blue-4" />
            </template>
          </q-input>
        </div>

        <!-- Error message -->
        <q-banner v-if="error" dense rounded class="error-banner q-mt-sm">
          <template #avatar>
            <q-icon name="error_outline" color="red-4" />
          </template>
          {{ error }}
        </q-banner>

        <q-btn
          type="submit"
          :loading="loading"
          class="login-btn full-width q-mt-md"
          size="lg"
          no-caps
        >
          <template #loading>
            <q-spinner-dots size="24px" color="white" />
          </template>
          <q-icon name="login" class="q-mr-sm" size="20px" />
          Ingresar Directo
        </q-btn>
      </q-form>

      <!-- Admin link -->
      <div class="q-mt-lg text-center">
        <router-link :to="{ name: 'login-admin' }" class="admin-link">
          <q-icon name="admin_panel_settings" size="14px" class="q-mr-xs" />
          Acceso Dirección de Carrera (Usuario y Contraseña)
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ ci: '' })
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.loginDocente(form.ci)
    router.push({ name: 'materias' })
  } catch (e) {
    error.value = e.response?.data?.message || 'CI no registrado como docente.'
  } finally {
    loading.value = false
  }
}
</script>

<style lang="scss" scoped>
.auth-page {
  position: relative;
  overflow: hidden;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    radial-gradient(ellipse at 30% 30%, rgba(30, 64, 175, 0.25) 0%, transparent 60%),
    radial-gradient(ellipse at 70% 70%, rgba(6, 182, 212, 0.15) 0%, transparent 60%),
    linear-gradient(135deg, #0a0f1e 0%, #0f172a 50%, #0a0f1e 100%);
}

.auth-card {
  position: relative;
  z-index: 10;
  width: 100%;
  max-width: 440px;
  background: rgba(15, 23, 42, 0.85);
  backdrop-filter: blur(30px);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 60px rgba(59, 130, 246, 0.1);
  padding: 40px;
}

.auth-card .auth-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 20px;
  background: linear-gradient(135deg, #1e40af, #06b6d4);
  box-shadow: 0 0 30px rgba(59, 130, 246, 0.4);
  margin: 0 auto 24px;
  font-size: 32px;
}

.auth-card h1 {
  font-size: 1.75rem;
  font-weight: 700;
  text-align: center;
  color: white;
  margin-bottom: 4px;
  letter-spacing: -0.5px;
}

.auth-card .subtitle {
  text-align: center;
  color: rgba(148, 163, 184, 0.8);
  font-size: 0.875rem;
  margin-bottom: 32px;
}

.particle {
  position: fixed;
  border-radius: 50%;
  filter: blur(60px);
  opacity: 0.15;
  pointer-events: none;
  animation: float 8s ease-in-out infinite;

  &.p1 { width: 300px; height: 300px; background: #1e40af; top: -100px; left: -100px; animation-delay: 0s; }
  &.p2 { width: 250px; height: 250px; background: #06b6d4; bottom: -80px; right: -80px; animation-delay: 3s; }
  &.p3 { width: 200px; height: 200px; background: #7c3aed; top: 50%; right: 20%; animation-delay: 6s; }
}

@keyframes float {
  0%, 100% { transform: translateY(0px) scale(1); }
  50% { transform: translateY(-20px) scale(1.05); }
}

.field-label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: rgba(148, 163, 184, 0.8);
  margin-bottom: 6px;
  letter-spacing: 0.5px;
  text-transform: uppercase;
}

.login-btn {
  background: linear-gradient(135deg, #1e40af, #2563eb) !important;
  color: white !important;
  border-radius: 12px !important;
  font-weight: 600 !important;
  letter-spacing: 0.5px !important;
  box-shadow: 0 4px 20px rgba(37, 99, 235, 0.4) !important;
  transition: all 0.3s !important;
  height: 52px;

  &:hover {
    background: linear-gradient(135deg, #2563eb, #3b82f6) !important;
    box-shadow: 0 6px 30px rgba(37, 99, 235, 0.6) !important;
    transform: translateY(-1px);
  }
}

.error-banner {
  background: rgba(239, 68, 68, 0.1) !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
  color: #fca5a5 !important;
  border-radius: 10px !important;
}

.admin-link {
  color: rgba(148, 163, 184, 0.6);
  font-size: 0.8rem;
  text-decoration: none;
  transition: all 0.2s;
  &:hover { color: #60a5fa; }
}
</style>
