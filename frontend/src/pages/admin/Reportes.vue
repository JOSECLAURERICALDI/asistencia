<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 q-mb-xl fade-in-up">
      <div>
        <h2 class="page-title">Reportes por Rango de Fechas</h2>
        <p class="page-subtitle">Generación de reportes detallados y exportación a PDF/Excel</p>
      </div>
    </div>

    <!-- Panel de Filtros -->
    <q-card class="glass-card q-mb-xl fade-in-up" flat>
      <q-card-section>
        <div class="text-subtitle1 text-weight-bold text-white q-mb-md flex items-center gap-2">
          <q-icon name="tune" color="blue-4" />
          Parámetros de Reporte
        </div>

        <div class="row q-col-gutter-md">
          <!-- Fecha Inicio -->
          <div class="col-12 col-sm-6 col-md-3">
            <label class="field-label">Fecha Desde</label>
            <q-input v-model="filtros.fecha_inicio" type="date" outlined dark dense />
          </div>

          <!-- Fecha Fin -->
          <div class="col-12 col-sm-6 col-md-3">
            <label class="field-label">Fecha Hasta</label>
            <q-input v-model="filtros.fecha_fin" type="date" outlined dark dense />
          </div>

          <!-- Materia -->
          <div class="col-12 col-sm-6 col-md-4">
            <label class="field-label">Materia (Opcional - Todas por defecto)</label>
            <q-select
              v-model="filtros.materia"
              :options="opcionesMaterias"
              option-value="id"
              option-label="nombre_completo"
              clearable
              outlined dark dense
              placeholder="Todas las materias de la carrera"
            />
          </div>

          <!-- Botón Generar -->
          <div class="col-12 col-sm-6 col-md-2 flex items-end">
            <q-btn
              no-caps label="Buscar" icon="search" color="blue"
              class="full-width search-btn" :loading="loading"
              @click="generarReporte"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Resultados -->
    <div v-if="reporteData" class="fade-in-up">
      <!-- Acciones de Exportación & Resumen -->
      <div class="flex items-center justify-between flex-wrap gap-3 q-mb-md">
        <div class="text-h6 text-weight-bold text-white">
          Resultados ({{ reporteData.detalle.length }} registros)
        </div>
        <div class="flex items-center gap-2">
          <q-btn
            no-caps icon="picture_as_pdf" label="Exportar PDF" color="red-7"
            @click="exportar('pdf')" class="export-btn"
          />
          <q-btn
            no-caps icon="table_view" label="Exportar Excel" color="positive"
            @click="exportar('excel')" class="export-btn"
          />
        </div>
      </div>

      <!-- Resumen por materia -->
      <div class="row q-col-gutter-md q-mb-lg" v-if="reporteData.resumen?.length > 0">
        <div v-for="r in reporteData.resumen" :key="r.materia_codigo" class="col-12 col-sm-6 col-md-4">
          <div class="materia-summary-card glass-card q-pa-md">
            <div class="text-weight-bold text-blue-3">{{ r.materia_codigo }} - {{ r.materia_nombre }}</div>
            <div class="flex justify-between q-mt-sm text-caption">
              <span class="text-positive">Presentes: {{ r.total_presentes }}</span>
              <span class="text-negative">Ausentes: {{ r.total_ausentes }}</span>
              <span class="text-warning">Permisos: {{ r.total_permisos }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabla Detallada -->
      <q-card class="glass-card" flat>
        <q-card-section class="q-pa-none">
          <q-table
            :rows="reporteData.detalle"
            :columns="columns"
            row-key="id"
            flat dark
            :pagination="{ rowsPerPage: 15 }"
          >
            <template #body-cell-fecha="props">
              <q-td :props="props">
                <div class="text-weight-semibold text-white">{{ props.row.fecha }}</div>
                <div class="text-caption text-grey-5">{{ props.row.hora_inicio }} ({{ props.row.tipo }})</div>
              </q-td>
            </template>

            <template #body-cell-estudiante="props">
              <q-td :props="props">
                <div class="text-weight-bold text-white">{{ props.row.estudiante_nombre }}</div>
                <div class="text-caption text-grey-5">Carnet: {{ props.row.estudiante_carnet }}</div>
              </q-td>
            </template>

            <template #body-cell-materia="props">
              <q-td :props="props">
                <div class="text-weight-semibold text-blue-3">{{ props.row.materia_codigo }}</div>
                <div class="text-caption text-grey-5">{{ props.row.materia_nombre }}</div>
              </q-td>
            </template>

            <template #body-cell-estado="props">
              <q-td :props="props">
                <q-chip
                  :color="props.row.estado === 'presente' ? 'positive' : props.row.estado === 'ausente' ? 'negative' : 'warning'"
                  :text-color="props.row.estado === 'permiso' ? 'dark' : 'white'"
                  dense size="sm" class="text-capitalize text-weight-bold"
                  :label="props.row.estado"
                />
              </q-td>
            </template>

            <template #body-cell-retroactiva="props">
              <q-td :props="props">
                <div v-if="props.row.es_retroactiva">
                  <q-chip color="orange-9" text-color="white" dense size="xs" icon="history" label="Retroactiva" />
                  <div class="text-caption text-grey-4" style="font-size:0.7rem;">{{ props.row.justificacion }}</div>
                </div>
                <span v-else class="text-grey-6">-</span>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar, date } from 'quasar'

const $q = useQuasar()
const loading = ref(false)
const reporteData = ref(null)
const opcionesMaterias = ref([])

const hoy = date.formatDate(new Date(), 'YYYY-MM-DD')
const haceUnMes = date.formatDate(date.subtractFromDate(new Date(), { month: 1 }), 'YYYY-MM-DD')

const filtros = reactive({
  fecha_inicio: haceUnMes,
  fecha_fin: hoy,
  materia: null,
})

const columns = [
  { name: 'fecha', label: 'Fecha / Hora', align: 'left', field: 'fecha' },
  { name: 'materia', label: 'Materia', align: 'left', field: 'materia_nombre' },
  { name: 'estudiante', label: 'Estudiante', align: 'left', field: 'estudiante_nombre' },
  { name: 'docente', label: 'Docente Registrador', align: 'left', field: 'docente' },
  { name: 'estado', label: 'Estado', align: 'center', field: 'estado' },
  { name: 'retroactiva', label: 'Retroactivo / Justificación', align: 'left', field: 'es_retroactiva' },
]

async function cargarMaterias() {
  try {
    const res = await api.get('/admin/materias')
    opcionesMaterias.value = res.data.map(m => ({
      ...m,
      nombre_completo: `${m.codigo} - ${m.nombre}`
    }))
  } catch (e) {}
}

async function generarReporte() {
  if (!filtros.fecha_inicio || !filtros.fecha_fin) {
    $q.notify({ type: 'warning', message: 'Selecciona fecha de inicio y fin.' })
    return
  }

  loading.value = true
  try {
    const res = await api.get('/admin/reportes/por-fechas', {
      params: {
        fecha_inicio: filtros.fecha_inicio,
        fecha_fin: filtros.fecha_fin,
        materia_id: filtros.materia?.id || null,
      }
    })
    reporteData.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al generar el reporte.' })
  } finally {
    loading.value = false
  }
}

function exportar(tipo) {
  $q.notify({
    type: 'info',
    message: `Generando archivo ${tipo.toUpperCase()}...`,
    icon: 'download'
  })
}

onMounted(() => {
  cargarMaterias()
  generarReporte()
})
</script>

<style lang="scss" scoped>
.page-title { font-size: 1.75rem; font-weight: 800; color: var(--color-text); margin: 0 0 4px; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.875rem; margin: 0; }

.field-label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(148, 163, 184, 0.8);
  margin-bottom: 4px;
  text-transform: uppercase;
}

.search-btn {
  height: 40px;
  border-radius: 8px;
  font-weight: 600;
}

.export-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
}

.materia-summary-card {
  border-left: 3px solid #3b82f6;
}
</style>
