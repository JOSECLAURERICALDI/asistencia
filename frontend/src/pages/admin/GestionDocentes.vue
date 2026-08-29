<template>
  <q-page class="q-pa-lg">
    <div class="flex items-center justify-between q-mb-lg flex-wrap gap-3">
      <div>
        <h2 class="text-h5 text-weight-bold text-white q-ma-none">Gestión de Docentes</h2>
        <div class="text-caption text-grey-4">Adicionar, editar y administrar el plantel docente de la universidad</div>
      </div>
      <q-btn color="primary" icon="person_add" label="Nuevo Docente" no-caps @click="abrirCrearModal" unelevated />
    </div>

    <!-- Filtros -->
    <q-card class="dark-card q-mb-lg q-pa-md">
      <div class="row q-col-gutter-md items-center">
        <div class="col-12 col-md-8">
          <q-input v-model="buscarQuery" label="Buscar docente por CI o Nombre" outlined dark dense clearable />
        </div>
        <div class="col-12 col-md-4 flex justify-end">
          <q-btn color="blue-7" icon="search" label="Buscar" no-caps unelevated />
        </div>
      </div>
    </q-card>

    <!-- Tabla -->
    <q-card class="dark-card">
      <q-table
        :rows="docentesFiltrados"
        :columns="columns"
        row-key="id"
        flat dark
        :loading="loading"
        no-data-label="No hay docentes registrados"
      >
        <template #body-cell-ci="props">
          <q-td :props="props">
            <q-badge color="blue-7" class="text-weight-bold q-px-sm q-py-xs">
              {{ props.row.ci }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-nombre_completo="props">
          <q-td :props="props">
            <div class="text-weight-bold text-white">{{ props.row.apellido }} {{ props.row.nombre }}</div>
          </q-td>
        </template>

        <template #body-cell-activo="props">
          <q-td :props="props" align="center">
            <q-chip dense :color="props.row.activo ? 'positive' : 'negative'" text-color="white" class="text-weight-bold">
              {{ props.row.activo ? 'Activo' : 'Inactivo' }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" align="right">
            <q-btn flat round dense icon="edit" color="blue-4" @click="abrirEditarModal(props.row)" />
            <q-btn flat round dense icon="delete" color="negative" @click="eliminarDocente(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Crear / Editar -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card style="width: 440px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-white">{{ isEditing ? 'Editar Docente' : 'Nuevo Docente' }}</div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="form.ci" label="Cédula de Identidad (CI)" outlined dark dense class="q-mb-md" :disable="isEditing" />
          <q-input v-model="form.nombre" label="Nombre(s)" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.apellido" label="Apellidos" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.email" type="email" label="Correo electrónico (Opcional)" outlined dark dense class="q-mb-md" />
          <q-toggle v-model="form.activo" label="Docente Activo" color="positive" dark />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="primary" label="Guardar" :loading="saving" @click="guardarDocente" unelevated />
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
const docentes = ref([])
const buscarQuery = ref('')

const modalOpen = ref(false)
const isEditing = ref(false)
const selectedId = ref(null)

const form = reactive({
  ci: '',
  nombre: '',
  apellido: '',
  email: '',
  activo: true,
})

const columns = [
  { name: 'ci', label: 'CI', field: 'ci', align: 'left', sortable: true },
  { name: 'nombre_completo', label: 'Nombre Completo', field: 'nombre_completo', align: 'left', sortable: true },
  { name: 'email', label: 'Email', field: 'email', align: 'left' },
  { name: 'activo', label: 'Estado', field: 'activo', align: 'center' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'right' },
]

const docentesFiltrados = computed(() => {
  if (!buscarQuery.value) return docentes.value
  const q = buscarQuery.value.toLowerCase()
  return docentes.value.filter(d =>
    d.ci.toLowerCase().includes(q) ||
    d.nombre.toLowerCase().includes(q) ||
    d.apellido.toLowerCase().includes(q)
  )
})

async function cargarDocentes() {
  loading.value = true
  try {
    const res = await api.get('/admin/docentes')
    docentes.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar docentes', position: 'top' })
  } finally {
    loading.value = false
  }
}

function abrirCrearModal() {
  isEditing.value = false
  selectedId.value = null
  form.ci = ''
  form.nombre = ''
  form.apellido = ''
  form.email = ''
  form.activo = true
  modalOpen.value = true
}

function abrirEditarModal(row) {
  isEditing.value = true
  selectedId.value = row.id
  form.ci = row.ci
  form.nombre = row.nombre
  form.apellido = row.apellido
  form.email = row.email || ''
  form.activo = row.activo
  modalOpen.value = true
}

async function guardarDocente() {
  if (!form.ci || !form.nombre || !form.apellido) {
    $q.notify({ type: 'warning', message: 'CI, Nombre y Apellido son requeridos', position: 'top' })
    return
  }
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/admin/docentes/${selectedId.value}`, form)
    } else {
      await api.post('/admin/docentes', form)
    }
    $q.notify({ type: 'positive', message: '✅ Docente guardado exitosamente.', position: 'top' })
    modalOpen.value = false
    cargarDocentes()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al guardar docente.', position: 'top' })
  } finally {
    saving.value = false
  }
}

function eliminarDocente(row) {
  $q.dialog({
    title: 'Eliminar Docente',
    message: `¿Eliminar al docente ${row.nombre} ${row.apellido}?`,
    cancel: true, dark: true,
  }).onOk(async () => {
    try {
      await api.delete(`/admin/docentes/${row.id}`)
      $q.notify({ type: 'positive', message: 'Docente eliminado.', position: 'top' })
      cargarDocentes()
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Error al eliminar docente.', position: 'top' })
    }
  })
}

onMounted(cargarDocentes)
</script>

<style lang="scss" scoped>
.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 14px;
}
</style>
