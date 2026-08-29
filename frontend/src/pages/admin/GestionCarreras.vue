<template>
  <q-page class="q-pa-lg">
    <!-- Header y Acciones -->
    <div class="flex items-center justify-between q-mb-lg flex-wrap gap-3">
      <div>
        <h2 class="text-h5 text-weight-bold text-white q-ma-none">Gestión de Carreras Universitarias</h2>
        <div class="text-caption text-grey-4">Registrar y administrar las carreras profesionales por sede universitaria</div>
      </div>
      
      <div class="flex items-center gap-2 flex-wrap">
        <q-btn
          color="positive" icon="upload_file" label="Importar Carreras (Excel)"
          no-caps unelevated @click="modalImportOpen = true" class="action-btn"
        />
        
        <q-btn
          color="primary" icon="add" label="Nueva Carrera"
          no-caps unelevated @click="abrirCrearModal" class="action-btn"
        />
      </div>
    </div>

    <!-- Tabla -->
    <q-card class="dark-card">
      <q-table
        :rows="carreras"
        :columns="columns"
        row-key="id"
        flat dark
        :loading="loading"
        :pagination="{ rowsPerPage: 10 }"
        no-data-label="No hay carreras registradas"
      >
        <template #body-cell-sigla="props">
          <q-td :props="props">
            <q-badge color="blue-7" class="text-weight-bold q-px-sm q-py-xs">
              {{ props.row.sigla || 'S/S' }}
            </q-badge>
          </q-td>
        </template>

        <template #body-cell-sede="props">
          <q-td :props="props">
            <q-chip dense color="deep-purple-7" text-color="white" icon="location_on" class="text-weight-bold">
              {{ props.row.sede || 'Cochabamba' }}
            </q-chip>
          </q-td>
        </template>

        <template #body-cell-acciones="props">
          <q-td :props="props" align="right">
            <q-btn flat round dense icon="edit" color="blue-4" @click="abrirEditarModal(props.row)" />
            <q-btn flat round dense icon="delete" color="negative" @click="eliminarCarrera(props.row)" />
          </q-td>
        </template>
      </q-table>
    </q-card>

    <!-- Modal Crear / Editar -->
    <q-dialog v-model="modalOpen" persistent>
      <q-card style="width: 460px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="text-h6 text-weight-bold text-white">{{ isEditing ? 'Editar Carrera' : 'Nueva Carrera' }}</div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input v-model="form.nombre" label="Nombre de la Carrera (ej: Medicina)" outlined dark dense class="q-mb-md" />
          <q-input v-model="form.sigla" label="Sigla o Código (ej: MED, SIS, DER)" outlined dark dense class="q-mb-md" />
          
          <q-select
            v-model="form.sede"
            :options="sedesDisponibles"
            use-input
            new-value-mode="add-unique"
            label="Sede Universitaria (ej: Cochabamba, La Paz, Santa Cruz)"
            outlined dark dense class="q-mb-md"
            hint="Puedes seleccionar o escribir una sede personalizada"
          />

          <q-input v-model="form.descripcion" type="textarea" rows="3" label="Descripción (Opcional)" outlined dark dense />
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn color="primary" label="Guardar" :loading="saving" @click="guardarCarrera" unelevated />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Importar Carreras por Lote (Excel) -->
    <q-dialog v-model="modalImportOpen" persistent>
      <q-card style="width: 500px; max-width: 95vw;" class="dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="upload_file" color="positive" size="24px" />
            <div class="text-h6 text-weight-bold text-white">Importar Carreras por Lote</div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <p class="text-caption text-grey-3 q-mb-md">
            Sube una planilla Excel (`.xlsx`, `.xls`) o CSV conteniendo el catálogo de carreras profesionales y su respectiva sede.
          </p>

          <q-btn
            outline no-caps icon="download" label="Descargar Plantilla Base (CSV/Excel)"
            color="indigo-3" class="full-width q-mb-md" @click="descargarPlantilla"
          />

          <q-file
            v-model="archivoExcel"
            label="Seleccionar archivo de Carreras"
            outlined dark dense
            accept=".xlsx, .xls, .csv, .txt"
          >
            <template #prepend>
              <q-icon name="attach_file" />
            </template>
          </q-file>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat label="Cancelar" color="grey-5" v-close-popup />
          <q-btn
            color="positive" icon="cloud_upload" label="Procesar Importación"
            :loading="importing" :disable="!archivoExcel"
            @click="procesarImportacion" unelevated
          />
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
const importing = ref(false)
const carreras = ref([])
const modalOpen = ref(false)
const modalImportOpen = ref(false)
const isEditing = ref(false)
const selectedId = ref(null)
const archivoExcel = ref(null)

const sedesDisponibles = [
  'Cochabamba',
  'Ivirgarzama',
  'Santa Cruz',
  'Puerto Quijarro',
  'Guayaramerin',
  'Cobija',
  'La Paz',
  'El Alto',
]

const form = reactive({
  nombre: '',
  sigla: '',
  sede: 'Cochabamba',
  descripcion: '',
})

const columns = [
  { name: 'sigla', label: 'Sigla', field: 'sigla', align: 'left' },
  { name: 'nombre', label: 'Nombre de la Carrera', field: 'nombre', align: 'left', sortable: true },
  { name: 'sede', label: 'Sede Universitaria', field: 'sede', align: 'left', sortable: true },
  { name: 'descripcion', label: 'Descripción', field: 'descripcion', align: 'left' },
  { name: 'acciones', label: 'Acciones', field: 'acciones', align: 'right' },
]

async function cargarCarreras() {
  loading.value = true
  try {
    const res = await api.get('/admin/carreras')
    carreras.value = res.data
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al cargar carreras', position: 'top' })
  } finally {
    loading.value = false
  }
}

function abrirCrearModal() {
  isEditing.value = false
  selectedId.value = null
  form.nombre = ''
  form.sigla = ''
  form.sede = 'Cochabamba'
  form.descripcion = ''
  modalOpen.value = true
}

function abrirEditarModal(row) {
  isEditing.value = true
  selectedId.value = row.id
  form.nombre = row.nombre
  form.sigla = row.sigla || ''
  form.sede = row.sede || 'Cochabamba'
  form.descripcion = row.descripcion || ''
  modalOpen.value = true
}

async function guardarCarrera() {
  if (!form.nombre) {
    $q.notify({ type: 'warning', message: 'El nombre es obligatorio', position: 'top' })
    return
  }
  saving.value = true
  try {
    if (isEditing.value) {
      await api.put(`/admin/carreras/${selectedId.value}`, form)
    } else {
      await api.post('/admin/carreras', form)
    }
    $q.notify({ type: 'positive', message: '✅ Carrera guardada con éxito.', position: 'top' })
    modalOpen.value = false
    cargarCarreras()
  } catch (e) {
    $q.notify({ type: 'negative', message: 'Error al guardar carrera.', position: 'top' })
  } finally {
    saving.value = false
  }
}

function descargarPlantilla() {
  window.open('/api/admin/carreras/descargar-plantilla', '_blank')
}

async function procesarImportacion() {
  if (!archivoExcel.value) return
  importing.value = true
  try {
    const formData = new FormData()
    formData.append('archivo', archivoExcel.value)

    const res = await api.post('/admin/carreras/importar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    $q.notify({
      type: 'positive',
      message: `✅ ${res.data.message} (${res.data.creadas} creadas, ${res.data.actualizadas} actualizadas)`,
      position: 'top',
    })

    modalImportOpen.value = false
    archivoExcel.value = null
    cargarCarreras()
  } catch (e) {
    const msg = e.response?.data?.message || 'Error al procesar el archivo Excel.'
    $q.notify({ type: 'negative', message: msg, position: 'top' })
  } finally {
    importing.value = false
  }
}

function eliminarCarrera(row) {
  $q.dialog({
    title: 'Eliminar Carrera',
    message: `¿Eliminar la carrera "${row.nombre}" (${row.sede})?`,
    cancel: true, dark: true,
  }).onOk(async () => {
    try {
      await api.delete(`/admin/carreras/${row.id}`)
      $q.notify({ type: 'positive', message: 'Carrera eliminada.', position: 'top' })
      cargarCarreras()
    } catch (e) {
      $q.notify({ type: 'negative', message: 'Error al eliminar carrera.', position: 'top' })
    }
  })
}

onMounted(cargarCarreras)
</script>

<style lang="scss" scoped>
.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.1);
  border-radius: 14px;
}

.action-btn {
  border-radius: 8px !important;
  font-weight: 600 !important;
}
</style>
