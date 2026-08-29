<template>
  <q-page class="q-pa-lg">
    <div class="flex items-center justify-between q-mb-lg flex-wrap gap-3">
      <div>
        <h2 class="text-h5 text-weight-bold text-white q-ma-none">Gestión de Directores y Administradores</h2>
        <div class="text-caption text-grey-4">Administrar accesos de Directores de Carrera y SuperAdministradores</div>
      </div>
      <q-btn color="primary" icon="person_add" label="Nuevo Director / Admin" no-caps @click="abrirCrearModal" unelevated />
    </div>

    <!-- Tabla -->
    <q-card class="dark-card">
      <q-table
        :rows="admins"
        :columns="columns"
        row-key="id"
        flat dark
        :loading="loading"
        no-data-label="No hay directores ni administradores registrados"
      >
        <template #body-cell-super_admin="props">
          <q-td :props="props" align="center">
            <q-chip
              dense
              :color="props.row.super_admin ? 'purple-7' : 'blue-7'"
              text-color="white"
              class="text-weight-bold"
            >
              {{ props.row.super_admin ? '⚡ SuperAdmin' : '🏛️ Director de Carrera' }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-carrera="props">
          <q-td :props="props">
            <div class="text-white text-weight-medium">{{ props.row.carrera?.nombre || 'Todas las Carreras' }}</div>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" align="right">
            <q-btn flat round dense icon="edit" color="blue-4" @click="abrirEditarModal(props.row)" />
            <q-btn flat round dense icon="delete" color="negative" @click="eliminarAdmin(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Crear / Editar -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card style="width: 480px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-white">{{ isEditing ? 'Editar Director / Admin' : 'Nuevo Director / Admin' }}</div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="form.nombre" label="Nombre Completo" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.email" type="email" label="Correo electrónico / Usuario" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.password" type="password" :label="isEditing ? 'Contraseña (dejar en blanco para no cambiar)' : 'Contraseña'" outlined dark dense class="q-mb-md" />
          
          <q-toggle v-model="form.super_admin" label="Permisos de SuperAdmin (Acceso a todas las carreras)" color="purple-4" dark class="q-mb-md" />

          <q-select
            v-if="!form.super_admin"
            v-model="form.carrera_id"
            :options="carreras"
            option-value="id"
            option-label="nombre"
            emit-value map-options
            label="Carrera Asignada" outlined dark dense
            class="q-mb-md"
          />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="primary" label="Guardar" :loading="saving" @click="guardarAdmin" unelevated />
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
const admins = ref([])
const carreras = ref([])
const modalOpen = ref(false)
const isEditing = ref(false)
const selectedId = ref(null)

const form = reactive({
  nombre: '',
  email: '',
  password: '',
  carrera_id: null,
  super_admin: false,
})

const columns = [
  { name: 'nombre', label: 'Nombre', field: 'nombre', align: 'left', sortable: true },
  { name: 'email', label: 'Correo / Usuario', field: 'email', align: 'left' },
  { name: 'super_admin', label: 'Tipo de Rol', field: 'super_admin', align: 'center' },
  { name: 'carrera', label: 'Carrera Asignada', field: 'carrera', align: 'left' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'right' },
]

async function cargarAdmins() {
  loading.value = true
  try {
    const res = await api.get('/admin/administradores')
    admins.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar usuarios administradores', position: 'top' })
  } finally {
    loading.value = false
  }
}

async function cargarCarreras() {
  try {
    const res = await api.get('/admin/carreras')
    carreras.value = res.data
  } catch (e) {
    console.error(e)
  }
}

function abrirCrearModal() {
  isEditing.value = false
  selectedId.value = null
  form.nombre = ''
  form.email = ''
  form.password = ''
  form.carrera_id = null
  form.super_admin = false
  modalOpen.value = true
}

function abrirEditarModal(row) {
  isEditing.value = true
  selectedId.value = row.id
  form.nombre = row.nombre
  form.email = row.email
  form.password = ''
  form.carrera_id = row.carrera_id
  form.super_admin = row.super_admin
  modalOpen.value = true
}

async function guardarAdmin() {
  if (!form.nombre || !form.email) {
    $q.notify({ type: 'warning', message: 'Nombre y Correo son requeridos', position: 'top' })
    return
  }
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/admin/administradores/${selectedId.value}`, form)
    } else {
      await api.post('/admin/administradores', form)
    }
    $q.notify({ type: 'positive', message: '✅ Guardado correctamente.', position: 'top' })
    modalOpen.value = false
    cargarAdmins()
  } catch (e) {
    const msg = e.response?.data?.message || 'Error al guardar usuario.'
    $q.notify({ type: 'negative', message: msg, position: 'top' })
  } finally {
    saving.value = false
  }
}

function eliminarAdmin(row) {
  $q.dialog({
    title: 'Eliminar usuario',
    message: `¿Eliminar a ${row.nombre}?`,
    cancel: true, dark: true,
  }).onOk(async () => {
    try {
      await api.delete(`/admin/administradores/${row.id}`)
      $q.notify({ type: 'positive', message: 'Usuario eliminado.', position: 'top' })
      cargarAdmins()
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Error al eliminar.', position: 'top' })
    }
  })
}

onMounted(() => {
  cargarAdmins()
  cargarCarreras()
})
</script>

<style lang="scss" scoped>
.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 14px;
}
</style>
