<template>
  <q-page class="q-pa-lg">
    <div class="flex items-center justify-between q-mb-lg flex-wrap gap-3">
      <div>
        <h2 class="text-h5 text-weight-bold text-white q-ma-none">Gestión de Horarios y Materias</h2>
        <div class="text-caption text-grey-4">Crear y administrar bloques de horarios, asignación de docentes y aulas</div>
      </div>
      <q-btn color="primary" icon="add" label="Nuevo Horario" no-caps @click="abrirCrearModal" unelevated />
    </div>

    <!-- Tabla -->
    <q-card class="dark-card">
      <q-table
        :rows="horarios"
        :columns="columns"
        row-key="id"
        flat dark
        :loading="loading"
        no-data-label="No hay horarios registrados"
      >
        <template #body-cell-materia="props">
          <q-td :props="props">
            <div class="text-weight-bold text-blue-3">{{ props.row.materia?.codigo }} - {{ props.row.materia?.nombre }}</div>
            <div class="text-caption text-grey-4">{{ props.row.materia?.carrera?.nombre }}</div>
          </q-td>
        </template>

        <template #body-cell-docente="props">
          <q-td :props="props">
            <div class="text-white">{{ props.row.docente?.nombre }} {{ props.row.docente?.apellido }}</div>
            <div class="text-caption text-grey-4">CI: {{ props.row.docente?.ci }}</div>
          </q-td>
        </template>

        <template #body-cell-dia_semana="props">
          <q-td :props="props">
            <q-badge color="purple-7" class="text-weight-bold text-capitalize">
              {{ props.row.dia_semana }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-hora="props">
          <q-td :props="props">
            <div class="text-white font-mono">{{ props.row.hora_inicio }} – {{ props.row.hora_fin }}</div>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" align="right">
            <q-btn flat round dense icon="edit" color="blue-4" @click="abrirEditarModal(props.row)" />
            <q-btn flat round dense icon="delete" color="negative" @click="eliminarHorario(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Crear / Editar -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card style="width: 480px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-white">{{ isEditing ? 'Editar Horario' : 'Nuevo Horario' }}</div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
            v-model="form.materia_id"
            :options="materias"
            option-value="id"
            :option-label="m => `${m.codigo} - ${m.nombre}`"
            emit-value map-options
            label="Materia" outlined dark dense class="q-mb-md"
          />

          <q-select
            v-model="form.docente_id"
            :options="docentes"
            option-value="id"
            :option-label="d => `${d.nombre} ${d.apellido} (${d.ci})`"
            emit-value map-options
            label="Docente Asignado" outlined dark dense class="q-mb-md"
          />

          <q-select
            v-model="form.dia_semana"
            :options="[
              { label: 'Lunes', value: 'lunes' },
              { label: 'Martes', value: 'martes' },
              { label: 'Miércoles', value: 'miercoles' },
              { label: 'Jueves', value: 'jueves' },
              { label: 'Viernes', value: 'viernes' },
              { label: 'Sábado', value: 'sabado' }
            ]"
            emit-value map-options
            label="Día de la Semana" outlined dark dense class="q-mb-md"
          />

          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-6">
              <q-input v-model="form.hora_inicio" label="Hora Inicio (HH:MM)" placeholder="07:30" outlined dark dense />
            </div>
            <div class="col-6">
              <q-input v-model="form.hora_fin" label="Hora Fin (HH:MM)" placeholder="09:45" outlined dark dense />
            </div>
          </div>

          <div class="row q-col-gutter-sm q-mb-md">
            <div class="col-6">
              <q-input v-model="form.aula" label="Aula / Lab" placeholder="A-101" outlined dark dense />
            </div>
            <div class="col-6">
              <q-select
                v-model="form.tipo"
                :options="[
                  { label: 'Teórica', value: 'teorica' },
                  { label: 'Práctica', value: 'practica' }
                ]"
                emit-value map-options
                label="Tipo de Clase" outlined dark dense
              />
            </div>
          </div>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="primary" label="Guardar" :loading="saving" @click="guardarHorario" unelevated />
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
const horarios = ref([])
const materias = ref([])
const docentes = ref([])

const modalOpen = ref(false)
const isEditing = ref(false)
const selectedId = ref(null)

const form = reactive({
  materia_id: null,
  docente_id: null,
  dia_semana: 'lunes',
  hora_inicio: '07:30',
  hora_fin: '09:45',
  aula: 'A-101',
  tipo: 'teorica',
})

const columns = [
  { name: 'materia', label: 'Materia / Carrera', field: 'materia', align: 'left' },
  { name: 'docente', label: 'Docente', field: 'docente', align: 'left' },
  { name: 'dia_semana', label: 'Día', field: 'dia_semana', align: 'center' },
  { name: 'hora', label: 'Horario', field: 'hora', align: 'center' },
  { name: 'aula', label: 'Aula', field: 'aula', align: 'center' },
  { name: 'tipo', label: 'Tipo', field: 'tipo', align: 'center' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'right' },
]

async function cargarHorarios() {
  loading.value = true
  try {
    const res = await api.get('/admin/horarios')
    horarios.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar horarios', position: 'top' })
  } finally {
    loading.value = false
  }
}

async function cargarCatalogos() {
  try {
    const [resM, resD] = await Promise.all([
      api.get('/admin/materias'),
      api.get('/admin/docentes')
    ])
    materias.value = resM.data
    docentes.value = resD.data
  } catch (e) {
    console.error(e)
  }
}

function abrirCrearModal() {
  isEditing.value = false
  selectedId.value = null
  form.materia_id = materias.value[0]?.id || null
  form.docente_id = docentes.value[0]?.id || null
  form.dia_semana = 'lunes'
  form.hora_inicio = '07:30'
  form.hora_fin = '09:45'
  form.aula = 'A-101'
  form.tipo = 'teorica'
  modalOpen.value = true
}

function abrirEditarModal(row) {
  isEditing.value = true
  selectedId.value = row.id
  form.materia_id = row.materia_id
  form.docente_id = row.docente_id
  form.dia_semana = row.dia_semana
  form.hora_inicio = row.hora_inicio
  form.hora_fin = row.hora_fin
  form.aula = row.aula || ''
  form.tipo = row.tipo
  modalOpen.value = true
}

async function guardarHorario() {
  if (!form.materia_id || !form.docente_id || !form.dia_semana || !form.hora_inicio || !form.hora_fin) {
    $q.notify({ type: 'warning', message: 'Completa todos los campos obligatorios', position: 'top' })
    return
  }
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/admin/horarios/${selectedId.value}`, form)
    } else {
      await api.post('/admin/horarios', form)
    }
    $q.notify({ type: 'positive', message: '✅ Horario guardado exitosamente.', position: 'top' })
    modalOpen.value = false
    cargarHorarios()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al guardar horario.', position: 'top' })
  } finally {
    saving.value = false
  }
}

function eliminarHorario(row) {
  $q.dialog({
    title: 'Eliminar Horario',
    message: `¿Eliminar horario de ${row.materia?.codigo}?`,
    cancel: true, dark: true,
  }).onOk(async () => {
    try {
      await api.delete(`/admin/horarios/${row.id}`)
      $q.notify({ type: 'positive', message: 'Horario eliminado.', position: 'top' })
      cargarHorarios()
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Error al eliminar horario.', position: 'top' })
    }
  })
}

onMounted(() => {
  cargarHorarios()
  cargarCatalogos()
})
</script>

<style lang="scss" scoped>
.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 14px;
}
</style>
