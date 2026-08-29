<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 q-mb-xl fade-in-up">
      <div>
        <h2 class="page-title">Estudiantes con excesos de faltas</h2>
        <p class="page-subtitle">Listado de estudiantes con inasistencias acumuladas (≥ {{ limiteFaltas }} faltas)</p>
      </div>

      <!-- Filtros y Botones de Exportación -->
      <div class="flex items-center gap-3 flex-wrap">
        <q-select
          v-model="limiteFaltas"
          :options="[
            { label: '≥ 1 falta', value: 1 },
            { label: '≥ 2 faltas', value: 2 },
            { label: '≥ 3 faltas', value: 3 },
            { label: '≥ 4 faltas', value: 4 },
            { label: '≥ 5 faltas', value: 5 }
          ]"
          emit-value map-options
          label="Mínimo de faltas"
          outlined dark dense
          style="min-width: 150px;"
          @update:model-value="cargarEstudiantes"
        />
        <q-select
          v-model="materiaSeleccionada"
          :options="opcionesMaterias"
          option-value="id"
          option-label="nombre"
          label="Filtrar por Materia"
          clearable
          outlined dark dense
          style="min-width: 220px;"
          @update:model-value="cargarEstudiantes"
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
          @click="cargarEstudiantes" class="glass-btn"
        />
      </div>
    </div>

    <!-- Alert banner -->
    <q-banner class="alert-banner q-mb-lg" rounded>
      <template #avatar>
        <q-icon name="warning" color="warning" size="24px" />
      </template>
      <div class="text-weight-bold" style="color: #fcd34d;">Alerta de Asistencia</div>
      <div style="color: #fde68a; font-size: 0.85rem;">
        Se registran <strong class="text-white">{{ estudiantes.length }}</strong> estudiante(s) con {{ limiteFaltas }} o más ausencias acumuladas.
      </div>
    </q-banner>

    <!-- Table -->
    <q-card class="glass-card" flat>
      <q-card-section class="q-pa-none">
        <q-table
          :rows="estudiantes"
          :columns="columns"
          row-key="estudiante_carnet"
          :loading="loading"
          flat dark
          :pagination="{ rowsPerPage: 15 }"
          no-data-label="No se encontraron estudiantes con faltas excesivas."
        >
          <template #body-cell-estudiante="props">
            <q-td :props="props">
              <div class="text-weight-bold text-white">{{ props.row.estudiante_nombre }}</div>
              <div class="text-caption text-grey-5">Carnet/CI: {{ props.row.estudiante_carnet }}</div>
            </q-td>
          </template>

          <template #body-cell-materia="props">
            <q-td :props="props">
              <div class="text-weight-semibold text-blue-3">{{ props.row.materia_codigo }} - {{ props.row.materia_nombre }}</div>
              <div class="text-caption text-grey-5">{{ props.row.carrera }}</div>
            </q-td>
          </template>

          <template #body-cell-faltas="props">
            <q-td :props="props">
              <q-chip
                :color="props.row.total_faltas >= 4 ? 'negative' : 'warning'"
                text-color="dark"
                class="text-weight-bold"
                icon="cancel"
                :label="`${props.row.total_faltas} faltas`"
              />
            </q-td>
          </template>

          <template #body-cell-acciones="props">
            <q-td :props="props" align="center">
              <q-btn
                flat round dense icon="visibility" color="blue-4"
                @click="verDetalleEstudiante(props.row)"
              >
                <q-tooltip>Ver detalle de inasistencias</q-tooltip>
              </q-btn>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Modal Detalle de Fechas de Faltas -->
    <q-dialog v-model="modalDetalleOpen" persistent>
      <q-card style="width: 580px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="event_busy" color="negative" size="28px" />
            <div>
              <div class="text-h6 text-weight-bold text-white">Detalle de Inasistencias</div>
              <div class="text-caption text-grey-4" v-if="estudianteSeleccionado">
                {{ estudianteSeleccionado.estudiante_nombre }} (CI: {{ estudianteSeleccionado.estudiante_carnet }})
              </div>
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none" v-if="estudianteSeleccionado">
          <div class="info-box q-mb-md">
            <div class="text-weight-bold text-blue-3">{{ estudianteSeleccionado.materia_codigo }} - {{ estudianteSeleccionado.materia_nombre }}</div>
            <div class="text-caption text-grey-3">Carrera: {{ estudianteSeleccionado.carrera }} · Total inasistencias: <strong class="text-amber-4">{{ estudianteSeleccionado.total_faltas }}</strong></div>
          </div>

          <div class="text-subtitle2 text-grey-3 q-mb-xs">Fechas en las que se registraron faltas:</div>

          <q-table
            :rows="estudianteSeleccionado.fechas_faltas || []"
            :columns="columnsDetalle"
            row-key="fecha"
            flat dark dense
            no-data-label="No hay detalle de fechas disponible"
          >
            <template #body-cell-fecha="props">
              <q-td :props="props">
                <q-badge color="red-9" class="text-weight-bold q-px-sm q-py-xs">
                  📅 {{ props.row.fecha }}
                </q-badge>
              </q-td>
            </template>

            <template #body-cell-docente="props">
              <q-td :props="props">
                <div class="text-grey-3">{{ props.row.docente_nombre }}</div>
              </q-td>
            </template>

            <template #body-cell-justificacion="props">
              <q-td :props="props">
                <div v-if="props.row.justificacion" class="text-caption text-amber-3">
                  ⚠️ {{ props.row.justificacion }}
                </div>
                <div v-else class="text-caption text-grey-5">
                  Sin observación
                </div>
              </q-td>
            </template>
          </q-table>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn color="primary" label="Cerrar" v-close-popup unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const loading = ref(true)
const estudiantes = ref([])
const limiteFaltas = ref(2)
const materiaSeleccionada = ref(null)
const opcionesMaterias = ref([])

const modalDetalleOpen = ref(false)
const estudianteSeleccionado = ref(null)

const columns = [
  { name: 'estudiante', label: 'Estudiante', align: 'left', field: 'estudiante_nombre' },
  { name: 'materia', label: 'Materia / Carrera', align: 'left', field: 'materia_nombre' },
  { name: 'faltas', label: 'Total Ausencias', align: 'center', field: 'total_faltas', sortable: true },
  { name: 'acciones', label: 'Acciones', align: 'center' },
]

const columnsDetalle = [
  { name: 'fecha', label: 'Fecha de Falta', align: 'left', field: 'fecha' },
  { name: 'docente', label: 'Docente Registrador', align: 'left', field: 'docente_nombre' },
  { name: 'justificacion', label: 'Observación / Nota', align: 'left', field: 'justificacion' },
]

async function cargarMaterias() {
  try {
    const res = await api.get('/admin/materias')
    opcionesMaterias.value = res.data
  } catch (e) {}
}

async function cargarEstudiantes() {
  loading.value = true
  try {
    const res = await api.get('/admin/reportes/estudiantes-faltas', {
      params: {
        limite: limiteFaltas.value,
        materia_id: materiaSeleccionada.value?.id || null
      }
    })
    estudiantes.value = res.data.estudiantes
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar reporte de estudiantes con faltas.' })
  } finally {
    loading.value = false
  }
}

function verDetalleEstudiante(row) {
  estudianteSeleccionado.value = row
  modalDetalleOpen.value = true
}

function exportarExcel() {
  if (estudiantes.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar.' })
    return
  }

  let csvContent = "\uFEFF"; // UTF-8 BOM
  csvContent += "CARNET/CI,ESTUDIANTE,CARRERA,CODIGO MATERIA,MATERIA,TOTAL FALTAS,FECHAS DE INASISTENCIA\n";

  estudiantes.value.forEach(e => {
    const fechasStr = (e.fechas_faltas || []).map(f => f.fecha).join(" | ");
    csvContent += `"${e.estudiante_carnet}","${e.estudiante_nombre}","${e.carrera}","${e.materia_codigo}","${e.materia_nombre}","${e.total_faltas}","${fechasStr}"\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  link.setAttribute("href", url);
  link.setAttribute("download", `Reporte_Estudiantes_Faltas_UNITEPC_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  $q.notify({ type: 'positive', message: '✅ Reporte Excel generado correctamente.' });
}

function exportarPDF() {
  if (estudiantes.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar a PDF.' })
    return
  }

  const printWindow = window.open('', '_blank');
  const materiaFiltro = materiaSeleccionada.value ? materiaSeleccionada.value.nombre : 'Todas las materias';
  const fechaHoy = new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });

  let rowsHtml = '';
  estudiantes.value.forEach(e => {
    const fechasStr = (e.fechas_faltas || []).map(f => `<span class="badge-date">${f.fecha}</span>`).join(' ');
    rowsHtml += `
      <tr>
        <td><strong>${e.estudiante_carnet}</strong></td>
        <td>${e.estudiante_nombre}</td>
        <td>${e.carrera}</td>
        <td><strong>${e.materia_codigo}</strong> - ${e.materia_nombre}</td>
        <td style="text-align: center; font-weight: bold; color: #dc2626;">${e.total_faltas}</td>
        <td>${fechasStr || 'Sin fechas'}</td>
      </tr>
    `;
  });

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reporte de Estudiantes con Faltas - UNITEPC</title>
      <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 25px; color: #1e293b; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
        .header img { height: 50px; }
        .title { font-size: 20px; font-weight: bold; color: #1e3a8a; }
        .subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }
        .meta-info { margin-bottom: 20px; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th { background: #1e3a8a; color: white; text-align: left; padding: 10px; font-weight: 600; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge-date { background: #fee2e2; color: #991b1b; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; margin: 2px; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <div class="title">UNITEPC - UNIVERSIDAD PRIVADA</div>
          <div class="subtitle">Reporte Institucional de Estudiantes con Exceso de Faltas</div>
        </div>
        <img src="/logo-unitepc.png" alt="UNITEPC Logo">
      </div>

      <div class="meta-info">
        <strong>Fecha de emisión:</strong> ${fechaHoy} &nbsp;|&nbsp; 
        <strong>Criterio:</strong> ≥ ${limiteFaltas.value} inasistencias &nbsp;|&nbsp; 
        <strong>Filtro Materia:</strong> ${materiaFiltro} &nbsp;|&nbsp; 
        <strong>Total registrados:</strong> ${estudiantes.value.length} estudiante(s)
      </div>

      <table>
        <thead>
          <tr>
            <th>Carnet / CI</th>
            <th>Estudiante</th>
            <th>Carrera</th>
            <th>Materia</th>
            <th style="text-align: center;">Faltas</th>
            <th>Fechas de Inasistencia</th>
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

onMounted(() => {
  cargarMaterias()
  cargarEstudiantes()
})
</script>

<style lang="scss" scoped>
.page-title { font-size: 1.75rem; font-weight: 800; color: var(--color-text); margin: 0 0 4px; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.875rem; margin: 0; }

.alert-banner {
  background: rgba(245, 158, 11, 0.08) !important;
  border: 1px solid rgba(245, 158, 11, 0.2) !important;
  border-radius: 12px !important;
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
