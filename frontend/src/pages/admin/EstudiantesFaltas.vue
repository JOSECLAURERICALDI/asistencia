<template>
  <q-page class="page-bg q-pa-lg">
    <!-- Header -->
    <div class="flex items-center justify-between flex-wrap gap-3 q-mb-xl fade-in-up">
      <div>
        <h2 class="page-title">Estudiantes con Inasistencias y Acciones</h2>
        <p class="page-subtitle">Seguimiento de inasistencias activas (≥ {{ limiteFaltas }} faltas), registro de acciones tomadas y estado de abandono</p>
      </div>

      <!-- Filtros y Botones de Exportación -->
      <div class="flex items-center gap-3 flex-wrap">
        <q-select
          v-model="limiteFaltas"
          :options="[
            { label: '≥ 1 falta activa', value: 1 },
            { label: '≥ 2 faltas activas', value: 2 },
            { label: '≥ 3 faltas activas', value: 3 },
            { label: '≥ 4 faltas activas', value: 4 },
            { label: '≥ 5 faltas activas', value: 5 }
          ]"
          emit-value map-options
          label="Mínimo de faltas activas"
          outlined dark dense
          style="min-width: 170px;"
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
      <div class="text-weight-bold" style="color: #fcd34d;">Alerta de Asistencia por Falta Activa</div>
      <div style="color: #fde68a; font-size: 0.85rem;">
        Se registran <strong class="text-white">{{ estudiantes.length }}</strong> estudiante(s) con {{ limiteFaltas }} o más ausencias acumuladas **posteriores a su última notificación**.
      </div>
    </q-banner>

    <!-- Table -->
    <q-card class="glass-card" flat>
      <q-card-section class="q-pa-none">
        <q-table
          :rows="estudiantes"
          :columns="columns"
          row-key="inscripcion_id"
          :loading="loading"
          flat dark
          :pagination="{ rowsPerPage: 15 }"
          no-data-label="No se encontraron estudiantes con faltas activas acumuladas."
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
                :label="`${props.row.total_faltas} activa(s)`"
              />
              <div class="text-caption text-grey-5">Históricas: {{ props.row.total_faltas_historicas }}</div>
            </q-td>
          </template>

          <template #body-cell-estado="props">
            <q-td :props="props" align="center">
              <q-chip
                v-if="props.row.estado_inscripcion === 'abandono'"
                color="negative" text-color="white" dense icon="no_accounts" label="ABANDONO" size="sm" class="text-weight-bold"
              />
              <q-chip
                v-else-if="props.row.tiene_notificacion"
                color="purple-7" text-color="white" dense icon="mark_email_read" label="NOTIFICADO" size="sm" class="text-weight-bold"
              />
              <q-chip
                v-else
                color="amber-9" text-color="white" dense icon="schedule" label="PENDIENTE ACCIÓN" size="sm" class="text-weight-bold"
              />
            </q-td>
          </template>

          <template #body-cell-acciones="props">
            <q-td :props="props" align="center">
              <div class="flex items-center justify-center gap-1">
                <q-btn
                  flat round dense icon="visibility" color="blue-4"
                  @click="verDetalleEstudiante(props.row)"
                >
                  <q-tooltip>Ver detalle, notificar o tomar acción</q-tooltip>
                </q-btn>

                <q-btn
                  flat round dense icon="no_accounts" color="negative"
                  @click="abrirModalAbandono(props.row)"
                >
                  <q-tooltip>Gestionar estado de Abandono (Total/Parcial)</q-tooltip>
                </q-btn>
              </div>
            </q-td>
          </template>
        </q-table>
      </q-card-section>
    </q-card>

    <!-- Modal Detalle de Fechas de Faltas y Acción a Tomar -->
    <q-dialog v-model="modalDetalleOpen" persistent>
      <q-card style="width: 680px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="assignment_ind" color="blue-4" size="28px" />
            <div>
              <div class="text-h6 text-weight-bold text-white">Detalle de Inasistencias y Acciones</div>
              <div class="text-caption text-grey-4" v-if="estudianteSeleccionado">
                {{ estudianteSeleccionado.estudiante_nombre }} (CI: {{ estudianteSeleccionado.estudiante_carnet }})
              </div>
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none" v-if="estudianteSeleccionado">
          <!-- Info box de materia -->
          <div class="info-box q-mb-md flex items-center justify-between flex-wrap gap-2">
            <div>
              <div class="text-weight-bold text-blue-3">{{ estudianteSeleccionado.materia_codigo }} - {{ estudianteSeleccionado.materia_nombre }}</div>
              <div class="text-caption text-grey-3">Carrera: {{ estudianteSeleccionado.carrera }}</div>
            </div>
            <div>
              <q-chip color="negative" text-color="white" class="text-weight-bold" icon="error">
                Faltas activas: {{ estudianteSeleccionado.total_faltas }}
              </q-chip>
            </div>
          </div>

          <!-- Botón de Acción a Tomar (Notificación) -->
          <div class="accion-banner q-pa-md q-mb-md flex items-center justify-between flex-wrap gap-3">
            <div>
              <div class="text-weight-bold text-amber-3">📞 Registrar Acción Tomada (Llamada / Contacto)</div>
              <div class="text-caption text-grey-3">Registra la llamada realizada por la Dirección para cambiar el estado a Notificado y reiniciar el contador de faltas activas.</div>
            </div>
            <q-btn
              color="positive" no-caps icon="phone_in_talk" label="Acción a tomar"
              @click="abrirFormularioAccion" unelevated
            />
          </div>

          <!-- Historial de Acciones Tomadas -->
          <div v-if="estudianteSeleccionado.acciones_tomadas && estudianteSeleccionado.acciones_tomadas.length > 0" class="q-mb-md">
            <div class="text-subtitle2 text-purple-3 text-weight-bold flex items-center gap-1 q-mb-xs">
              <q-icon name="history" size="18px" /> Historial de Acciones Tomadas / Notificaciones ({{ estudianteSeleccionado.acciones_tomadas.length }}):
            </div>
            <q-list class="historial-list" separator dense>
              <q-item v-for="acc in estudianteSeleccionado.acciones_tomadas" :key="acc.id" class="q-py-xs">
                <q-item-section>
                  <q-item-label class="text-weight-bold text-white text-caption">
                    📅 {{ acc.fecha_accion }} por {{ acc.admin_nombre }}
                  </q-item-label>
                  <q-item-label caption class="text-grey-3">
                    💬 Observación: {{ acc.observacion }}
                  </q-item-label>
                </q-item-section>
              </q-item>
            </q-list>
          </div>

          <!-- Lista de fechas de faltas -->
          <div class="text-subtitle2 text-grey-3 q-mb-xs">Fechas en las que se registraron faltas:</div>

          <q-table
            :rows="estudianteSeleccionado.fechas_faltas || []"
            :columns="columnsDetalle"
            row-key="id"
            flat dark dense
            no-data-label="No hay detalle de fechas disponible"
          >
            <template #body-cell-fecha="props">
              <q-td :props="props">
                <q-badge :color="props.row.es_notificada ? 'blue-grey-7' : 'red-9'" class="text-weight-bold q-px-sm q-py-xs">
                  📅 {{ props.row.fecha }}
                </q-badge>
              </q-td>
            </template>

            <template #body-cell-estado_falta="props">
              <q-td :props="props" align="center">
                <q-chip v-if="props.row.es_notificada" color="blue-grey-8" text-color="grey-4" dense size="sm" icon="check_circle">
                  Notificado
                </q-chip>
                <q-chip v-else color="negative" text-color="white" dense size="sm" icon="warning">
                  Falta Activa
                </q-chip>
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

        <q-card-actions align="right" class="q-px-md q-pb-md gap-2">
          <q-btn flat color="negative" icon="no_accounts" label="Gestionar Abandono" @click="abrirModalAbandono(estudianteSeleccionado)" />
          <q-btn color="primary" label="Cerrar" v-close-popup unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Formulario Acción a Tomar (Redactar Observación) -->
    <q-dialog v-model="modalAccionOpen" persistent>
      <q-card style="width: 500px; max-width: 90vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-amber-3 flex items-center gap-2">
            <q-icon name="phone_in_talk" size="24px" /> Redactar Acción Tomada
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <p class="text-grey-3 text-caption">
            Ingresa las observaciones de la llamada o entrevista realizada al estudiante. Al guardar, el estado del estudiante pasará a **Notificado** y el contador de faltas activas para alertas se **reiniciará a cero**.
          </p>

          <q-input
            v-model="observacionAccion"
            type="textarea"
            rows="4"
            label="Observaciones / Acta de llamada con el estudiante *"
            placeholder="Ejemplo: Se realizó la llamada telefónica al estudiante. Indicó tener problemas de salud y se compromete a presentar justificativo médico y ponerse al día..."
            outlined dark
            class="q-mt-sm"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat color="grey-4" label="Cancelar" v-close-popup />
          <q-btn color="positive" label="Guardar Acción y Notificar" :loading="guardandoAccion" @click="guardarAccionTomada" unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Gestión de Abandono (Total / Parcial) -->
    <q-dialog v-model="modalAbandonoOpen" persistent>
      <q-card style="width: 550px; max-width: 90vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-negative flex items-center gap-2">
            <q-icon name="no_accounts" size="24px" /> Gestión de Estado de Abandono
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none" v-if="estudianteAbandonoInfo">
          <div class="text-subtitle1 text-weight-bold text-white">
            {{ estudianteAbandonoInfo.estudiante.nombre_completo }}
          </div>
          <div class="text-caption text-grey-4 q-mb-md">
            CI: {{ estudianteAbandonoInfo.estudiante.carnet }} · {{ estudianteAbandonoInfo.estudiante.carrera }}
          </div>

          <div class="text-subtitle2 text-grey-3 q-mb-xs">Selecciona el tipo de cambio de estado:</div>

          <q-option-group
            v-model="tipoAbandonoSeleccionado"
            :options="[
              { label: '🔴 Abandono Total (El estudiante abandonó todas sus materias)', value: 'total' },
              { label: '🟡 Abandono Parcial (Seleccionar materias específicas)', value: 'parcial' },
              { label: '🟢 Restablecer a Activo (Reactivar todas las materias)', value: 'restablecer' }
            ]"
            color="primary"
            dark
            class="q-mb-md"
          />

          <!-- Materias inscritas si elige Parcial -->
          <div v-if="tipoAbandonoSeleccionado === 'parcial'" class="q-mb-md">
            <div class="text-subtitle2 text-amber-3 q-mb-xs">Marcar las materias en las que el estudiante abandonó:</div>
            <div v-for="inc in estudianteAbandonoInfo.inscripciones" :key="inc.materia_id" class="q-py-xs">
              <q-checkbox
                v-model="materiasParcialAbandono"
                :val="inc.materia_id"
                :label="`${inc.materia_codigo} - ${inc.materia_nombre}`"
                dark color="negative"
              />
            </div>
          </div>

          <q-input
            v-model="motivoAbandonoText"
            type="textarea"
            rows="2"
            label="Motivo o Justificación de Abandono (Opcional)"
            placeholder="Motivo reportado por el estudiante o dirección..."
            outlined dark dense
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat color="grey-4" label="Cancelar" v-close-popup />
          <q-btn color="negative" label="Confirmar Cambio de Estado" :loading="guardandoAbandono" @click="guardarCambioAbandono" unelevated />
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

const modalAccionOpen = ref(false)
const observacionAccion = ref('')
const guardandoAccion = ref(false)

const modalAbandonoOpen = ref(false)
const estudianteAbandonoInfo = ref(null)
const tipoAbandonoSeleccionado = ref('total')
const materiasParcialAbandono = ref([])
const motivoAbandonoText = ref('')
const guardandoAbandono = ref(false)

const columns = [
  { name: 'estudiante', label: 'Estudiante', align: 'left', field: 'estudiante_nombre' },
  { name: 'materia', label: 'Materia / Carrera', align: 'left', field: 'materia_nombre' },
  { name: 'faltas', label: 'Faltas Activas', align: 'center', field: 'total_faltas', sortable: true },
  { name: 'estado', label: 'Estado Acción', align: 'center', field: 'estado_inscripcion' },
  { name: 'acciones', label: 'Acciones', align: 'center' },
]

const columnsDetalle = [
  { name: 'fecha', label: 'Fecha de Falta', align: 'left', field: 'fecha' },
  { name: 'estado_falta', label: 'Estado Falta', align: 'center', field: 'es_notificada' },
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

function abrirFormularioAccion() {
  observacionAccion.value = ''
  modalAccionOpen.value = true
}

async function guardarAccionTomada() {
  if (!observacionAccion.value || observacionAccion.value.trim().length < 5) {
    $q.notify({ type: 'warning', message: 'Por favor ingrese una observación o redacte el acta de llamada (mínimo 5 caracteres).' })
    return
  }

  guardandoAccion.value = true
  try {
    const inscId = estudianteSeleccionado.value.inscripcion_id
    const res = await api.post(`/admin/faltas/${inscId}/accion-tomar`, {
      observacion: observacionAccion.value
    })
    $q.notify({ type: 'positive', message: res.data.message })
    modalAccionOpen.value = false
    modalDetalleOpen.value = false
    cargarEstudiantes()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al guardar acción tomada.' })
  } finally {
    guardandoAccion.value = false
  }
}

async function abrirModalAbandono(row) {
  const estId = row.estudiante_id || row.id
  try {
    const res = await api.get(`/admin/estudiantes/${estId}/inscripciones-abandono`)
    estudianteAbandonoInfo.value = res.data
    materiasParcialAbandono.value = res.data.inscripciones
      .filter(i => i.es_abandono)
      .map(i => i.materia_id)

    if (res.data.es_abandono_total) {
      tipoAbandonoSeleccionado.value = 'total'
    } else if (res.data.es_abandono_parcial) {
      tipoAbandonoSeleccionado.value = 'parcial'
    } else {
      tipoAbandonoSeleccionado.value = 'total'
    }

    motivoAbandonoText.value = ''
    modalAbandonoOpen.value = true
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al consultar inscripciones del estudiante.' })
  }
}

async function guardarCambioAbandono() {
  guardandoAbandono.value = true
  try {
    const estId = estudianteAbandonoInfo.value.estudiante.id
    const res = await api.post(`/admin/estudiantes/${estId}/cambiar-abandono`, {
      tipo_abandono: tipoAbandonoSeleccionado.value,
      materia_ids: materiasParcialAbandono.value,
      motivo: motivoAbandonoText.value
    })
    $q.notify({ type: 'positive', message: res.data.message })
    modalAbandonoOpen.value = false
    modalDetalleOpen.value = false
    cargarEstudiantes()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al actualizar estado de abandono.' })
  } finally {
    guardandoAbandono.value = false
  }
}

function exportarExcel() {
  if (estudiantes.value.length === 0) {
    $q.notify({ type: 'warning', message: 'No hay datos para exportar.' })
    return
  }

  let csvContent = "\uFEFF"; // UTF-8 BOM
  csvContent += "CARNET/CI,ESTUDIANTE,CARRERA,CODIGO MATERIA,MATERIA,FALTAS ACTIVAS,FALTAS HISTORICAS,ESTADO INSCRIPCION,TIENE NOTIFICACION,ACCIONES REGISTRADAS\n";

  estudiantes.value.forEach(e => {
    const accionesStr = (e.acciones_tomadas || []).map(a => `${a.fecha_accion}: ${a.observacion}`).join(" | ");
    csvContent += `"${e.estudiante_carnet}","${e.estudiante_nombre}","${e.carrera}","${e.materia_codigo}","${e.materia_nombre}","${e.total_faltas}","${e.total_faltas_historicas}","${e.estado_inscripcion}","${e.tiene_notificacion ? 'SI' : 'NO'}","${accionesStr}"\n`;
  });

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement("a");
  const url = URL.createObjectURL(blob);
  link.setAttribute("href", url);
  link.setAttribute("download", `Reporte_Inasistencias_Y_Acciones_UNITEPC_${new Date().toISOString().slice(0,10)}.csv`);
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
    const notifBadge = e.tiene_notificacion ? '<span class="badge-purple">NOTIFICADO</span>' : '<span class="badge-amber">PENDIENTE</span>';
    const estadoBadge = e.estado_inscripcion === 'abandono' ? '<span class="badge-danger">ABANDONO</span>' : notifBadge;

    rowsHtml += `
      <tr>
        <td><strong>${e.estudiante_carnet}</strong></td>
        <td>${e.estudiante_nombre}</td>
        <td>${e.carrera}</td>
        <td><strong>${e.materia_codigo}</strong> - ${e.materia_nombre}</td>
        <td style="text-align: center; font-weight: bold; color: #dc2626;">${e.total_faltas}</td>
        <td style="text-align: center;">${estadoBadge}</td>
      </tr>
    `;
  });

  const html = `
    <!DOCTYPE html>
    <html>
    <head>
      <title>Reporte Inasistencias y Acciones Tomadas - UNITEPC</title>
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
        .badge-danger { background: #fee2e2; color: #991b1b; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-purple { background: #f3e8ff; color: #6b21a8; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-amber { background: #fef3c7; color: #92400e; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .footer { margin-top: 30px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
      </style>
    </head>
    <body>
      <div class="header">
        <div>
          <div class="title">UNITEPC - UNIVERSIDAD PRIVADA</div>
          <div class="subtitle">Reporte Institucional de Inasistencias, Notificaciones y Abandono</div>
        </div>
        <img src="/logo-unitepc.png" alt="UNITEPC Logo">
      </div>

      <div class="meta-info">
        <strong>Fecha de emisión:</strong> ${fechaHoy} &nbsp;|&nbsp; 
        <strong>Criterio:</strong> ≥ ${limiteFaltas.value} faltas activas &nbsp;|&nbsp; 
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
            <th style="text-align: center;">Faltas Activas</th>
            <th style="text-align: center;">Estado Acción</th>
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

.accion-banner {
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: 12px;
}

.historial-list {
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(147, 51, 234, 0.25);
  border-radius: 10px;
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
