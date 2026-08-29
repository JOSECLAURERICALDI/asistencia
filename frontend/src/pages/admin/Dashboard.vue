<template>
  <q-page class="page-bg q-pa-lg">
    <div class="q-mb-xl fade-in-up">
      <h2 class="page-title">Dashboard</h2>
      <p class="page-subtitle">Resumen del día · {{ fechaHoy }}</p>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid q-mb-xl">
      <div class="stat-card fade-in-up animate-delay-1" v-if="!loading">
        <div class="stat-icon" style="background: rgba(59,130,246,0.15);">📚</div>
        <div class="stat-value" style="background: linear-gradient(135deg, #60a5fa, #93c5fd); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
          {{ stats.total_clases_hoy }}
        </div>
        <div class="stat-label">Clases programadas hoy</div>
      </div>

      <div class="stat-card fade-in-up animate-delay-2" v-if="!loading">
        <div class="stat-icon" style="background: rgba(16,185,129,0.15);">✅</div>
        <div class="stat-value" style="background: linear-gradient(135deg, #34d399, #6ee7b7); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
          {{ stats.clases_con_asistencia }}
        </div>
        <div class="stat-label">Con asistencia registrada</div>
      </div>

      <div class="stat-card fade-in-up animate-delay-3" @click="$router.push({name:'admin-docentes'})" style="cursor:pointer;" v-if="!loading">
        <div class="stat-icon" style="background: rgba(239,68,68,0.15);">⚠️</div>
        <div class="stat-value" style="background: linear-gradient(135deg, #f87171, #fca5a5); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
          {{ stats.clases_sin_asistencia }}
        </div>
        <div class="stat-label">Sin asistencia registrada</div>
        <div style="font-size: 0.72rem; color: #60a5fa; margin-top: 8px;">Ver detalle →</div>
      </div>

      <div class="stat-card fade-in-up animate-delay-4" @click="$router.push({name:'admin-faltas'})" style="cursor:pointer;" v-if="!loading">
        <div class="stat-icon" style="background: rgba(245,158,11,0.15);">🔴</div>
        <div class="stat-value" style="background: linear-gradient(135deg, #fbbf24, #fcd34d); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
          {{ stats.estudiantes_con_faltas }}
        </div>
        <div class="stat-label">Estudiantes con +2 faltas</div>
        <div style="font-size: 0.72rem; color: #60a5fa; margin-top: 8px;">Ver reporte →</div>
      </div>

      <!-- Skeleton loaders -->
      <template v-if="loading">
        <q-skeleton v-for="i in 4" :key="i" class="stat-card" height="140px" animation="wave" dark />
      </template>
    </div>

    <!-- Barra de progreso del día -->
    <q-card v-if="!loading && stats.total_clases_hoy > 0" class="glass-card q-mb-xl fade-in-up" flat>
      <q-card-section>
        <div class="flex items-center justify-between q-mb-md">
          <div>
            <div class="text-weight-bold text-white" style="font-size: 1rem;">Progreso del día</div>
            <div style="color: var(--color-text-muted); font-size: 0.8rem;">
              {{ stats.clases_con_asistencia }} de {{ stats.total_clases_hoy }} clases con asistencia registrada
            </div>
          </div>
          <q-chip
            :label="`${porcentaje}%`"
            :color="porcentaje >= 75 ? 'positive' : porcentaje >= 50 ? 'warning' : 'negative'"
            text-color="white"
            style="font-weight: 700; font-size: 1rem;"
          />
        </div>
        <q-linear-progress
          :value="porcentaje / 100"
          :color="porcentaje >= 75 ? 'positive' : porcentaje >= 50 ? 'warning' : 'negative'"
          track-color="grey-9"
          rounded size="12px"
          class="progress-bar"
          animation-speed="800"
        />
      </q-card-section>
    </q-card>

    <!-- Accesos rápidos -->
    <div>
      <div class="text-weight-bold text-white q-mb-md" style="font-size: 1rem; opacity: 0.7; letter-spacing: 1px; text-transform: uppercase; font-size: 0.75rem;">Accesos rápidos</div>
      <div class="quick-actions">
        <q-card
          v-for="action in quickActions"
          :key="action.to"
          class="quick-action-card glass-card"
          flat @click="$router.push({name: action.to})" style="cursor: pointer;"
        >
          <q-card-section class="flex items-center gap-3">
            <div class="quick-icon" :style="`background: ${action.bg};`">{{ action.emoji }}</div>
            <div>
              <div class="text-weight-semibold text-white" style="font-size: 0.9rem;">{{ action.label }}</div>
              <div style="color: var(--color-text-muted); font-size: 0.75rem;">{{ action.desc }}</div>
            </div>
            <q-space />
            <q-icon name="chevron_right" color="grey-6" />
          </q-card-section>
        </q-card>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const loading = ref(true)
const stats = ref({
  total_clases_hoy: 0,
  clases_con_asistencia: 0,
  clases_sin_asistencia: 0,
  estudiantes_con_faltas: 0,
})

const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']
const dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado']
const ahora = new Date()
const fechaHoy = `${dias[ahora.getDay()]} ${ahora.getDate()} de ${meses[ahora.getMonth()]} ${ahora.getFullYear()}`

const porcentaje = computed(() =>
  stats.value.total_clases_hoy > 0
    ? Math.round((stats.value.clases_con_asistencia / stats.value.total_clases_hoy) * 100)
    : 0
)

const quickActions = [
  { to: 'admin-docentes', emoji: '📋', label: 'Docentes sin marcar', desc: 'Ver quiénes no registraron asistencia hoy', bg: 'rgba(239,68,68,0.15)' },
  { to: 'admin-faltas', emoji: '👤', label: 'Estudiantes con faltas', desc: 'Más de 2 ausencias registradas', bg: 'rgba(245,158,11,0.15)' },
  { to: 'admin-reportes', emoji: '📊', label: 'Reportes por fechas', desc: 'Exportar a PDF o Excel', bg: 'rgba(59,130,246,0.15)' },
]

async function cargarDashboard() {
  loading.value = true
  try {
    const res = await api.get('/admin/dashboard')
    stats.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar el dashboard.', position: 'top' })
  } finally {
    loading.value = false
  }
}

onMounted(cargarDashboard)
</script>

<style lang="scss" scoped>
.page-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: var(--color-text);
  margin: 0 0 4px;
  letter-spacing: -0.5px;
}
.page-subtitle {
  color: var(--color-text-muted);
  font-size: 0.875rem;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 20px;
}

.progress-bar {
  border-radius: 10px;
}

.quick-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.quick-action-card {
  transition: all 0.2s !important;
  &:hover {
    transform: translateX(4px);
    border-color: rgba(59, 130, 246, 0.3) !important;
  }
}

.quick-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}
</style>
