<template>
  <q-page class="q-pa-lg">
    <div class="flex items-center justify-between q-mb-lg flex-wrap gap-3">
      <div>
        <h2 class="text-h5 text-weight-bold text-white q-ma-none">Gestión de Estudiantes e Inscripciones</h2>
        <div class="text-caption text-grey-4">Adicionar nuevos estudiantes y vincularlos a materias por carrera</div>
      </div>
      <q-btn color="primary" icon="person_add" label="Nuevo Estudiante" no-caps @click="abrirCrearModal" unelevated />
    </div>

    <!-- Filtros -->
    <q-card class="dark-card q-mb-lg q-pa-md">
      <div class="row q-col-gutter-md items-center">
        <div class="col-12 col-md-4">
          <q-select
            v-model="filtroCarrera"
            :options="carreras"
            option-value="id"
            option-label="nombre"
            emit-value map-options
            label="Filtrar por Carrera" outlined dark dense clearable
            @update:model-value="cargarEstudiantes"
          />
        </div>
        <div class="col-12 col-md-5">
          <q-input v-model="buscarQuery" label="Buscar por Carnet o Nombre" outlined dark dense clearable @keyup.enter="cargarEstudiantes" />
        </div>
        <div class="col-12 col-md-3 flex justify-end">
          <q-btn color="blue-7" icon="search" label="Buscar" no-caps @click="cargarEstudiantes" unelevated />
        </div>
      </div>
    </q-card>

    <!-- Tabla -->
    <q-card class="dark-card">
      <q-table
        :rows="estudiantesFiltrados"
        :columns="columns"
        row-key="id"
        flat dark
        :loading="loading"
        no-data-label="No hay estudiantes registrados"
      >
        <template #body-cell-nombre_completo="props">
          <q-td :props="props">
            <div class="text-weight-bold text-white">{{ props.row.primer_apellido }} {{ props.row.segundo_apellido }} {{ props.row.nombres }}</div>
          </q-td>
        </template>

        <template #body-cell-carrera="props">
          <q-td :props="props">
            <q-badge color="indigo-7" class="text-weight-medium">
              {{ props.row.carrera?.nombre || 'S/C' }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" align="right">
            <q-btn flat round dense icon="library_add" color="positive" @click="abrirInscribirModal(props.row)">
              <q-tooltip>Inscribir a Materia</q-tooltip>
            </q-btn>
            <q-btn flat round dense icon="edit" color="blue-4" @click="abrirEditarModal(props.row)" />
            <q-btn flat round dense icon="delete" color="negative" @click="eliminarEstudiante(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Crear / Editar Estudiante -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card style="width: 460px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-white">{{ isEditing ? 'Editar Estudiante' : 'Nuevo Estudiante' }}</div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="form.carnet" label="Carnet de Identidad / Código" outlined dark dense class="q-mb-md" :disable="isEditing" />
          <q-input v-model="form.nombres" label="Nombre(s)" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.primer_apellido" label="1er. Apellido" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.segundo_apellido" label="2do. Apellido (Opcional)" outlined dark dense class="q-mb-md" />
          <q-select
            v-model="form.carrera_id"
            :options="carreras"
            option-value="id"
            option-label="nombre"
            emit-value map-options
            label="Carrera" outlined dark dense class="q-mb-md"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="primary" label="Guardar" :loading="saving" @click="guardarEstudiante" unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Inscribir a Materia -->
    <q-dialog v-model="modalInscribirOpen" persistent>
      <q-card style="width: 480px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div>
            <div class="text-h6 text-weight-bold text-white">Inscribir a Materia</div>
            <div class="text-caption text-grey-4" v-if="selectedEstudiante">
              Estudiante: {{ selectedEstudiante.primer_apellido }} {{ selectedEstudiante.nombres }} ({{ selectedEstudiante.carnet }})
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
            v-model="inscribirMateriaId"
            :options="materias"
            option-value="id"
            :option-label="m => `${m.codigo} - ${m.nombre}`"
            emit-value map-options
            label="Seleccionar Materia" outlined dark dense class="q-mb-md"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="positive" icon="check" label="Inscribir Estudiante" :loading="savingInscripcion" @click="procesarInscripcion" unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const loading = ref(false)
const saving = ref(false)
const savingInscripcion = ref(false)
const estudiantes = ref([])
const carreras = ref([])
const materias = ref([])
const filtroCarrera = ref(null)
const buscarQuery = ref('')

const modalOpen = ref(false)
const modalInscribirOpen = ref(false)
const isEditing = ref(false)
const selectedId = ref(null)
const selectedEstudiante = ref(null)
const inscribirMateriaId = ref(null)

const form = reactive({
  carnet: '',
  nombres: '',
  primer_apellido: '',
  segundo_apellido: '',
  carrera_id: null,
})

const columns = [
  { name: 'carnet', label: 'Carnet / Código', field: 'carnet', align: 'left', sortable: true },
  { name: 'nombre_completo', label: 'Estudiante', field: 'nombre_completo', align: 'left', sortable: true },
  { name: 'carrera', label: 'Carrera', field: 'carrera', align: 'left' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'right' },
]

const estudiantesFiltrados = computed(() => {
  let list = estudiantes.value
  if (buscarQuery.value) {
    const q = buscarQuery.value.toLowerCase()
    list = list.filter(e =>
      e.carnet.toLowerCase().includes(q) ||
      e.nombres.toLowerCase().includes(q) ||
      e.primer_apellido.toLowerCase().includes(q)
    )
  }
  return list
})

async function cargarEstudiantes() {
  loading.value = true
  try {
    const res = await api.get('/admin/estudiantes', { params: { carrera_id: filtroCarrera.value } })
    estudiantes.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar estudiantes', position: 'top' })
  } finally {
    loading.value = false
  }
}

async function cargarCatalogos() {
  try {
    const [resC, resM] = await Promise.all([
      api.get('/admin/carreras'),
      api.get('/admin/materias')
    ])
    carreras.value = resC.data
    materias.value = resM.data
  } catch (e) {
    console.error(e)
  }
}

function abrirCrearModal() {
  isEditing.value = false
  selectedId.value = null
  form.carnet = ''
  form.nombres = ''
  form.primer_apellido = ''
  form.segundo_apellido = ''
  form.carrera_id = carreras.value[0]?.id || null
  modalOpen.value = true
}

function abrirEditarModal(row) {
  isEditing.value = true
  selectedId.value = row.id
  form.carnet = row.carnet
  form.nombres = row.nombres
  form.primer_apellido = row.primer_apellido
  form.segundo_apellido = row.segundo_apellido || ''
  form.carrera_id = row.carrera_id
  modalOpen.value = true
}

function abrirInscribirModal(row) {
  selectedEstudiante.value = row
  inscribirMateriaId.value = null
  modalInscribirOpen.value = true
}

async function guardarEstudiante() {
  if (!form.carnet || !form.nombres || !form.primer_apellido || !form.carrera_id) {
    $q.notify({ type: 'warning', message: 'Carnet, Nombres, Apellido y Carrera son requeridos', position: 'top' })
    return
  }
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/admin/estudiantes/${selectedId.value}`, form)
    } else {
      await api.post('/admin/estudiantes', form)
    }
    $q.notify({ type: 'positive', message: '✅ Estudiante guardado correctamente.', position: 'top' })
    modalOpen.value = false
    cargarEstudiantes()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al guardar estudiante.', position: 'top' })
  } finally {
    saving.value = false
  }
}

async function procesarInscripcion() {
  if (!inscribirMateriaId.value || !selectedEstudiante.value) return
  savingInscripcion.value = true
  try {
    await api.post(`/admin/estudiantes/${selectedEstudiante.value.id}/inscribir`, {
      materia_id: inscribirMateriaId.value
    })
    $q.notify({ type: 'positive', message: '✅ Estudiante inscrito en la materia correctamente.', position: 'top' })
    modalInscribirOpen.value = false
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al inscribir en materia.', position: 'top' })
  } finally {
    savingInscripcion.value = false
  }
}

function eliminarEstudiante(row) {
  $q.dialog({
    title: 'Eliminar Estudiante',
    message: `¿Eliminar a ${row.primer_apellido} ${row.nombres}?`,
    cancel: true, dark: true,
  }).onOk(async () => {
    try {
      await api.delete(`/admin/estudiantes/${row.id}`)
      $q.notify({ type: 'positive', message: 'Estudiante eliminado.', position: 'top' })
      cargarEstudiantes()
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Error al eliminar.', position: 'top' })
    }
  })
}

onMounted(() => {
  cargarEstudiantes()
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
