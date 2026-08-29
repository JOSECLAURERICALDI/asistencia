<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 q-mb-xl fade-in-up">
      <div>
        <h2 class="page-title">Docentes y Control de Asistencia por Clases</h2>
        <p class="page-subtitle">Seguimiento detallado de clases marcadas, pendientes, retroactivas y no marcadas por motivo</p>
      </div>

      <!-- Filtros por Fecha y Estado -->
      <div class="flex items-center gap-3 flex-wrap">
        <q-btn
          no-caps
          color="indigo-7"
          icon="history"
          label="📅 Histórico (Desde 03/08/2026)"
          @click="seleccionarHistorico"
          class="action-btn"
          unelevated
        />

        <div class="flex items-center gap-2">
          <q-input
            v-model="fechaInicio"
            type="date"
            label="Desde"
            outlined dark dense
            style="width: 135px;"
            @update:model-value="cargarReporte"
          />
          <q-input
            v-model="fechaFin"
            type="date"
            label="Hasta"
            outlined dark dense
            style="width: 135px;"
            @update:model-value="cargarReporte"
          />
        </div>

        <q-select
          v-model="filtroEstado"
          :options="[
            { label: '📌 Sin Marcar (Pendientes 0%)', value: 'sin_marcar' },
            { label: '✅ Marcadas Completas', value: 'marcadas' },
            { label: '⏳ Marcado Incompleto', value: 'parcialmente' },
            { label: '🛑 No Marcadas (Justificadas)', value: 'omitidas' },
            { label: '🌐 Todas las Clases', value: 'todas' }
          ]"
          option-value="value"
          option-label="label"
          emit-value map-options
          label="Filtrar por Estado"
          outlined dark dense
          style="min-width: 230px;"
        />

        <q-btn
          no-caps icon="table_chart" color="positive" label="Exportar Excel"
          @click="exportarExcel" class="action-btn" unelevated
        />
        
        <q-btn
          no-caps icon="picture_as_pdf" color="red-7" label="Exportar PDF"
          @click="exportarPDF" class="action-btn" unelevated
        />

        <q-btn
          flat no-caps icon="refresh" color="blue-4" label="Actualizar"
          @click="cargarReporte" class="glass-btn"
        />
      </div>
    </div>

    <!-- Summary cards -->
    <div class="row q-col-gutter-sm q-mb-lg">
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stat-mini-card danger cursor-pointer" @click="filtroEstado = 'sin_marcar'">
          <div class="stat-num">{{ sinMarcarList.length }}</div>
          <div class="stat-text">Sin Marcar (Pendientes)</div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stat-mini-card success cursor-pointer" @click="filtroEstado = 'marcadas'">
          <div class="stat-num">{{ marcadasList.length }}</div>
          <div class="stat-text">Marcadas Completas</div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stat-mini-card info cursor-pointer" @click="filtroEstado = 'omitidas'">
          <div class="stat-num">{{ omitidasList.length }}</div>
          <div class="stat-text">No Marcadas (Justificadas)</div>
        </div>
      </div>
      <div class="col-12 col-sm-6 col-md-3">
        <div class="stat-mini-card total cursor-pointer" @click="filtroEstado = 'todas'">
          <div class="stat-num">{{ totalClases }}</div>
          <div class="stat-text">Total Clases Programadas</div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center q-py-xl">
      <q-spinner-grid size="48px" color="blue-4" />
    </div>

    <template v-else>
      <q-card class="glass-card" flat>
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="assignment" color="blue-4" size="22px" />
            <div class="text-h6 text-weight-bold text-white">
              Reporte de Asistencia Docente ({{ listaFiltrada.length }} registro/s)
            </div>
          </div>
          <div class="text-caption text-grey-4">Del {{ fechaInicio }} al {{ fechaFin }}</div>
        </q-card-section>

        <q-card-section class="q-pa-none">
          <q-table
            :rows="listaFiltrada"
            :columns="columns"
            row-key="docente_id"
            flat dark
            :pagination="{ rowsPerPage: 15 }"
            no-data-label="No se encontraron registros para el filtro seleccionado"
          >
            <template #body-cell-fecha="props">
              <q-td :props="props">
                <div class="text-weight-bold text-blue-3">📅 {{ props.row.fecha }}</div>
                <div class="text-caption text-grey-4">{{ props.row.dia_semana }}</div>
              </q-td>
            </template>

            <template #body-cell-docente="props">
              <q-td :props="props">
                <div class="text-weight-bold text-white">{{ props.row.docente_nombre }}</div>
                <div class="text-caption text-grey-5">CI: {{ props.row.docente_ci }}</div>
              </q-td>
            </template>

            <template #body-cell-materia="props">
              <q-td :props="props">
                <div class="text-weight-semibold text-blue-3">{{ props.row.materia_codigo }} - {{ props.row.materia_nombre }}</div>
                <div class="text-caption text-grey-5">{{ props.row.carrera }}</div>
              </q-td>
            </template>

            <template #body-cell-horario="props">
              <q-td :props="props">
                <q-chip dense dark size="sm" icon="schedule" color="dark">
                  {{ props.row.hora_inicio }} - {{ props.row.hora_fin }}
                </q-chip>
                <span class="text-caption text-grey-4 q-ml-xs">Aula: {{ props.row.aula || 'N/A' }}</span>
              </q-td>
            </template>

            <template #body-cell-estado="props">
              <q-td :props="props" align="center">
                <!-- Sin Marcar -->
                <q-chip
                  v-if="props.row.sin_marcar"
                  color="negative" text-color="white" dense icon="highlight_off" label="Sin Marcar" size="sm" class="text-weight-bold"
                />

                <!-- Marcada Completa -->
                <q-chip
                  v-else-if="props.row.modo_registro === 'en_fecha'"
                  color="positive" text-color="white" dense icon="check_circle" label="Marcado En Fecha" size="sm" class="text-weight-bold"
                />

                <!-- Marcada Retroactiva / Fecha Posterior -->
                <q-chip
                  v-else-if="props.row.modo_registro === 'retroactivo'"
                  color="purple-7" text-color="white" dense icon="history" label="Marcado Posterior" size="sm" class="text-weight-bold"
                />

                <!-- No Marcada Justificada / Omitida -->
                <q-chip
                  v-else-if="props.row.omitida"
                  color="blue-grey-7" text-color="white" dense icon="do_not_disturb_on" label="No Marcado (Omitido)" size="sm" class="text-weight-bold"
                />

                <!-- Parcialmente Marcado -->
                <q-chip
                  v-else-if="props.row.parcialmente_marcado"
                  color="warning" text-color="dark" dense icon="timelapse" :label="`${props.row.marcados}/${props.row.total_estudiantes} marcados`" size="sm" class="text-weight-bold"
                />
              </q-td>
            </template>

            <template #body-cell-fecha_registro="props">
              <q-td :props="props">
                <div v-if="props.row.fecha_registro" class="text-caption text-indigo-3 font-mono">
                  ⏱️ {{ props.row.fecha_registro }}
                </div>
                <div v-else class="text-caption text-grey-6">
                  Sin registro
                </div>
              </q-td>
            </template>

            <template #body-cell-observacion="props">
              <q-td :props="props">
                <div v-if="props.row.omitida" class="text-caption text-amber-3 text-weight-medium">
                  🛑 Motivo docente: {{ props.row.observacion }}
                </div>
                <div v-else-if="props.row.modo_registro === 'retroactivo' && props.row.observacion" class="text-caption text-purple-3">
                  ⚠️ Justificación: {{ props.row.observacion }}
                </div>
                <div v-else-if="props.row.modo_registro === 'en_fecha'" class="text-caption text-positive">
                  ✓ Registro en tiempo real
                </div>
                <div v-else class="text-caption text-grey-5">
                  Sin observación
                </div>
              </q-td>
            </template>
          </q-table>
        </q-card-section>
      </q-card>
    </template>
  </q-page>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar, date } from 'quasar'

const $q = useQuasar()
const loading = ref(true)
const hoyStr = date.formatDate(new Date(), 'YYYY-MM-DD')

const fechaInicio = ref(hoyStr)
const fechaFin = ref(hoyStr)
const filtroEstado = ref('sin_marcar')

const sinMarcarList = ref([])
const marcadasList = ref([])
const parcialmenteList = ref([])
const omitidasList = ref([])
const todasLasClases = ref([])
const totalClases = ref(0)

const columns = [
  { name: 'fecha', label: 'Fecha / Día', align: 'left', field: 'fecha', sortable: true },
  { name: 'docente', label: 'Docente', align: 'left', field: 'docente_nombre', sortable: true },
  { name: 'materia', label: 'Materia / Carrera', align: 'left', field: 'materia_nombre', sortable: true },
  { name: 'horario', label: 'Horario / Aula', align: 'left', field: 'hora_inicio' },
  { name: 'estado', label: 'Estado Registro', align: 'center', field: 'modo_registro' },
  { name: 'fecha_registro', label: 'Fecha / Hora Marcado', align: 'left', field: 'fecha_registro', sortable: true },
  { name: 'observacion', label: 'Observaciones / Motivo Docente', align: 'left', field: 'observacion' },
]

const listaFiltrada = computed(() => {
  if (filtroEstado.value === 'sin_marcar') return sinMarcarList.value
  if (filtroEstado.value === 'marcadas') return marcadasList.value
  if (filtroEstado.value === 'parcialmente') return parcialmenteList.value
  if (filtroEstado.value === 'omitidas') return omitidasList.value
  return todasLasClases.value
})

function seleccionarHistorico() {
  fechaInicio.value = '2026-08-03'
  fechaFin.value = hoyStr
  cargarReporte()
}

async function cargarReporte() {
  loading.value = true
  try {
    const res = await api.get('/admin/reportes/docentes-sin-marcar', {
      params: {
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value,
      }
    })
    sinMarcarList.value = res.data.sin_marcar || []
    marcadasList.value = res.data.marcadas || []
    parcialmenteList.value = res.data.parcialmente || []
    omitidasList.value = res.data.omitidas || []
    todasLasClases.value = res.data.todas || []
    totalClases.value = res.data.total_clases || 0
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al obtener reporte de docentes.' })
  } finally {
    loading.value = false
  }
}

function getEstadoLabel(item) {
  if (item.sin_marcar) return "SIN REGISTRO (0%)"
  if (item.modo_registro === 'en_fecha') return "MARCADO EN FECHA"
  if (item.modo_registro === 'retroactivo') return "MARCADO POSTERIOR (RETROACTIVO)"
  if (item.omitida) return "NO MARCADO (OMITIDO/JUSTIFICADO)"
  if (item.parcialmente_marcado) return `INCOMPLETO (${item.marcados}/${item.total_estudiantes})`
  return "MARCADO"
}

function exportarExcel() {
  if (listaFiltrada.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar.' })
    return
  }

  let csvContent = "\uFEFF"; // UTF-8 BOM
  csvContent += `REPORTE CONTROL ASISTENCIA DOCENTE - RANGO: ${fechaInicio.value} AL ${fechaFin.value}\n\n`;
  csvContent += "FECHA PROGRAMADA,DIA,CI DOCENTE,DOCENTE,CODIGO MATERIA,MATERIA,CARRERA,HORARIO,AULA,ESTADO REGISTRO,FECHA/HORA REAL MARCADO,OBSERVACION / MOTIVO DOCENTE\n";

  listaFiltrada.value.forEach(item => {
    const estadoStr = getEstadoLabel(item)
    csvContent += `"${item.fecha}","${item.dia_semana}","${item.docente_ci}","${item.docente_nombre}","${item.materia_codigo}","${item.materia_nombre}","${item.carrera}","${item.hora_inicio} - ${item.hora_fin}","${item.aula || ''}","${estadoStr}","${item.fecha_registro || 'Sin Registro'}","${item.observacion || ''}"\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  link.setAttribute("href", url);
  link.setAttribute("download", `Reporte_Asistencia_Docentes_UNITEPC_${fechaInicio.value}_al_${fechaFin.value}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  $q.notify({ type: 'positive', message: '✅ Reporte Excel generado correctamente.' });
}

function exportarPDF() {
  if (listaFiltrada.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar a PDF.' })
    return
  }

  const printWindow = window.open('', '_blank');
  const fechaHoy = new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });

  let rowsHtml = '';
  listaFiltrada.value.forEach(item => {
    const estadoStr = getEstadoLabel(item)
    const badgeClass = item.sin_marcar ? 'badge-danger' : item.modo_registro === 'en_fecha' ? 'badge-success' : item.modo_registro === 'retroactivo' ? 'badge-purple' : item.omitida ? 'badge-grey' : 'badge-warning';

    rowsHtml += `
      <tr>
        <td><strong>${item.fecha}</strong><br><small style="color:#64748b">${item.dia_semana}</small></td>
        <td><strong>${item.docente_nombre}</strong><br><small style="color:#64748b">CI: ${item.docente_ci}</small></td>
        <td><strong>${item.materia_codigo}</strong> - ${item.materia_nombre}<br><small style="color:#64748b">${item.carrera}</small></td>
        <td>${item.hora_inicio} - ${item.hora_fin}<br><small style="color:#64748b">Aula: ${item.aula || 'N/A'}</small></td>
        <td style="text-align: center;"><span class="${badgeClass}">${estadoStr}</span></td>
        <td><small style="font-family: monospace;">${item.fecha_registro || 'Sin Registro'}</small></td>
        <td><small style="color: #475569;">${item.observacion || 'Sin observación'}</small></td>
      </tr>
    `;
  });

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reporte Control Asistencia Docente - UNITEPC</title>
      <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 25px; color: #1e293b; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
        .header img { height: 50px; }
        .title { font-size: 20px; font-weight: bold; color: #1e3a8a; }
        .subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }
        .meta-info { margin-bottom: 20px; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        th { background: #1e3a8a; color: white; text-align: left; padding: 8px; font-weight: 600; }
        td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge-danger { background: #fee2e2; color: #991b1b; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-success { background: #dcfce7; color: #166534; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-grey { background: #e2e8f0; color: #334155; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-warning { background: #fef3c7; color: #92400e; padding: 3px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <div class="title">UNITEPC - UNIVERSIDAD PRIVADA</div>
          <div class="subtitle">Reporte Control Asistencia Docente y Seguimiento de Clases</div>
        </div>
        <img src="/logo-unitepc.png" alt="UNITEPC Logo">
      </div>

      <div class="meta-info">
        <strong>Fecha de emisión:</strong> ${fechaHoy} &nbsp;|&nbsp; 
        <strong>Rango de consulta:</strong> Desde ${fechaInicio.value} Hasta ${fechaFin.value} &nbsp;|&nbsp; 
        <strong>Total clases mostradas:</strong> ${listaFiltrada.value.length} registro(s)
      </div>

      <table>
        <thead>
          <tr>
            <th>Fecha Prog.</th>
            <th>Docente</th>
            <th>Materia / Carrera</th>
            <th>Horario / Aula</th>
            <th style="text-align: center;">Estado Registro</th>
            <th>Fecha Marcado</th>
            <th>Observación / Motivo Docente</th>
          </tr>
        </thead>
        <tbody>
          ${rowsHtml}
        </tbody>
      </table>

      <div class="footer">
        Sistema Control de Asistencia Universitaria UNITEPC · Documento Generado Automáticamente
      </div>

      <script>window.onload = function() { window.print(); }<\/script>
    </body>
    </html>
  `;

  printWindow.document.write(html);
  printWindow.document.close();
}

onMounted(cargarReporte)
</script>

<style lang="scss" scoped>
.page-title { font-size: 1.75rem; font-weight: 800; color: var(--color-text); margin: 0 0 4px; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.875rem; margin: 0; }

.stat-mini-card {
  padding: 14px 16px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: rgba(30, 41, 59, 0.7);

  .stat-num { font-size: 1.6rem; font-weight: 800; line-height: 1; margin-bottom: 2px; }
  .stat-text { font-size: 0.75rem; color: var(--color-text-muted); }

  &.danger { border-color: rgba(239, 68, 68, 0.3); .stat-num { color: #f87171; } }
  &.success { border-color: rgba(34, 197, 94, 0.3); .stat-num { color: #4ade80; } }
  &.warning { border-color: rgba(245, 158, 11, 0.3); .stat-num { color: #fbbf24; } }
  &.info { border-color: rgba(148, 163, 184, 0.3); .stat-num { color: #cbd5e1; } }
  &.total { border-color: rgba(59, 130, 246, 0.3); .stat-num { color: #60a5fa; } }
}

.action-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
}

.glass-btn {
  background: rgba(59, 130, 246, 0.1) !important;
  border: 1px solid rgba(59, 130, 246, 0.2) !important;
  border-radius: 8px !important;
}
</style>
