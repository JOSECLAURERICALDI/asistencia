<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Cabecera de la página -->
    <div class="page-header q-mb-lg fade-in-up">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h2 class="page-title">Panel del Docente</h2>
          <p class="page-subtitle">
            <q-icon name="calendar_today" size="14px" class="q-mr-xs" />
            Gestión 2-2026 (03/08/2026 - 30/12/2026)
          </p>
        </div>

        <!-- Selector de fecha -->
        <div class="date-selector flex items-center gap-2">
          <q-btn
            flat dense no-caps
            :label="fechaSeleccionada === hoy ? 'Hoy' : 'Fecha: ' + fechaSeleccionada"
            :color="fechaSeleccionada === hoy ? 'blue-4' : 'orange-4'"
            icon="today"
            class="date-btn"
            @click="abrirSelectFecha"
          />

          <!-- Popup date picker -->
          <q-dialog v-model="showDatePicker">
            <q-card class="glass-card" style="min-width: 320px;">
              <q-card-section>
                <div class="text-subtitle1 text-weight-bold text-white q-mb-sm">
                  Seleccionar fecha de la gestión
                </div>
                <q-banner class="retroactive-banner q-mb-md" dense rounded>
                  <template #avatar><q-icon name="warning_amber" color="orange-4" size="18px" /></template>
                  <span style="font-size: 0.8rem; color: #fcd34d;">
                    Al seleccionar un día anterior, deberás justificar el registro retroactivo.
                  </span>
                </q-banner>
                <q-date
                  v-model="tempFecha"
                  dark flat
                  :options="fechasValidas"
                  color="blue-5"
                  class="full-width"
                />
              </q-card-section>
              <q-card-actions align="right" class="q-px-md q-pb-md">
                <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
                <q-btn flat label="Ir a esta fecha" color="blue-4" @click="confirmarFecha" />
              </q-card-actions>
            </q-card>
          </q-dialog>
        </div>
      </div>

      <!-- Pestañas principales -->
      <q-tabs
        v-model="activeTab"
        dense
        align="left"
        class="text-grey-4 q-mt-md tabs-style"
        active-color="blue-4"
        indicator-color="blue-4"
      >
        <q-tab name="hoy" label="Clases del Día" icon="today" />
        <q-tab name="pasadas" icon="history_toggle_off">
          <div class="flex items-center gap-2">
            <span>Clases Pasadas Sin Marcar</span>
            <q-badge v-if="clasesPasadas.length > 0" color="negative" class="text-weight-bold">
              {{ clasesPasadas.length }}
            </q-badge>
          </div>
        </q-tab>
      </q-tabs>
    </div>

    <!-- TAB 1: CLASES DEL DÍA / FECHA SELECCIONADA -->
    <div v-if="activeTab === 'hoy'">
      <!-- Banner retroactivo -->
      <q-banner v-if="esRetroactiva" class="retroactive-banner q-mb-md" rounded>
        <template #avatar>
          <q-icon name="history" color="orange-4" size="24px" />
        </template>
        <div>
          <div class="text-weight-bold" style="color: #fcd34d; font-size: 0.9rem;">
            Modo de registro retroactivo ({{ fechaDisplay }})
          </div>
          <div style="font-size: 0.8rem; color: #fde68a;">
            Se solicitará justificación obligatoria para marcar asistencia en fechas pasadas.
          </div>
        </div>
        <template #action>
          <q-btn flat no-caps label="Volver a hoy" color="orange-3" @click="irAHoy" />
        </template>
      </q-banner>

      <!-- Loading -->
      <div v-if="loading" class="flex justify-center q-mt-xl">
        <div class="text-center">
          <q-spinner-grid size="48px" color="blue-4" />
          <p class="q-mt-md" style="color: var(--color-text-muted);">Cargando clases...</p>
        </div>
      </div>

      <!-- Sin clases -->
      <div v-else-if="!loading && horarios.length === 0" class="empty-state fade-in-up">
        <q-icon name="event_busy" size="64px" style="color: var(--color-text-muted); opacity: 0.4;" />
        <h3 style="color: var(--color-text-muted); margin: 16px 0 8px;">Sin clases programadas</h3>
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">
          No tienes clases programadas pendientes para {{ fechaDisplay }}.
        </p>
        <q-btn v-if="clasesPasadas.length > 0" flat no-caps label="Ver Clases Pasadas Sin Marcar" icon="warning" color="warning" class="q-mt-md" @click="activeTab = 'pasadas'" />
      </div>

      <!-- Grid de materias -->
      <div v-else class="materias-grid">
        <div
          v-for="(horario, idx) in horarios"
          :key="horario.id"
          class="materia-card fade-in-up"
          :class="{ 'ya-registrado': horario.ya_registrado }"
          :style="`animation-delay: ${idx * 0.08}s`"
        >
          <div class="flex items-start justify-between q-mb-md">
            <q-badge
              :label="horario.tipo"
              :class="horario.tipo === 'Teórica' || horario.tipo === 'teorica' ? 'badge-teorica' : 'badge-practica'"
            />
            <div v-if="horario.ya_registrado" class="registered-badge">
              <q-icon name="check_circle" size="16px" color="green-4" />
              <span>Registrado / Omitido</span>
            </div>
          </div>

          <div class="materia-codigo">{{ horario.materia_codigo }}</div>
          <h3 class="materia-nombre" @click="irANomina(horario, fechaSeleccionada)">{{ horario.materia_nombre }}</h3>
          <div class="materia-carrera">
            <q-icon name="school" size="12px" class="q-mr-xs" />
            {{ horario.carrera }}
          </div>

          <div class="materia-schedule q-mb-md">
            <div class="schedule-item">
              <q-icon name="schedule" size="14px" />
              {{ horario.hora_inicio }} – {{ horario.hora_fin }}
            </div>
            <div class="schedule-item" v-if="horario.aula && horario.aula !== '-'">
              <q-icon name="room" size="14px" />
              {{ horario.aula }}
            </div>
          </div>

          <!-- Acciones -->
          <div class="flex items-center justify-between gap-2 q-mt-auto">
            <q-btn
              outline dense no-caps color="grey-4" icon="block" label="No marcar fecha"
              class="omitir-btn"
              @click.stop="abrirModalOmitir(horario, fechaSeleccionada)"
            >
              <q-tooltip>Registrar observación para no pasar lista en esta fecha</q-tooltip>
            </q-btn>

            <q-btn
              color="primary" dense no-caps icon="edit" label="Marcar"
              class="marcar-btn"
              @click.stop="irANomina(horario, fechaSeleccionada)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: CLASES PASADAS PENDIENTES (DESDE 3 DE AGOSTO) -->
    <div v-else-if="activeTab === 'pasadas'">
      <div v-if="loadingPasadas" class="flex justify-center q-mt-xl">
        <div class="text-center">
          <q-spinner-dots size="48px" color="orange-4" />
          <p class="q-mt-md" style="color: var(--color-text-muted);">Buscando clases pasadas sin marcar desde el 3 de Agosto...</p>
        </div>
      </div>

      <div v-else-if="clasesPasadas.length === 0" class="empty-state fade-in-up">
        <q-icon name="task_alt" size="64px" color="positive" style="opacity: 0.8;" />
        <h3 class="text-positive" style="margin: 16px 0 8px;">¡Al día con las asistencias!</h3>
        <p style="color: var(--color-text-muted); font-size: 0.875rem;">
          No tienes ninguna clase pasada pendiente de marcar desde el inicio de gestión (03 de Agosto de 2026).
        </p>
      </div>

      <div v-else class="materias-grid">
        <div
          v-for="(item, idx) in clasesPasadas"
          :key="item.id + '_' + item.fecha"
          class="materia-card past-card fade-in-up"
          :style="`animation-delay: ${idx * 0.05}s`"
        >
          <div class="flex items-start justify-between q-mb-md">
            <q-badge color="orange-9" class="q-px-sm q-py-xs text-weight-bold">
              📅 {{ item.fecha }} ({{ item.dia_semana }})
            </q-badge>
            <q-chip dense size="sm" color="negative" text-color="white" class="text-weight-bold">
              Sin marcar
            </q-chip>
          </div>

          <div class="materia-codigo">{{ item.materia_codigo }}</div>
          <h3 class="materia-nombre" @click="irANomina(item, item.fecha)">{{ item.materia_nombre }}</h3>
          <div class="materia-carrera">
            <q-icon name="school" size="12px" class="q-mr-xs" />
            {{ item.carrera }}
          </div>

          <div class="materia-schedule q-mb-md">
            <div class="schedule-item">
              <q-icon name="schedule" size="14px" />
              {{ item.hora_inicio }} – {{ item.hora_fin }}
            </div>
            <div class="schedule-item" v-if="item.aula && item.aula !== '-'">
              <q-icon name="room" size="14px" />
              {{ item.aula }}
            </div>
          </div>

          <!-- Acciones pasadas -->
          <div class="flex items-center justify-between gap-2 q-mt-auto">
            <q-btn
              outline dense no-caps color="grey-4" icon="block" label="No marcar fecha"
              class="omitir-btn"
              @click.stop="abrirModalOmitir(item, item.fecha)"
            >
              <q-tooltip>Omitir esta fecha con observación justificada</q-tooltip>
            </q-btn>

            <q-btn
              color="orange-7" dense no-caps icon="edit" label="Marcar"
              class="marcar-btn"
              @click.stop="irANomina(item, item.fecha)"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Omitir / No Marcar Clase -->
    <q-dialog v-model="modalOmitirOpen" persistent>
      <q-card style="width: 460px; max-width: 95vw;" class="glass-card dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="do_not_disturb_on" color="amber-4" size="28px" />
            <div>
              <div class="text-h6 text-weight-bold text-white">No marcar esta fecha</div>
              <div class="text-caption text-grey-4">Registrar observación institucional</div>
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none" v-if="selectedItem">
          <div class="info-box q-mb-md">
            <div class="text-weight-bold text-blue-3">{{ selectedItem.materia_codigo }} - {{ selectedItem.materia_nombre }}</div>
            <div class="text-caption text-grey-3">Fecha: <strong>{{ targetFechaOmitir }}</strong></div>
          </div>

          <div class="text-caption text-amber-3 q-mb-sm">
            ℹ️ Al guardar, esta clase quedará registrada pero <strong>NO marcará falta a ningún estudiante</strong>.
          </div>

          <q-select
            v-model="motivoPreset"
            :options="[
              'Feriado Nacional / Departamental',
              'Tolerancia Institucional',
              'Feria de Ciencias / Evento Académico',
              'Suspensión de Actividades',
              'Examen General / Evaluaciones Institucionales',
              'Licencia / Permiso Institucional Docente',
              'Otro motivo (Escribir abajo)'
            ]"
            label="Motivo o Justificación"
            outlined dark dense
            class="q-mb-sm"
          />

          <q-input
            v-model="motivoTexto"
            type="textarea"
            rows="3"
            label="Observación detallada"
            outlined dark dense
            placeholder="Ingrese detalles de la suspensión o no marcado de lista..."
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="amber-7" icon="check" label="Guardar Sin Marcar" :loading="savingOmitir" @click="procesarOmitirClase" unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { api } from 'boot/axios'
import { useQuasar, date } from 'quasar'

const $q = useQuasar()
const router = useRouter()

const activeTab = ref('hoy')
const horarios = ref([])
const clasesPasadas = ref([])
const loading = ref(false)
const loadingPasadas = ref(false)
const esRetroactiva = ref(false)
const showDatePicker = ref(false)
const tempFecha = ref('')

const modalOmitirOpen = ref(false)
const selectedItem = ref(null)
const targetFechaOmitir = ref('')
const motivoPreset = ref('Feriado Nacional / Departamental')
const motivoTexto = ref('')
const savingOmitir = ref(false)

const hoy = date.formatDate(new Date(), 'YYYY-MM-DD')
const fechaSeleccionada = ref(hoy)

const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']
const dias = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado']

const fechaDisplay = computed(() => {
  const d = new Date(fechaSeleccionada.value + 'T12:00:00')
  return `${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]}`
})

function fechasValidas(fechaStr) {
  const d = new Date(fechaStr)
  const hoyDate = new Date()
  hoyDate.setHours(23,59,59,999)
  return d <= hoyDate && d >= new Date('2026-08-03')
}

function abrirSelectFecha() {
  tempFecha.value = fechaSeleccionada.value
  showDatePicker.value = true
}

function confirmarFecha() {
  fechaSeleccionada.value = tempFecha.value
  showDatePicker.value = false
  cargarHorarios()
}

function irAHoy() {
  fechaSeleccionada.value = hoy
  cargarHorarios()
}

async function cargarHorarios() {
  loading.value = true
  try {
    const res = await api.get('/docente/horarios/dia', {
      params: { fecha: fechaSeleccionada.value }
    })
    horarios.value = res.data.horarios || []
    esRetroactiva.value = res.data.es_retroactiva
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar las clases.', position: 'top' })
  } finally {
    loading.value = false
  }
}

async function cargarClasesPasadas() {
  loadingPasadas.value = true
  try {
    const res = await api.get('/docente/horarios/clases-pendientes-pasadas')
    clasesPasadas.value = res.data.pendientes_pasadas || []
  } catch (e) {
    console.error('Error cargando clases pasadas:', e)
  } finally {
    loadingPasadas.value = false
  }
}

function abrirModalOmitir(item, fechaTarget) {
  selectedItem.value = item
  targetFechaOmitir.value = fechaTarget || fechaSeleccionada.value
  motivoPreset.value = 'Feriado Nacional / Departamental'
  motivoTexto.value = ''
  modalOmitirOpen.value = true
}

async function procesarOmitirClase() {
  if (!selectedItem.value) return
  const motivoFinal = motivoTexto.value ? `${motivoPreset.value}: ${motivoTexto.value}` : motivoPreset.value

  savingOmitir.value = true
  try {
    await api.post('/docente/horarios/omitir-clase', {
      horario_id: selectedItem.value.id,
      fecha: targetFechaOmitir.value,
      motivo: motivoFinal,
    })

    $q.notify({
      type: 'positive',
      message: '✅ Clase registrada como no marcada. No afectará a los estudiantes.',
      position: 'top',
    })

    modalOmitirOpen.value = false
    cargarHorarios()
    cargarClasesPasadas()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al registrar observación de clase.' })
  } finally {
    savingOmitir.value = false
  }
}

function irANomina(item, fechaTarget) {
  router.push({
    name: 'nomina',
    params: { horarioId: item.id },
    query: { fecha: fechaTarget || fechaSeleccionada.value }
  })
}

onMounted(() => {
  cargarHorarios()
  cargarClasesPasadas()
})
</script>

<style lang="scss" scoped>
.page-header {
  animation: fadeInUp 0.4s ease forwards;
}

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

.date-btn {
  background: rgba(59, 130, 246, 0.1) !important;
  border: 1px solid rgba(59, 130, 246, 0.2) !important;
  border-radius: 10px !important;
  color: #60a5fa !important;
  padding: 8px 16px !important;
}

.tabs-style {
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.retroactive-banner {
  background: rgba(245, 158, 11, 0.08) !important;
  border: 1px solid rgba(245, 158, 11, 0.2) !important;
  border-radius: 12px !important;
  color: #fde68a !important;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
  animation: fadeInUp 0.4s ease forwards;
}

.materias-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.materia-card {
  display: flex;
  flex-direction: column;
  min-height: 220px;
  position: relative;
  padding: 20px;
  background: rgba(30, 41, 59, 0.7);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 16px;
  transition: all 0.2s;

  &:hover {
    border-color: rgba(59, 130, 246, 0.4);
    transform: translateY(-3px);
  }

  &.past-card {
    border-color: rgba(245, 158, 11, 0.2);
    &:hover {
      border-color: rgba(245, 158, 11, 0.5);
    }
  }
}

.badge-teorica {
  background: rgba(59, 130, 246, 0.2) !important;
  color: #93c5fd !important;
  border: 1px solid rgba(59, 130, 246, 0.3) !important;
  font-size: 0.7rem !important;
  padding: 3px 10px !important;
  border-radius: 6px !important;
}

.badge-practica {
  background: rgba(16, 185, 129, 0.2) !important;
  color: #6ee7b7 !important;
  border: 1px solid rgba(16, 185, 129, 0.3) !important;
  font-size: 0.7rem !important;
  padding: 3px 10px !important;
  border-radius: 6px !important;
}

.registered-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  span {
    font-size: 0.75rem;
    color: #34d399;
    font-weight: 500;
  }
}

.materia-codigo {
  font-size: 0.75rem;
  font-weight: 700;
  color: #60a5fa;
  letter-spacing: 1.5px;
  text-transform: uppercase;
  margin-bottom: 4px;
}

.materia-nombre {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 6px;
  line-height: 1.3;
  cursor: pointer;
  &:hover {
    color: #60a5fa;
  }
}

.materia-carrera {
  font-size: 0.75rem;
  color: var(--color-text-muted);
  margin-bottom: 12px;
}

.materia-schedule {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.schedule-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.8rem;
  color: var(--color-text-muted);
  background: rgba(148, 163, 184, 0.08);
  padding: 4px 10px;
  border-radius: 6px;
}

.omitir-btn {
  border-radius: 8px !important;
  font-size: 0.75rem !important;
}

.marcar-btn {
  border-radius: 8px !important;
  font-weight: 700 !important;
  font-size: 0.8rem !important;
}

.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
}

.info-box {
  background: rgba(59, 130, 246, 0.08);
  border: 1px solid rgba(59, 130, 246, 0.2);
  padding: 12px 16px;
  border-radius: 10px;
}
</style>
