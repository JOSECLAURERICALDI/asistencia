<template>
  <div class="auth-page">
    <div class="particle p1"></div>
    <div class="particle p2"></div>
    <div class="particle p3"></div>

    <div class="auth-card fade-in-up">
      <div class="text-center q-mb-lg">
        <img src="/logo-unitepc.png" style="max-height: 56px; background: #ffffff; padding: 6px 16px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.4);" alt="UNITEPC Logo" />
      </div>
      <h1>Direccion de Carrera</h1>
      <p class="subtitle">Acceso al panel administrativo</p>

      <q-form @submit="handleLogin" class="q-gutter-md">
        <div>
          <label class="field-label">Correo electrónico</label>
          <q-input
            v-model="form.email"
            type="email"
            placeholder="director@carsis.edu.bo"
            outlined dark
            :rules="[val => !!val || 'El correo es requerido']"
            :disable="loading"
          >
            <template #prepend>
              <q-icon name="email" color="purple-4" />
            </template>
          </q-input>
        </div>

        <div>
          <label class="field-label">Contraseña</label>
          <q-input
            v-model="form.password"
            placeholder="Tu contraseña"
            :type="showPwd ? 'text' : 'password'"
            outlined dark
            :rules="[val => !!val || 'La contraseña es requerida']"
            :disable="loading"
          >
            <template #prepend>
              <q-icon name="lock" color="purple-4" />
            </template>
            <template #append>
              <q-icon
                :name="showPwd ? 'visibility_off' : 'visibility'"
                class="cursor-pointer" color="grey-5"
                @click="showPwd = !showPwd"
              />
            </template>
          </q-input>
        </div>

        <q-banner v-if="error" dense rounded class="error-banner">
          <template #avatar><q-icon name="error_outline" color="red-4" /></template>
          {{ error }}
        </q-banner>

        <q-btn
          type="submit"
          :loading="loading"
          class="login-btn full-width q-mt-md"
          size="lg" no-caps
        >
          <template #loading><q-spinner-dots size="24px" color="white" /></template>
          <q-icon name="admin_panel_settings" class="q-mr-sm" size="20px" />
          Ingresar al Panel
        </q-btn>
      </q-form>

      <div class="q-mt-lg text-center">
        <router-link :to="{ name: 'login' }" class="back-link">
          <q-icon name="arrow_back" size="14px" class="q-mr-xs" />
          Volver al login docente
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
const form = reactive({ email: 'director@carsis.edu.bo', password: 'director123' })
const showPwd = ref(false)
const loading = ref(false)
const error = ref('')

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.loginAdmin(form.email, form.password)
    router.push({ name: 'admin-horarios-carrera' })
  } catch (e) {
    error.value = e.response?.data?.message || 'Credenciales incorrectas.'
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
    radial-gradient(ellipse at 30% 30%, rgba(124, 58, 237, 0.25) 0%, transparent 60%),
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
  border: 1px solid rgba(124, 58, 237, 0.3);
  border-radius: 24px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5), 0 0 60px rgba(124, 58, 237, 0.2);
  padding: 40px;
}

.auth-card .auth-logo {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 72px;
  height: 72px;
  border-radius: 20px;
  box-shadow: 0 0 30px rgba(124, 58, 237, 0.4);
  margin: 0 auto 24px;
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

  &.p1 { width: 300px; height: 300px; background: #7c3aed; top: -100px; left: -100px; animation-delay: 0s; }
  &.p2 { width: 250px; height: 250px; background: #06b6d4; bottom: -80px; right: -80px; animation-delay: 3s; }
  &.p3 { width: 200px; height: 200px; background: #3b82f6; top: 50%; right: 20%; animation-delay: 6s; }
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
  background: linear-gradient(135deg, #7c3aed, #6d28d9) !important;
  color: white !important;
  border-radius: 12px !important;
  font-weight: 600 !important;
  box-shadow: 0 4px 20px rgba(124, 58, 237, 0.4) !important;
  height: 52px;
  &:hover {
    box-shadow: 0 6px 30px rgba(124, 58, 237, 0.6) !important;
    transform: translateY(-1px);
  }
}

.error-banner {
  background: rgba(239, 68, 68, 0.1) !important;
  border: 1px solid rgba(239, 68, 68, 0.3) !important;
  color: #fca5a5 !important;
  border-radius: 10px !important;
}

.back-link {
  color: rgba(148, 163, 184, 0.6);
  font-size: 0.8rem;
  text-decoration: none;
  &:hover { color: #a78bfa; }
}
</style>
