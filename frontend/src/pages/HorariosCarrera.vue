<template>
  <q-page class="q-pa-lg">
    <!-- Header -->
    <div class="header-card q-mb-lg q-pa-lg glass-card">
      <div class="row items-center justify-between q-col-gutter-md">
        <div class="col-12 col-md-6">
          <div class="flex items-center gap-3">
            <div class="icon-avatar">
              <q-icon name="schedule" size="28px" color="blue-4" />
            </div>
            <div>
              <h1 class="page-title text-white">Horarios por Carrera</h1>
              <p class="page-subtitle text-grey-4">
                Consulta y filtro completo de horarios académicos asignados a la carrera
              </p>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6 flex justify-end gap-3">
          <q-btn
            no-caps
            color="primary"
            icon="print"
            label="Imprimir / Exportar"
            @click="imprimirHorarios"
            unelevated
            class="export-btn"
          />
        </div>
      </div>

      <!-- Filtros -->
      <div class="row q-col-gutter-md q-mt-md">
        <div class="col-12 col-sm-4">
          <q-select
            v-model="carreraSeleccionada"
            :options="carrerasOptions"
            label="Carrera"
            option-value="id"
            option-label="nombre"
            outlined dark dense
            emit-value map-options
            @update:model-value="cargarHorarios"
          >
            <template #prepend>
              <q-icon name="school" color="blue-4" />
            </template>
          </q-select>
        </div>

        <div class="col-12 col-sm-4">
          <q-select
            v-model="diaSeleccionado"
            :options="diasOptions"
            label="Día de la semana"
            outlined dark dense
            @update:model-value="cargarHorarios"
          >
            <template #prepend>
              <q-icon name="today" color="blue-4" />
            </template>
          </q-select>
        </div>

        <div class="col-12 col-sm-4">
          <q-input
            v-model="filtroBuscar"
            placeholder="Buscar por materia, sigla o docente..."
            outlined dark dense
            clearable
          >
            <template #prepend>
              <q-icon name="search" color="grey-5" />
            </template>
          </q-input>
        </div>
      </div>
    </div>

    <!-- Spinner loading -->
    <div v-if="loading" class="text-center q-pa-xl">
      <q-spinner-dots color="primary" size="50px" />
      <div class="text-grey-4 q-mt-md">Cargando horarios de la carrera...</div>
    </div>

    <!-- Empty State -->
    <div v-else-if="horariosFiltrados.length === 0" class="empty-card glass-card text-center q-pa-xl">
      <q-icon name="event_busy" size="64px" color="grey-6" />
      <div class="text-h6 text-white q-mt-md">No se encontraron horarios</div>
      <div class="text-grey-4">No hay horarios asignados con los filtros seleccionados. Usa el botón "Importar Excel" para cargar la planilla de horarios.</div>
    </div>

    <!-- Grilla de Horarios por Día -->
    <div v-else class="row q-col-gutter-lg">
      <div
        v-for="(horariosDia, diaName) in horariosPorDia"
        :key="diaName"
        class="col-12 col-md-6 col-lg-4"
      >
        <div class="day-card glass-card">
          <div class="day-header flex items-center justify-between q-pa-md">
            <div class="flex items-center gap-2">
              <q-icon name="event" color="blue-4" size="20px" />
              <span class="text-weight-bold text-white text-uppercase" style="letter-spacing: 0.5px;">{{ diaName }}</span>
            </div>
            <q-badge color="blue-9" class="q-px-sm q-py-xs text-weight-bold">
              {{ horariosDia.length }} {{ horariosDia.length === 1 ? 'clase' : 'clases' }}
            </q-badge>
          </div>

          <q-separator dark class="separator-border" />

          <div class="q-pa-md flex flex-column gap-3">
            <div
              v-for="item in horariosDia"
              :key="item.id"
              class="schedule-item-card q-pa-md"
            >
              <div class="flex items-center justify-between q-mb-xs">
                <div class="flex items-center gap-2">
                  <q-badge color="primary" class="sigla-badge text-weight-bold">
                    {{ item.materia_codigo }}
                  </q-badge>
                  <q-chip
                    dense size="sm"
                    :color="item.tipo === 'Teórica' ? 'indigo-9' : 'teal-9'"
                    text-color="white"
                    class="text-weight-semibold"
                  >
                    {{ item.tipo }}
                  </q-chip>
                </div>
                <div class="text-caption text-blue-3 text-weight-bold flex items-center gap-1">
                  <q-icon name="schedule" size="14px" />
                  {{ item.hora_inicio }} - {{ item.hora_fin }}
                </div>
              </div>

              <div class="text-weight-bold text-white q-mt-xs" style="font-size: 0.95rem;">
                {{ item.materia_nombre }}
              </div>

              <div class="flex items-center justify-between q-mt-sm text-caption text-grey-4">
                <div class="flex items-center gap-1">
                  <q-icon name="meeting_room" color="amber-4" size="14px" />
                  <span class="text-weight-medium text-amber-3">{{ item.aula }}</span>
                </div>
                <div class="flex items-center gap-1">
                  <q-icon name="person" color="grey-4" size="14px" />
                  <span>{{ item.docente_nombre }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const loading = ref(false)
const horarios = ref([])
const carrerasOptions = ref([
  { id: null, nombre: 'Todas las Carreras' },
  { id: 1, nombre: 'Ingeniería de Sistemas (SIS-)' },
])
const carreraSeleccionada = ref(1)

const diasOptions = ['Todos', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
const diaSeleccionado = ref('Todos')
const filtroBuscar = ref('')

async function cargarHorarios() {
  loading.value = true
  try {
    const params = {}
    if (carreraSeleccionada.value) params.carrera_id = carreraSeleccionada.value
    if (diaSeleccionado.value && diaSeleccionado.value !== 'Todos') {
      params.dia_semana = diaSeleccionado.value
    }

    const res = await api.get('/admin/reportes/horarios-carrera', { params })
    horarios.value = res.data.horarios || []

    if (res.data.carreras?.length) {
      carrerasOptions.value = [
        { id: null, nombre: 'Todas las Carreras' },
        ...res.data.carreras.map(c => ({ id: c.id, nombre: `${c.nombre} (${c.sigla})` }))
      ]
    }
  } catch (e) {
    $q.notify({
      type: 'negative',
      message: 'Error al cargar los horarios de la carrera.',
      position: 'top',
    })
  } finally {
    loading.value = false
  }
}

const horariosFiltrados = computed(() => {
  if (!filtroBuscar.value) return horarios.value
  const q = filtroBuscar.value.toLowerCase().trim()
  return horarios.value.filter(h =>
    h.materia_codigo.toLowerCase().includes(q) ||
    h.materia_nombre.toLowerCase().includes(q) ||
    h.docente_nombre.toLowerCase().includes(q) ||
    h.aula.toLowerCase().includes(q)
  )
})

const horariosPorDia = computed(() => {
  const map = {}
  const ordenDias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado']
  
  // Inicializar orden
  ordenDias.forEach(d => { map[d] = [] })

  horariosFiltrados.value.forEach(item => {
    const dia = item.dia_semana
    if (!map[dia]) map[dia] = []
    map[dia].push(item)
  })

  // Eliminar días vacíos si hay filtro de día o si no tienen clases
  Object.keys(map).forEach(key => {
    if (map[key].length === 0) delete map[key]
  })

  return map
})

function imprimirHorarios() {
  window.print()
}

onMounted(cargarHorarios)
</script>

<style lang="scss" scoped>
.page-title {
  font-size: 1.5rem;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.5px;
}

.page-subtitle {
  font-size: 0.85rem;
  margin: 2px 0 0;
}

.glass-card {
  background: rgba(15, 23, 42, 0.7);
  backdrop-filter: blur(16px);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 16px;
}

.icon-avatar {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
}

.export-btn {
  border-radius: 10px;
  font-weight: 700;
  box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
}

.day-card {
  height: 100%;
  transition: transform 0.2s, border-color 0.2s;
  &:hover {
    border-color: rgba(59, 130, 246, 0.3);
    transform: translateY(-2px);
  }
}

.day-header {
  background: rgba(30, 41, 59, 0.5);
  border-top-left-radius: 16px;
  border-top-right-radius: 16px;
}

.separator-border {
  background: rgba(148, 163, 184, 0.1);
}

.schedule-item-card {
  background: rgba(30, 41, 59, 0.6);
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 12px;
  transition: all 0.15s;
  &:hover {
    background: rgba(30, 41, 59, 0.9);
    border-color: rgba(59, 130, 246, 0.25);
  }
}

.sigla-badge {
  font-size: 0.75rem;
  padding: 4px 8px;
  border-radius: 6px;
  background: linear-gradient(135deg, #1e40af, #3b82f6) !important;
}

@media print {
  .header-card, .export-btn, .admin-sidebar, .q-header {
    display: none !important;
  }
  .glass-card {
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #ccc !important;
  }
}
</style>
