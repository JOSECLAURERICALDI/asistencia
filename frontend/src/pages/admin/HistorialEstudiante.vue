<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 q-mb-xl fade-in-up">
      <div>
        <h2 class="page-title">Historial de Asistencia por Estudiante</h2>
        <p class="page-subtitle">Consultar registros, asistencias, faltas, notificaciones y fechas no marcadas por docente</p>
      </div>

      <!-- Filtros -->
      <div class="flex items-center gap-3 flex-wrap">
        <q-input
          v-model="buscarQuery"
          label="Buscar por Carnet o Nombre"
          outlined dark dense
          style="min-width: 240px;"
          @keyup.enter="cargarHistorial"
        >
          <template #append>
            <q-icon name="search" class="cursor-pointer" @click="cargarHistorial" />
          </template>
        </q-input>

        <div class="flex items-center gap-2">
          <q-input
            v-model="fechaInicio"
            type="date"
            label="Desde"
            outlined dark dense
            style="width: 140px;"
            @update:model-value="cargarHistorial"
          />
          <q-input
            v-model="fechaFin"
            type="date"
            label="Hasta"
            outlined dark dense
            style="width: 140px;"
            @update:model-value="cargarHistorial"
          />
        </div>

        <q-select
          v-if="materiasEstudiante.length > 0"
          v-model="materiaSeleccionada"
          :options="materiasEstudiante"
          option-value="id"
          :option-label="m => `${m.codigo} - ${m.nombre}`"
          emit-value map-options
          clearable
          label="Filtrar por Materia"
          outlined dark dense
          style="min-width: 220px;"
          @update:model-value="cargarHistorial"
        />

        <q-btn
          no-caps icon="table_chart" color="positive" label="Exportar Excel"
          @click="exportarExcel" class="action-btn" unelevated :disable="!estudianteData"
        />
        
        <q-btn
          no-caps icon="picture_as_pdf" color="red-7" label="Exportar PDF"
          @click="exportarPDF" class="action-btn" unelevated :disable="!estudianteData"
        />

        <q-btn
          flat no-caps icon="refresh" color="blue-4" label="Buscar"
          @click="cargarHistorial" class="glass-btn"
        />
      </div>
    </div>

    <!-- Indicador de Carga -->
    <div v-if="loading" class="flex justify-center q-py-xl">
      <q-spinner-grid size="48px" color="blue-4" />
    </div>

    <template v-else>
      <!-- Estado Inicial / No Encontrado -->
      <div v-if="!estudianteData" class="empty-card glass-card q-pa-xl text-center">
        <q-icon name="person_search" size="56px" color="blue-4" />
        <h3 class="text-white text-weight-bold q-mt-md q-mb-xs">Buscar Estudiante</h3>
        <p style="color: var(--color-text-muted);">Ingresa el Carnet de Identidad o Nombre completo para consultar el historial de clases.</p>
      </div>

      <template v-else>
        <!-- Tarjeta de Perfil Estudiante y Estadísticas -->
        <q-card class="glass-card q-mb-lg q-pa-md" flat>
          <div class="row q-col-gutter-md items-center">
            <div class="col-12 col-md-4 flex items-center gap-3">
              <q-avatar size="54px" color="blue-7" text-color="white" class="text-weight-bold">
                {{ estudianteData.nombre_completo.charAt(0) }}
              </q-avatar>
              <div>
                <div class="text-h6 text-weight-bold text-white">{{ estudianteData.nombre_completo }}</div>
                <div class="text-caption text-indigo-3 font-mono">Carnet / CI: {{ estudianteData.carnet }}</div>
                <div class="text-caption text-grey-4">Carrera: {{ estudianteData.carrera }}</div>
                <div v-if="estudianteData.contacto_telefono" class="text-caption text-positive font-weight-medium flex items-center gap-1 q-mt-xs">
                  <q-icon name="phone" size="14px" />
                  <span>Contacto: <strong>{{ estudianteData.contacto_nombre }}</strong><span v-if="estudianteData.contacto_parentesco"> ({{ estudianteData.contacto_parentesco }})</span>:</span>
                  <a :href="`tel:${estudianteData.contacto_telefono}`" class="text-blue-3 text-weight-bold text-decoration-none">{{ estudianteData.contacto_telefono }}</a>
                </div>
              </div>
            </div>

            <div class="col-12 col-md-8">
              <div class="row q-col-gutter-xs">
                <div class="col-6 col-sm-2-4">
                  <div class="stat-mini-card total text-center">
                    <div class="stat-num">{{ estadisticas.total_clases }}</div>
                    <div class="stat-text">Clases Evaluadas</div>
                  </div>
                </div>
                <div class="col-6 col-sm-2-4">
                  <div class="stat-mini-card success text-center">
                    <div class="stat-num">{{ estadisticas.presentes }}</div>
                    <div class="stat-text">Presentes</div>
                  </div>
                </div>
                <div class="col-6 col-sm-2-4">
                  <div class="stat-mini-card danger text-center">
                    <div class="stat-num">{{ estadisticas.ausentes }}</div>
                    <div class="stat-text">Faltas</div>
                  </div>
                </div>
                <div class="col-6 col-sm-2-4">
                  <div class="stat-mini-card info text-center">
                    <div class="stat-num">{{ estadisticas.notificados || 0 }}</div>
                    <div class="stat-text">Notificados</div>
                  </div>
                </div>
                <div class="col-6 col-sm-2-4">
                  <div class="stat-mini-card warning text-center">
                    <div class="stat-num">{{ estadisticas.porcentaje }}%</div>
                    <div class="stat-text">% Asistencia</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </q-card>

        <!-- Tabla Historial -->
        <q-card class="glass-card" flat>
          <q-card-section class="flex items-center justify-between q-pb-none">
            <div class="text-h6 text-weight-bold text-white">Historial de Clases y Asistencias</div>
            <div class="text-caption text-grey-4">Del {{ fechaInicio }} al {{ fechaFin }}</div>
          </q-card-section>

          <q-card-section class="q-pa-none q-mt-md">
            <q-table
              :rows="asistencias"
              :columns="columns"
              row-key="id"
              flat dark
              :pagination="{ rowsPerPage: 15 }"
              no-data-label="No hay registros de asistencia en el rango seleccionado"
            >
              <template #body-cell-fecha="props">
                <q-td :props="props">
                  <div class="text-weight-bold text-blue-3">📅 {{ props.row.fecha }}</div>
                  <div class="text-caption text-grey-4" v-if="props.row.hora_inicio">Hora: {{ props.row.hora_inicio }}</div>
                </q-td>
              </template>

              <template #body-cell-materia="props">
                <q-td :props="props">
                  <div class="text-weight-semibold text-white">{{ props.row.materia_codigo }} - {{ props.row.materia_nombre }}</div>
                  <div class="text-caption text-grey-4" v-if="props.row.aula">Aula: {{ props.row.aula }}</div>
                </q-td>
              </template>

              <template #body-cell-estado="props">
                <q-td :props="props" align="center">
                  <!-- Ausente Notificado -->
                  <q-chip
                    v-if="props.row.estado === 'ausente' && props.row.es_notificada"
                    dense
                    color="purple-7"
                    text-color="white"
                    class="text-weight-bold"
                    icon="mark_email_read"
                  >
                    Notificado
                  </q-chip>
                  <!-- Ausente Falta Activa -->
                  <q-chip
                    v-else-if="props.row.estado === 'ausente'"
                    dense
                    color="negative"
                    text-color="white"
                    class="text-weight-bold"
                  >
                    Ausente
                  </q-chip>
                  <!-- No Marcada -->
                  <q-chip
                    v-else-if="props.row.estado === 'omitida'"
                    dense
                    color="blue-grey-7"
                    text-color="white"
                    class="text-weight-bold"
                  >
                    No Marcada
                  </q-chip>
                  <!-- Presente / Permiso -->
                  <q-chip
                    v-else
                    dense
                    :color="props.row.estado === 'presente' ? 'positive' : 'warning'"
                    text-color="white"
                    class="text-weight-bold text-capitalize"
                  >
                    {{ props.row.estado }}
                  </q-chip>
                </q-td>
              </template>

              <template #body-cell-docente="props">
                <q-td :props="props">
                  <div class="text-grey-3 font-weight-medium">{{ props.row.docente_nombre }}</div>
                </q-td>
              </template>

              <template #body-cell-justificacion="props">
                <q-td :props="props">
                  <div v-if="props.row.es_notificada" class="text-caption text-purple-3 text-weight-medium">
                    📞 Notificado el {{ props.row.fecha_notificacion }}
                  </div>
                  <div v-else-if="props.row.estado === 'omitida'" class="text-caption text-amber-3 text-weight-medium">
                    🛑 {{ props.row.justificacion }}
                  </div>
                  <div v-else-if="props.row.justificacion" class="text-caption text-amber-3">
                    ⚠️ {{ props.row.justificacion }}
                  </div>
                  <div v-else-if="props.row.es_retroactiva" class="text-caption text-blue-3">
                    ℹ️ Marcado retroactivo
                  </div>
                  <div v-else class="text-caption text-grey-5">
                    Sin observación
                  </div>
                </q-td>
              </template>

              <template #body-cell-acciones="props">
                <q-td :props="props" align="center">
                  <q-btn
                    flat round dense icon="visibility" color="blue-4"
                    @click="verDetalleItem(props.row)"
                  >
                    <q-tooltip>Ver detalle completo de fecha, llamada y observaciones</q-tooltip>
                  </q-btn>
                </q-td>
              </template>
            </q-table>
          </q-card-section>
        </q-card>

        <!-- Modal Emergente Ojito (Detalle Completo) -->
        <q-dialog v-model="modalDetalleItemOpen" persistent>
          <q-card style="width: 550px; max-width: 95vw;" class="dark-card" v-if="itemSeleccionado">
            <q-card-section class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <q-icon name="info" color="blue-4" size="26px" />
                <div class="text-h6 text-weight-bold text-white">Detalle de Registro y Notificación</div>
              </div>
              <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
            </q-card-section>

            <q-card-section class="q-pt-none">
              <!-- Datos de Estudiante -->
              <div class="info-box q-mb-md">
                <div class="text-subtitle2 text-weight-bold text-white">{{ estudianteData.nombre_completo }}</div>
                <div class="text-caption text-indigo-3">CI: {{ estudianteData.carnet }} · Carrera: {{ estudianteData.carrera }}</div>
                <div v-if="estudianteData.contacto_telefono" class="text-caption text-positive font-weight-medium q-mt-xs">
                  📞 Referencia Familiar: <strong>{{ estudianteData.contacto_nombre }}</strong><span v-if="estudianteData.contacto_parentesco"> ({{ estudianteData.contacto_parentesco }})</span>:
                  <a :href="`tel:${estudianteData.contacto_telefono}`" class="text-blue-3 text-weight-bold text-decoration-none q-ml-xs">{{ estudianteData.contacto_telefono }}</a>
                </div>
              </div>

              <!-- Datos de Clase -->
              <div class="q-gutter-y-sm">
                <div class="flex items-center justify-between">
                  <span class="text-grey-4">Materia:</span>
                  <span class="text-weight-bold text-blue-3">{{ itemSeleccionado.materia_codigo }} - {{ itemSeleccionado.materia_nombre }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-grey-4">Fecha de Clase:</span>
                  <span class="text-white text-weight-medium">📅 {{ itemSeleccionado.fecha }} ({{ itemSeleccionado.hora_inicio || 'Horario programado' }})</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-grey-4">Docente Registrador:</span>
                  <span class="text-white">{{ itemSeleccionado.docente_nombre }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-grey-4">Estado de Asistencia:</span>
                  <q-chip
                    v-if="itemSeleccionado.es_notificada"
                    color="purple-7" text-color="white" dense icon="mark_email_read" label="Ausente (Notificado)" size="sm" class="text-weight-bold"
                  />
                  <q-chip
                    v-else-if="itemSeleccionado.estado === 'ausente'"
                    color="negative" text-color="white" dense icon="cancel" label="Ausente (Falta Activa)" size="sm" class="text-weight-bold"
                  />
                  <q-chip
                    v-else-if="itemSeleccionado.estado === 'presente'"
                    color="positive" text-color="white" dense icon="check_circle" label="Presente" size="sm" class="text-weight-bold"
                  />
                  <q-chip
                    v-else
                    color="blue-grey-7" text-color="white" dense icon="info" :label="itemSeleccionado.estado" size="sm" class="text-weight-bold text-capitalize"
                  />
                </div>
              </div>

              <q-separator dark class="q-my-md" />

              <!-- Sección Notificación (Si aplica) -->
              <div v-if="itemSeleccionado.es_notificada" class="notif-detail-box q-pa-md q-mb-sm">
                <div class="text-subtitle2 text-weight-bold text-purple-3 flex items-center gap-1 q-mb-xs">
                  <q-icon name="phone_in_talk" size="18px" /> Detalles de la Notificación / Llamada realizada:
                </div>
                <div class="text-caption text-grey-3 q-mb-xs">
                  <strong>Fecha y Hora de Llamada:</strong> {{ itemSeleccionado.fecha_notificacion }}
                </div>
                <div class="text-caption text-grey-3 q-mb-xs" v-if="itemSeleccionado.director_notificacion">
                  <strong>Registrado por:</strong> {{ itemSeleccionado.director_notificacion }}
                </div>
                <div class="text-caption text-grey-2 q-mt-sm">
                  <strong>Observaciones / Acta Redactada:</strong><br>
                  <span class="text-white italic font-mono bg-dark-purple q-pa-sm rounded-borders block q-mt-xs">
                    "{{ itemSeleccionado.observacion_notificacion }}"
                  </span>
                </div>
              </div>

              <!-- Justificación docente -->
              <div v-if="itemSeleccionado.justificacion && !itemSeleccionado.es_notificada" class="q-mt-sm">
                <div class="text-caption text-amber-3">
                  <strong>Nota / Observación Docente:</strong> {{ itemSeleccionado.justificacion }}
                </div>
              </div>
            </q-card-section>

            <q-card-actions align="right" class="q-px-md q-pb-md">
              <q-btn color="primary" label="Cerrar" v-close-popup unelevated />
            </q-card-actions>
          </q-card>
        </q-dialog>
      </template>
    </template>
  </q-page>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar, date } from 'quasar'

const $q = useQuasar()
const loading = ref(false)
const hoyStr = date.formatDate(new Date(), 'YYYY-MM-DD')

const buscarQuery = ref('MERIDA')
const fechaInicio = ref('2026-08-03')
const fechaFin = ref(hoyStr)
const materiaSeleccionada = ref(null)

const estudianteData = ref(null)
const estadisticas = ref({ total_clases: 0, presentes: 0, ausentes: 0, permisos: 0, omitidas: 0, notificados: 0, porcentaje: 0 })
const materiasEstudiante = ref([])
const asistencias = ref([])

const modalDetalleItemOpen = ref(false)
const itemSeleccionado = ref(null)

const columns = [
  { name: 'fecha', label: 'Fecha / Hora', align: 'left', field: 'fecha', sortable: true },
  { name: 'materia', label: 'Materia', align: 'left', field: 'materia_nombre', sortable: true },
  { name: 'estado', label: 'Estado', align: 'center', field: 'estado' },
  { name: 'docente', label: 'Docente Registrador', align: 'left', field: 'docente_nombre' },
  { name: 'justificacion', label: 'Observación / Motivo Docente', align: 'left', field: 'justificacion' },
  { name: 'acciones', label: 'Detalles', align: 'center' },
]

async function cargarHistorial() {
  if (!buscarQuery.value) {
    $q.notify({ type: 'warning', message: 'Ingresa un carnet o nombre para buscar.' })
    return
  }
  loading.value = true
  try {
    const res = await api.get('/admin/reportes/historial-estudiante', {
      params: {
        buscar: buscarQuery.value,
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value,
        materia_id: materiaSeleccionada.value || null,
      }
    })
    estudianteData.value = res.data.estudiante
    estadisticas.value = res.data.estadisticas || { total_clases: 0, presentes: 0, ausentes: 0, permisos: 0, omitidas: 0, notificados: 0, porcentaje: 0 }
    materiasEstudiante.value = res.data.materias || []
    asistencias.value = res.data.asistencias || []

    if (!estudianteData.value) {
      $q.notify({ type: 'warning', message: 'No se encontró ningún estudiante con el término ingresado.' })
    }
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al consultar historial del estudiante.' })
  } finally {
    loading.value = false
  }
}

function verDetalleItem(item) {
  itemSeleccionado.value = item
  modalDetalleItemOpen.value = true
}

function exportarExcel() {
  if (!estudianteData.value || asistencias.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar.' })
    return
  }

  let csvContent = "\uFEFF"; // UTF-8 BOM
  csvContent += `HISTORIAL DE ASISTENCIA - ${estudianteData.value.nombre_completo} (CI: ${estudianteData.value.carnet})\n`;
  csvContent += `CARRERA: ${estudianteData.value.carrera}\n`;
  csvContent += `RANGO: ${fechaInicio.value} AL ${fechaFin.value}\n\n`;
  csvContent += "FECHA,MATERIA CODIGO,MATERIA NOMBRE,ESTADO,DOCENTE REGISTRADOR,FECHA NOTIFICACION,OBSERVACION / MOTIVO\n";

  asistencias.value.forEach(a => {
    const estadoTexto = a.es_notificada ? 'NOTIFICADO' : a.estado === 'omitida' ? 'NO MARCADA (JUSTIFICADA)' : a.estado.toUpperCase();
    const notifInfo = a.es_notificada ? `Notificado ${a.fecha_notificacion}: ${a.observacion_notificacion}` : '';
    csvContent += `"${a.fecha}","${a.materia_codigo}","${a.materia_nombre}","${estadoTexto}","${a.docente_nombre}","${a.fecha_notificacion || ''}","${a.justificacion || notifInfo}"\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  link.setAttribute("href", url);
  link.setAttribute("download", `Historial_Asistencia_${estudianteData.value.carnet}_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  $q.notify({ type: 'positive', message: '✅ Reporte Excel generado correctamente.' });
}

function exportarPDF() {
  if (!estudianteData.value || asistencias.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar a PDF.' })
    return
  }

  const printWindow = window.open('', '_blank');
  const fechaHoy = new Date().toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });

  let rowsHtml = '';
  asistencias.value.forEach(a => {
    const badgeClass = a.es_notificada ? 'badge-purple' : a.estado === 'presente' ? 'badge-success' : a.estado === 'permiso' ? 'badge-warning' : a.estado === 'omitida' ? 'badge-info' : 'badge-danger';
    const estadoTexto = a.es_notificada ? 'NOTIFICADO' : a.estado === 'omitida' ? 'NO MARCADA' : a.estado.toUpperCase();
    const observacionTexto = a.es_notificada ? `<span style="color:#7e22ce; font-weight:600;">📞 Notificado el ${a.fecha_notificacion} (${a.observacion_notificacion})</span>` : a.estado === 'omitida' ? `<span style="color:#d97706; font-weight:600;">🛑 Motivo docente: ${a.justificacion}</span>` : (a.justificacion || '<span style="color:#94a3b8">Sin observación</span>');

    rowsHtml += `
      <tr>
        <td><strong>${a.fecha}</strong></td>
        <td><strong>${a.materia_codigo}</strong> - ${a.materia_nombre}</td>
        <td style="text-align: center;"><span class="${badgeClass}">${estadoTexto}</span></td>
        <td>${a.docente_nombre}</td>
        <td>${observacionTexto}</td>
      </tr>
    `;
  });

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Historial de Asistencia - ${estudianteData.value.nombre_completo}</title>
      <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 25px; color: #1e293b; }
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #1e3a8a; padding-bottom: 15px; margin-bottom: 20px; }
        .header img { height: 50px; }
        .title { font-size: 20px; font-weight: bold; color: #1e3a8a; }
        .subtitle { font-size: 13px; color: #64748b; margin-top: 4px; }
        .student-card { background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .student-name { font-size: 16px; font-weight: bold; color: #0f172a; }
        .stats-grid { display: flex; gap: 10px; margin-bottom: 20px; }
        .stat-box { flex: 1; background: #f1f5f9; padding: 10px; border-radius: 6px; text-align: center; border: 1px solid #cbd5e1; }
        .stat-val { font-size: 18px; font-weight: bold; }
        .stat-lbl { font-size: 11px; color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
        th { background: #1e3a8a; color: white; text-align: left; padding: 10px; font-weight: 600; }
        td { padding: 10px; border-bottom: 1px solid #e2e8f0; }
        tr:nth-child(even) { background: #f8fafc; }
        .badge-success { background: #dcfce7; color: #166534; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-danger { background: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-warning { background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-info { background: #e2e8f0; color: #334155; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <div class="title">UNITEPC - UNIVERSIDAD PRIVADA</div>
          <div class="subtitle">Historial de Asistencia Individual de Estudiante</div>
        </div>
        <img src="/logo-unitepc.png" alt="UNITEPC Logo">
      </div>

      <div class="student-card">
        <div>
          <div class="student-name">${estudianteData.value.nombre_completo}</div>
          <div style="font-size: 13px; color: #475569;">Carnet/CI: <strong>${estudianteData.value.carnet}</strong> · Carrera: ${estudianteData.value.carrera}</div>
        </div>
        <div style="font-size: 12px; color: #64748b; text-align: right;">
          Consulta: ${fechaInicio.value} al ${fechaFin.value}<br>Emisión: ${fechaHoy}
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-val" style="color: #2563eb;">${estadisticas.value.total_clases}</div>
          <div class="stat-lbl">Clases Evaluadas</div>
        </div>
        <div class="stat-box">
          <div class="stat-val" style="color: #16a34a;">${estadisticas.value.presentes}</div>
          <div class="stat-lbl">Presentes</div>
        </div>
        <div class="stat-box">
          <div class="stat-val" style="color: #dc2626;">${estadisticas.value.ausentes}</div>
          <div class="stat-lbl">Faltas</div>
        </div>
        <div class="stat-box">
          <div class="stat-val" style="color: #7e22ce;">${estadisticas.value.notificados || 0}</div>
          <div class="stat-lbl">Notificados</div>
        </div>
        <div class="stat-box">
          <div class="stat-val" style="color: #d97706;">${estadisticas.value.porcentaje}%</div>
          <div class="stat-lbl">% Asistencia</div>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Materia</th>
            <th style="text-align: center;">Estado</th>
            <th>Docente Registrador</th>
            <th>Observación / Motivo</th>
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

onMounted(cargarHistorial)
</script>

<style lang="scss" scoped>
.page-title { font-size: 1.75rem; font-weight: 800; color: var(--color-text); margin: 0 0 4px; }
.page-subtitle { color: var(--color-text-muted); font-size: 0.875rem; margin: 0; }

.stat-mini-card {
  padding: 10px 12px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: rgba(30, 41, 59, 0.7);

  .stat-num { font-size: 1.4rem; font-weight: 800; line-height: 1; margin-bottom: 2px; }
  .stat-text { font-size: 0.7rem; color: var(--color-text-muted); }

  &.success { border-color: rgba(34, 197, 94, 0.3); .stat-num { color: #4ade80; } }
  &.danger { border-color: rgba(239, 68, 68, 0.3); .stat-num { color: #f87171; } }
  &.warning { border-color: rgba(245, 158, 11, 0.3); .stat-num { color: #fbbf24; } }
  &.total { border-color: rgba(59, 130, 246, 0.3); .stat-num { color: #60a5fa; } }
  &.info { border-color: rgba(147, 51, 234, 0.3); .stat-num { color: #c084fc; } }
}

.notif-detail-box {
  background: rgba(147, 51, 234, 0.1);
  border: 1px solid rgba(147, 51, 234, 0.3);
  border-radius: 12px;
}

.bg-dark-purple {
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid rgba(147, 51, 234, 0.2);
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
