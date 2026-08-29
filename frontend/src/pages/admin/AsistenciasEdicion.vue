<template>
  <q-page class="q-pa-lg">
    <div class="flex items-center justify-between q-mb-lg flex-wrap gap-3">
      <div>
        <h2 class="text-h5 text-weight-bold text-white q-ma-none">Edición de Asistencias</h2>
        <div class="text-caption text-grey-4">Modificar o corregir asistencias registradas de cualquier estudiante y materia</div>
      </div>
    </div>

    <!-- Filtros -->
    <q-card class="dark-card q-mb-lg q-pa-md">
      <div class="row q-col-gutter-md items-center">
        <div class="col-12 col-md-3">
          <q-input v-model="filtros.fecha" type="date" label="Fecha" outlined dark dense clearable @update:model-value="cargarAsistencias" />
        </div>
        <div class="col-12 col-md-3">
          <q-select
            v-model="filtros.carrera_id"
            :options="carreras"
            option-value="id"
            option-label="nombre"
            emit-value map-options
            label="Carrera" outlined dark dense clearable
            @update:model-value="cargarAsistencias"
          />
        </div>
        <div class="col-12 col-md-3">
          <q-input v-model="filtros.buscar" label="Buscar estudiante (Carnet / Nombre)" outlined dark dense clearable @keyup.enter="cargarAsistencias" />
        </div>
        <div class="col-12 col-md-3 flex justify-end">
          <q-btn color="primary" icon="search" label="Filtrar" no-caps @click="cargarAsistencias" unelevated />
        </div>
      </div>
    </q-card>

    <!-- Tabla -->
    <q-card class="dark-card">
      <q-table
        :rows="asistencias"
        :columns="columns"
        row-key="id"
        flat dark
        :loading="loading"
        no-data-label="No se encontraron registros de asistencia con los filtros seleccionados"
      >
        <template #body-cell-estudiante="props">
          <q-td :props="props">
            <div class="text-weight-bold text-white">{{ props.row.inscripcion?.estudiante?.nombres }} {{ props.row.inscripcion?.estudiante?.primer_apellido }} {{ props.row.inscripcion?.estudiante?.segundo_apellido }}</div>
            <div class="text-caption text-grey-4">CI: {{ props.row.inscripcion?.estudiante?.carnet }}</div>
          </q-td>
        </template>

        <template #body-cell-materia="props">
          <q-td :props="props">
            <div class="text-weight-bold text-blue-3">{{ props.row.inscripcion?.materia?.codigo }} - {{ props.row.inscripcion?.materia?.nombre }}</div>
            <div class="text-caption text-grey-4">{{ props.row.inscripcion?.materia?.carrera?.nombre }}</div>
          </q-td>
        </template>

        <template #body-cell-docente="props">
          <q-td :props="props">
            <div class="text-white">{{ props.row.horario?.docente?.nombre }} {{ props.row.horario?.docente?.apellido }}</div>
            <div class="text-caption text-grey-4">CI: {{ props.row.horario?.docente?.ci }}</div>
          </q-td>
        </template>

        <template #body-cell-estado="props">
          <q-td :props="props">
            <q-chip
              dense
              :color="props.row.estado === 'presente' ? 'positive' : props.row.estado === 'permiso' ? 'warning' : 'negative'"
              text-color="white"
              class="text-weight-bold text-capitalize"
            >
              {{ props.row.estado }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" align="right">
            <q-btn flat round dense icon="edit" color="blue-4" @click="abrirEditarModal(props.row)">
              <q-tooltip>Editar Asistencia</q-tooltip>
            </q-btn>
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Editar Asistencia -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card style="width: 440px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-white">Editar Registro de Asistencia</div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none" v-if="selectedAsistencia">
          <div class="text-subtitle2 text-blue-3 q-mb-xs">Estudiante:</div>
          <div class="text-body2 text-white q-mb-md">
            {{ selectedAsistencia.inscripcion?.estudiante?.nombres }} {{ selectedAsistencia.inscripcion?.estudiante?.primer_apellido }} ({{ selectedAsistencia.inscripcion?.estudiante?.carnet }})
          </div>

          <div class="text-subtitle2 text-blue-3 q-mb-xs">Materia y Fecha:</div>
          <div class="text-body2 text-white q-mb-md">
            {{ selectedAsistencia.inscripcion?.materia?.codigo }} · Fecha: {{ selectedAsistencia.fecha }}
          </div>

          <q-select
            v-model="editForm.estado"
            :options="[
              { label: 'Presente', value: 'presente' },
              { label: 'Licencia / Permiso', value: 'permiso' },
              { label: 'Ausente (Falta)', value: 'ausente' }
            ]"
            emit-value map-options
            label="Estado de Asistencia"
            outlined dark dense
            class="q-mb-md"
          />

          <q-input
            v-model="editForm.justificacion_retroactiva"
            type="textarea"
            rows="3"
            label="Justificación / Observación (Opcional)"
            outlined dark dense
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="primary" label="Guardar Cambios" :loading="saving" @click="guardarCambios" unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const loading = ref(false)
const saving = ref(false)
const asistencias = ref([])
const carreras = ref([])
const modalOpen = ref(false)
const selectedAsistencia = ref(null)

const filtros = reactive({
  fecha: '',
  carrera_id: null,
  buscar: '',
})

const editForm = reactive({
  estado: 'presente',
  justificacion_retroactiva: '',
})

const columns = [
  { name: 'fecha', label: 'Fecha', field: 'fecha', sortable: true, align: 'left' },
  { name: 'estudiante', label: 'Estudiante', field: 'estudiante', align: 'left' },
  { name: 'materia', label: 'Materia', field: 'materia', align: 'left' },
  { name: 'docente', label: 'Docente', field: 'docente', align: 'left' },
  { name: 'estado', label: 'Estado', field: 'estado', align: 'center' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'right' },
]

async function cargarCarreras() {
  try {
    const res = await api.get('/admin/carreras')
    carreras.value = res.data
  } catch (e) {
    console.error(e)
  }
}

async function cargarAsistencias() {
  loading.value = true
  try {
    const res = await api.get('/admin/asistencias', { params: filtros })
    asistencias.value = res.data.data || res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar asistencias', position: 'top' })
  } finally {
    loading.value = false
  }
}

function abrirEditarModal(row) {
  selectedAsistencia.value = row
  editForm.estado = row.estado
  editForm.justificacion_retroactiva = row.justificacion_retroactiva || ''
  modalOpen.value = true
}

async function guardarCambios() {
  if (!selectedAsistencia.value) return
  saving.value = true
  try {
    await api.put(`/admin/asistencias/${selectedAsistencia.value.id}`, editForm)
    $q.notify({ type: 'positive', message: '✅ Asistencia actualizada correctamente.', position: 'top' })
    modalOpen.value = false
    cargarAsistencias()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al actualizar asistencia.', position: 'top' })
  } finally {
    saving.value = false
  }
}

onMounted(() => {
  cargarCarreras()
  cargarAsistencias()
})
</script>

<style lang="scss" scoped>
.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 14px;
}
</style>
