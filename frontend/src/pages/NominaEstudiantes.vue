<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Cabecera -->
    <div class="flex items-center gap-3 q-mb-xl fade-in-up">
      <q-btn flat round icon="arrow_back" color="blue-4" @click="router.back()" />
      <div>
        <div class="text-caption" style="color: var(--color-text-muted); letter-spacing: 1px; text-transform: uppercase;">
          {{ horarioInfo?.materia_codigo }}
          <q-badge v-if="horarioInfo?.tipo" :label="horarioInfo.tipo === 'teorica' ? 'Teórica' : 'Práctica'" class="q-ml-sm" style="font-size: 0.65rem;" />
        </div>
        <h2 class="page-title">{{ horarioInfo?.materia_nombre || 'Nómina de Estudiantes' }}</h2>
        <div class="flex items-center gap-3">
          <span style="color: var(--color-text-muted); font-size: 0.8rem;">
            <q-icon name="schedule" size="13px" class="q-mr-xs" />
            {{ horarioInfo?.hora_inicio }} – {{ horarioInfo?.hora_fin }}
          </span>
          <span v-if="horarioInfo?.aula" style="color: var(--color-text-muted); font-size: 0.8rem;">
            <q-icon name="room" size="13px" class="q-mr-xs" />
            {{ horarioInfo?.aula }}
          </span>
          <q-badge :label="fechaDisplay" style="background: rgba(59,130,246,0.15); color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); font-size: 0.75rem;" />
        </div>
      </div>
    </div>

    <!-- Banner retroactivo -->
    <q-banner v-if="esRetroactiva" class="retroactive-banner q-mb-lg" rounded>
      <template #avatar>
        <q-icon name="history" color="orange-4" size="22px" />
      </template>
      <div>
        <div class="text-weight-bold" style="color: #fcd34d; font-size: 0.9rem;">Registro Retroactivo</div>
        <div style="font-size: 0.8rem; color: #fde68a;">Esta asistencia es de un día anterior. Deberás ingresar una justificación antes de guardar.</div>
      </div>
    </q-banner>

    <!-- Justificación retroactiva (si aplica) -->
    <q-card v-if="esRetroactiva" class="glass-card q-mb-lg fade-in-up" flat>
      <q-card-section>
        <div class="flex items-center gap-2 q-mb-sm">
          <q-icon name="edit_note" color="orange-4" size="20px" />
          <span class="text-weight-semibold" style="color: #fcd34d;">Justificación requerida</span>
          <q-chip size="sm" color="orange" text-color="white" label="Obligatoria" />
        </div>
        <q-input
          v-model="justificacion"
          type="textarea"
          placeholder="Explica por qué estás registrando la asistencia en una fecha anterior (ej: 'Sistema no disponible el día de la clase', 'Registro olvidado')..."
          outlined dark
          :rows="3"
          counter
          maxlength="500"
          :rules="[val => esRetroactiva ? (!!val?.trim() || 'La justificación es obligatoria para registros retroactivos') : true]"
          class="justif-input"
        />
      </q-card-section>
    </q-card>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center q-mt-xl">
      <div class="text-center">
        <q-spinner-grid size="48px" color="blue-4" />
        <p class="q-mt-md" style="color: var(--color-text-muted);">Cargando nómina...</p>
      </div>
    </div>

    <template v-else>
      <!-- Barra de acciones rápidas -->
      <div class="actions-bar q-mb-lg fade-in-up">
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Contadores -->
          <div class="counter-chip presente">
            <q-icon name="check_circle" size="14px" />
            {{ countPresentes }} presentes
          </div>
          <div class="counter-chip ausente">
            <q-icon name="cancel" size="14px" />
            {{ countAusentes }} ausentes
          </div>
          <div class="counter-chip permiso">
            <q-icon name="access_time" size="14px" />
            {{ countPermisos }} permisos
          </div>
          <div class="counter-chip total" style="margin-left: auto;">
            <q-icon name="group" size="14px" />
            {{ nomina.length }} estudiantes
          </div>
        </div>

        <!-- Acciones masivas -->
        <div class="flex gap-2 q-mt-sm flex-wrap">
          <q-btn
            flat no-caps dense size="sm"
            label="Todos presentes"
            icon="done_all"
            color="green-4"
            @click="marcarTodos('presente')"
            class="action-btn"
          />
          <q-btn
            flat no-caps dense size="sm"
            label="Todos ausentes"
            icon="remove_done"
            color="red-4"
            @click="marcarTodos('ausente')"
            class="action-btn"
          />
          <q-space />
          <q-btn
            no-caps
            label="Guardar asistencia"
            icon="save"
            color="blue"
            :loading="saving"
            :disable="!todosMarcados"
            @click="guardarMasivo"
            class="save-btn"
            unelevated
          />
        </div>
      </div>

      <!-- Lista de estudiantes -->
      <div class="students-list">
        <div
          v-for="(est, idx) in nomina"
          :key="est.inscripcion_id"
          class="student-row fade-in-up"
          :class="{ [`estado-${est.estadoLocal}`]: est.estadoLocal }"
          :style="`animation-delay: ${idx * 0.04}s`"
        >
          <!-- Avatar con número -->
          <div class="student-number">{{ idx + 1 }}</div>

          <!-- Info estudiante -->
          <div class="student-info">
            <div class="student-name">
              {{ est.primer_apellido }} {{ est.segundo_apellido }}
              <span class="nombres-light">{{ est.nombres }}</span>
              <q-chip v-if="est.es_abandono" color="negative" text-color="white" dense icon="no_accounts" label="ABANDONO" size="sm" class="q-ml-sm text-weight-bold" />
            </div>
            <div class="student-carnet">
              <q-icon name="badge" size="12px" class="q-mr-xs" />
              {{ est.carnet }}
              <span v-if="est.motivo_abandono" class="text-red-3 q-ml-xs">({{ est.motivo_abandono }})</span>
            </div>
          </div>

          <!-- Toggle de estado -->
          <div class="estado-toggle">
            <template v-if="est.es_abandono">
              <q-chip color="blue-grey-9" text-color="amber-3" dense icon="block" label="Inhabilitado por Abandono" size="sm" class="text-weight-bold" />
            </template>
            <template v-else>
              <q-btn
                :class="['estado-btn', { active: est.estadoLocal === 'presente' }]"
                flat no-caps dense size="sm"
                label="P"
                @click="setEstado(est, 'presente')"
              >
                <q-tooltip>Presente</q-tooltip>
              </q-btn>
              <q-btn
                :class="['estado-btn permiso-btn', { active: est.estadoLocal === 'permiso' }]"
                flat no-caps dense size="sm"
                label="PE"
                @click="setEstado(est, 'permiso')"
              >
                <q-tooltip>Permiso</q-tooltip>
              </q-btn>
              <q-btn
                :class="['estado-btn ausente-btn', { active: est.estadoLocal === 'ausente' }]"
                flat no-caps dense size="sm"
                label="A"
                @click="setEstado(est, 'ausente')"
              >
                <q-tooltip>Ausente</q-tooltip>
              </q-btn>
            </template>
          </div>

          <!-- Indicador guardado -->
          <div class="save-indicator">
            <q-icon v-if="est.guardado" name="cloud_done" color="green-4" size="18px">
              <q-tooltip>Guardado</q-tooltip>
            </q-icon>
            <q-icon v-else-if="est.estadoLocal" name="edit" color="grey-5" size="16px">
              <q-tooltip>Sin guardar</q-tooltip>
            </q-icon>
          </div>
        </div>
      </div>

      <!-- Empty -->
      <div v-if="nomina.length === 0" class="empty-state">
        <q-icon name="group_off" size="56px" style="color: var(--color-text-muted); opacity: 0.4;" />
        <p style="color: var(--color-text-muted); margin-top: 12px;">No hay estudiantes inscritos en esta materia.</p>
      </div>

      <!-- Botón guardar flotante (mobile) -->
      <q-page-sticky position="bottom-right" :offset="[20, 20]" class="lt-sm">
        <q-btn
          fab
          icon="save"
          color="blue"
          :loading="saving"
          :disable="!todosMarcados"
          @click="guardarMasivo"
        />
      </q-page-sticky>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from 'boot/axios'
import { useQuasar, date } from 'quasar'

const props = defineProps({ horarioId: { type: String, required: true } })
const $q = useQuasar()
const route = useRoute()
const router = useRouter()

const nomina = ref([])
const horarioInfo = ref(null)
const loading = ref(false)
const saving = ref(false)
const justificacion = ref('')

const fecha = computed(() => route.query.fecha || date.formatDate(new Date(), 'YYYY-MM-DD'))
const esRetroactiva = computed(() => fecha.value < date.formatDate(new Date(), 'YYYY-MM-DD'))

const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']
const diasSemana = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado']
const fechaDisplay = computed(() => {
  const d = new Date(fecha.value + 'T12:00:00')
  return `${diasSemana[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]}`
})

const countPresentes = computed(() => nomina.value.filter(e => !e.es_abandono && e.estadoLocal === 'presente').length)
const countAusentes = computed(() => nomina.value.filter(e => !e.es_abandono && e.estadoLocal === 'ausente').length)
const countPermisos = computed(() => nomina.value.filter(e => !e.es_abandono && e.estadoLocal === 'permiso').length)
const todosMarcados = computed(() => {
  const activos = nomina.value.filter(e => !e.es_abandono)
  return activos.length > 0 && activos.every(e => e.estadoLocal)
})

function setEstado(est, estado) {
  if (est.es_abandono) return
  est.estadoLocal = estado
  est.guardado = false
}

function marcarTodos(estado) {
  nomina.value.forEach(e => {
    if (!e.es_abandono) {
      e.estadoLocal = estado
      e.guardado = false
    }
  })
}

async function cargarNomina() {
  loading.value = true
  try {
    const res = await api.get(`/docente/horarios/${props.horarioId}/nomina`, {
      params: { fecha: fecha.value }
    })
    horarioInfo.value = res.data.horario
    nomina.value = res.data.nomina.map(e => ({
      ...e,
      estadoLocal: e.estado || null,
      guardado: !!e.estado,
    }))
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar la nómina.', position: 'top' })
  } finally {
    loading.value = false
  }
}

async function guardarMasivo() {
  if (esRetroactiva.value && !justificacion.value?.trim()) {
    $q.notify({
      type: 'warning',
      message: 'Debes ingresar una justificación para el registro retroactivo.',
      position: 'top',
    })
    return
  }

  saving.value = true
  try {
    await api.post('/docente/asistencia/masivo', {
      horario_id: parseInt(props.horarioId),
      fecha: fecha.value,
      justificacion_retroactiva: esRetroactiva.value ? justificacion.value : null,
      asistencias: nomina.value.map(e => ({
        inscripcion_id: e.inscripcion_id,
        estado: e.estadoLocal,
      })),
    })
    nomina.value.forEach(e => { e.guardado = true })
    $q.notify({
      type: 'positive',
      message: '✅ Asistencia guardada correctamente.',
      position: 'top',
      timeout: 1500,
    })
    setTimeout(() => {
      router.push({ name: 'materias' })
    }, 1000)
  } catch (e) {
    const msg = e.response?.data?.message || 'Error al guardar la asistencia.'
    $q.notify({ type: 'negative', message: msg, position: 'top' })
  } finally {
    saving.value = false
  }
}

onMounted(cargarNomina)
</script>

<style lang="scss" scoped>
.page-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--color-text);
  margin: 0 0 4px;
  letter-spacing: -0.5px;
}

.retroactive-banner {
  background: rgba(245, 158, 11, 0.08) !important;
  border: 1px solid rgba(245, 158, 11, 0.2) !important;
  border-radius: 12px !important;
}

.justif-input {
  :deep(.q-field__control) {
    background: rgba(30, 41, 59, 0.8) !important;
  }
}

.actions-bar {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
}

.counter-chip {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 5px 12px;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 600;

  &.presente { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.25); }
  &.ausente  { background: rgba(239,68,68,0.15);  color: #f87171; border: 1px solid rgba(239,68,68,0.25); }
  &.permiso  { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.25); }
  &.total    { background: rgba(148,163,184,0.1); color: var(--color-text-muted); border: 1px solid var(--color-border); }
}

.action-btn {
  background: rgba(148,163,184,0.1) !important;
  border-radius: 8px !important;
  padding: 6px 12px !important;
}

.save-btn {
  border-radius: 10px !important;
  font-weight: 600 !important;
  letter-spacing: 0.3px !important;
  box-shadow: 0 4px 15px rgba(59,130,246,0.3) !important;
}

.students-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.student-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  transition: var(--transition);

  &:hover {
    border-color: rgba(59, 130, 246, 0.2);
    background: rgba(30, 41, 59, 0.9);
  }

  &.estado-presente {
    border-color: rgba(16, 185, 129, 0.3);
    background: rgba(16, 185, 129, 0.05);
  }
  &.estado-ausente {
    border-color: rgba(239, 68, 68, 0.3);
    background: rgba(239, 68, 68, 0.05);
  }
  &.estado-permiso {
    border-color: rgba(245, 158, 11, 0.3);
    background: rgba(245, 158, 11, 0.05);
  }
}

.student-number {
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: rgba(148, 163, 184, 0.1);
  color: var(--color-text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  flex-shrink: 0;
}

.student-info {
  flex: 1;
  min-width: 0;
}

.student-name {
  font-weight: 600;
  color: var(--color-text);
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  .nombres-light {
    font-weight: 400;
    color: var(--color-text-muted);
  }
}

.student-carnet {
  font-size: 0.72rem;
  color: var(--color-text-muted);
}

.estado-toggle {
  display: flex;
  gap: 4px;
  flex-shrink: 0;
}

.estado-btn {
  width: 36px;
  height: 32px;
  border-radius: 8px !important;
  font-weight: 700 !important;
  font-size: 0.7rem !important;
  background: rgba(148, 163, 184, 0.08) !important;
  color: var(--color-text-muted) !important;
  border: 1px solid var(--color-border) !important;
  transition: all 0.15s !important;

  &.active {
    background: rgba(16, 185, 129, 0.2) !important;
    color: #34d399 !important;
    border-color: rgba(16, 185, 129, 0.4) !important;
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.2) !important;
  }

  &.ausente-btn.active {
    background: rgba(239, 68, 68, 0.2) !important;
    color: #f87171 !important;
    border-color: rgba(239, 68, 68, 0.4) !important;
    box-shadow: 0 0 10px rgba(239, 68, 68, 0.2) !important;
  }

  &.permiso-btn.active {
    background: rgba(245, 158, 11, 0.2) !important;
    color: #fbbf24 !important;
    border-color: rgba(245, 158, 11, 0.4) !important;
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.2) !important;
  }
}

.save-indicator {
  width: 24px;
  display: flex;
  justify-content: center;
  flex-shrink: 0;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}
</style>
