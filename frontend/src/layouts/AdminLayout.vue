<template>
  <q-layout view="lHh Lpr lFf" class="bg-dark-surface">
    <!-- Sidebar -->
    <q-drawer v-model="leftDrawerOpen" show-if-above dark :width="270" class="admin-sidebar">
      <div class="sidebar-header q-pa-lg">
        <div class="q-mb-md text-center">
          <img src="/logo-unitepc.png" style="width: 100%; max-width: 170px; background: #ffffff; padding: 6px 12px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.3);" alt="UNITEPC Logo" />
        </div>
        <div class="text-weight-bold text-white" style="font-size: 1rem;">Panel Administración</div>
        <div style="font-size: 0.75rem; color: rgba(255,255,255,0.4); margin-top: 2px;">{{ admin?.nombre || 'Administrador' }}</div>
      </div>

      <q-scroll-area style="height: calc(100% - 190px);">
        <q-list class="q-px-md q-py-sm">
          <q-item
            v-for="item in menuItems"
            :key="item.to"
            clickable
            :to="item.to"
            active-class="menu-active"
            class="menu-item q-mb-xs"
            style="border-radius: 10px;"
          >
            <q-item-section avatar style="min-width: 36px;">
              <q-icon :name="item.icon" size="20px" />
            </q-item-section>
            <q-item-section>
              <q-item-label class="text-weight-medium" style="font-size: 0.85rem;">
                {{ item.label }}
              </q-item-label>
            </q-item-section>
          </q-item>
        </q-list>
      </q-scroll-area>

      <div class="q-pa-md" style="position: absolute; bottom: 0; left: 0; right: 0; background: #0a0f1e;">
        <q-btn
          flat
          class="full-width logout-btn"
          icon="logout"
          label="Cerrar sesión"
          @click="handleLogout"
          align="left"
          style="border-radius: 10px;"
        />
      </div>
    </q-drawer>

    <!-- Header -->
    <q-header elevated class="header-glass">
      <q-toolbar class="q-px-lg" style="height: 64px;">
        <q-btn flat round dense icon="menu" color="white" @click="leftDrawerOpen = !leftDrawerOpen" class="q-mr-sm" />

        <div class="text-weight-semibold text-white" style="font-size: 1rem;">
          {{ currentPageTitle }}
        </div>

        <q-space />

        <div class="flex items-center gap-2">
          <!-- Botón Importar Estudiantes -->
          <q-btn
            no-caps
            color="indigo-7"
            icon="group_add"
            label="Importar Estudiantes"
            class="import-btn"
            @click="modalEstudiantesOpen = true"
            unelevated
          />

          <!-- Botón Importar Excel Horarios -->
          <q-btn
            no-caps
            color="positive"
            icon="upload_file"
            label="Importar Horarios"
            class="import-btn"
            @click="modalImportarOpen = true"
            unelevated
          />

          <div class="flex items-center gap-2 gt-sm q-ml-sm">
            <q-icon name="calendar_today" color="blue-3" size="18px" />
            <span style="color: #93c5fd; font-size: 0.85rem;">{{ fechaHoy }}</span>
          </div>
        </div>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <router-view />
    </q-page-container>

    <!-- Modal Importar Estudiantes e Inscripciones -->
    <q-dialog v-model="modalEstudiantesOpen" persistent>
      <q-card style="width: 580px; max-width: 95vw;" class="glass-dialog dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="person_add_alt_1" color="indigo-4" size="28px" />
            <div>
              <div class="text-h6 text-weight-bold text-white">Importar Estudiantes e Inscripciones</div>
              <div class="text-caption text-grey-4">Registrar nómina y vincular por Carrera</div>
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <!-- Seleccionar Carrera para Importación -->
          <div class="q-mb-md">
            <label class="text-caption text-indigo-3 text-weight-bold">Carrera Destino:</label>
            <q-select
              v-model="carreraImportEstudiantes"
              :options="carreras"
              option-value="id"
              option-label="nombre"
              emit-value map-options
              outlined dark dense
            />
          </div>

          <!-- Botón Descargar Plantilla Estudiantes -->
          <q-btn
            outline
            no-caps
            color="indigo-3"
            icon="download"
            label="📥 Descargar Formato Estudiantes (.csv)"
            class="full-width q-mb-md template-modal-btn"
            @click="descargarPlantillaEstudiantes"
          />

          <!-- Banner instructivo -->
          <div class="info-banner q-mb-md" style="background: rgba(99, 102, 241, 0.1); border-color: rgba(99, 102, 241, 0.2);">
            <div class="text-weight-semibold text-indigo-3 q-mb-xs">📋 Formato de Columnas Esperado:</div>
            <div class="text-caption text-grey-3">
              El archivo (.xlsx o .csv) debe contener exactamente las columnas: <br>
              <strong>CODIGO MATERIA | CODIGO ESTUDIANTE | NOMBRES | 1er. Apellido | 2do. Apellido</strong>
            </div>
          </div>

          <!-- Selector de archivo -->
          <q-file
            v-model="archivoEstudiantes"
            label="Seleccionar Excel de Estudiantes (.xlsx / .csv)"
            outlined dark
            accept=".xlsx, .xls, .csv"
            clearable
            class="q-mb-md"
          >
            <template #prepend>
              <q-icon name="attach_file" color="indigo-4" />
            </template>
          </q-file>

          <!-- Resultado de importación -->
          <q-banner v-if="resultadoEstudiantes" class="success-banner q-mb-md" rounded>
            <template #avatar>
              <q-icon name="check_circle" color="positive" size="24px" />
            </template>
            <div class="text-weight-bold text-positive">{{ resultadoEstudiantes.message }}</div>
            <div class="text-caption text-grey-3 q-mt-xs">
              • Estudiantes registrados: <strong>{{ resultadoEstudiantes.estudiantes_creados }}</strong><br>
              • Inscripciones en materias: <strong>{{ resultadoEstudiantes.inscripciones_creadas }}</strong>
            </div>
          </q-banner>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat no-caps label="Cancelar" color="grey-5" v-close-popup />
          <q-btn
            no-caps
            color="indigo-7"
            icon="group_add"
            label="Procesar e Inscribir"
            :loading="importingEstudiantes"
            :disable="!archivoEstudiantes"
            @click="procesarImportacionEstudiantes"
            unelevated
          />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- Modal Importar Horarios -->
    <q-dialog v-model="modalImportarOpen" persistent>
      <q-card style="width: 560px; max-width: 95vw;" class="glass-dialog dark-card">
        <q-card-section class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <q-icon name="drive_folder_upload" color="positive" size="28px" />
            <div>
              <div class="text-h6 text-weight-bold text-white">Importar Horarios y Docentes</div>
              <div class="text-caption text-grey-4">Cargar Materias, Docentes y Horarios por Carrera</div>
            </div>
          </div>
          <q-btn flat round dense icon="close" color="grey-5" v-close-popup />
        </q-card-section>

        <q-card-section class="q-pt-none">
          <!-- Seleccionar Carrera para Importación -->
          <div class="q-mb-md">
            <label class="text-caption text-blue-3 text-weight-bold">Carrera Destino:</label>
            <q-select
              v-model="carreraImportHorarios"
              :options="carreras"
              option-value="id"
              option-label="nombre"
              emit-value map-options
              outlined dark dense
            />
          </div>

          <!-- Botón Descargar Plantilla -->
          <q-btn
            outline
            no-caps
            color="blue-4"
            icon="download"
            label="📥 Descargar Formato Horarios (.csv)"
            class="full-width q-mb-md template-modal-btn"
            @click="descargarPlantilla"
          />

          <!-- Banner instructivo -->
          <div class="info-banner q-mb-md">
            <div class="text-weight-semibold text-blue-3 q-mb-xs">📋 Formato de Columnas Esperado:</div>
            <div class="text-caption text-grey-3">
              El archivo (.xlsx, .xls o .csv) debe contener las columnas: <br>
              <strong>Sigla | Materia | Tipo | Día | Hora Inicio | Hora Fin | Aula | Nombre Docente | CI Docente</strong>
            </div>
          </div>

          <!-- Selector de archivo -->
          <q-file
            v-model="archivoExcel"
            label="Seleccionar archivo Excel (.xlsx / .csv)"
            outlined dark
            accept=".xlsx, .xls, .csv"
            clearable
            class="q-mb-md"
          >
            <template #prepend>
              <q-icon name="attach_file" color="positive" />
            </template>
          </q-file>

          <!-- Resultado de importación -->
          <q-banner v-if="resultadoImportacion" class="success-banner q-mb-md" rounded>
            <template #avatar>
              <q-icon name="check_circle" color="positive" size="24px" />
            </template>
            <div class="text-weight-bold text-positive">{{ resultadoImportacion.message }}</div>
            <div class="text-caption text-grey-3 q-mt-xs">
              • Docentes creados: <strong>{{ resultadoImportacion.docentes_creados }}</strong><br>
              • Materias creadas: <strong>{{ resultadoImportacion.materias_creadas }}</strong><br>
              • Horarios registrados: <strong>{{ resultadoImportacion.horarios_creados }}</strong>
            </div>
          </q-banner>
        </q-card-section>

        <q-card-actions align="right" class="q-px-md q-pb-md">
          <q-btn flat no-caps label="Cancelar" color="grey-5" v-close-popup />
          <q-btn
            no-caps
            color="positive"
            icon="cloud_upload"
            label="Procesar e Importar"
            :loading="importing"
            :disable="!archivoExcel"
            @click="procesarImportacion"
            unelevated
          />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-layout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from 'stores/auth'
import { useQuasar } from 'quasar'
import { api } from 'boot/axios'

const $q = useQuasar()
const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const leftDrawerOpen = ref(true)
const admin = computed(() => authStore.user)

const carreras = ref([])
const carreraImportHorarios = ref(null)
const carreraImportEstudiantes = ref(null)

const modalImportarOpen = ref(false)
const archivoExcel = ref(null)
const importing = ref(false)
const resultadoImportacion = ref(null)

const modalEstudiantesOpen = ref(false)
const archivoEstudiantes = ref(null)
const importingEstudiantes = ref(false)
const resultadoEstudiantes = ref(null)

const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']
const ahora = new Date()
const fechaHoy = computed(() => `${ahora.getDate()} de ${meses[ahora.getMonth()]} ${ahora.getFullYear()}`)

const menuItems = [
  { to: '/admin/dashboard', icon: 'dashboard', label: 'Dashboard' },
  { to: '/admin/historial-estudiante', icon: 'person_search', label: 'Historial por Estudiante' },
  { to: '/admin/asistencias', icon: 'edit_note', label: 'Edición de Asistencias' },
  { to: '/admin/horarios-carrera', icon: 'grid_view', label: 'Horarios por Carrera' },
  { to: '/admin/estudiantes', icon: 'people', label: 'Gestión de Estudiantes' },
  { to: '/admin/docentes-gestion', icon: 'badge', label: 'Gestión de Docentes' },
  { to: '/admin/horarios-gestion', icon: 'schedule', label: 'Horarios y Aulas' },
  { to: '/admin/carreras', icon: 'school', label: 'Carreras' },
  { to: '/admin/administradores', icon: 'admin_panel_settings', label: 'Directores y Admins' },
  { to: '/admin/docentes-sin-marcar', icon: 'fact_check', label: 'Docentes sin marcar' },
  { to: '/admin/faltas', icon: 'person_off', label: 'Estudiantes con faltas' },
  { to: '/admin/reportes', icon: 'assessment', label: 'Reportes por fechas' },
]

const pageTitles = {
  'admin-dashboard': 'Dashboard General',
  'admin-historial-estudiante': 'Historial de Asistencia por Estudiante',
  'admin-asistencias': 'Edición de Asistencias Registradas',
  'admin-horarios-carrera': 'Horarios por Carrera',
  'admin-estudiantes': 'Gestión de Estudiantes e Inscripciones',
  'admin-docentes-gestion': 'Gestión del Plantel Docente',
  'admin-horarios-gestion': 'Gestión de Horarios y Aulas',
  'admin-carreras': 'Gestión de Carreras Universitarias',
  'admin-administradores': 'Gestión de Directores y SuperAdmins',
  'admin-docentes': 'Docentes sin marcar asistencia',
  'admin-faltas': 'Estudiantes con más de 2 faltas',
  'admin-reportes': 'Reportes por fechas',
}
const currentPageTitle = computed(() => pageTitles[route.name] || 'Panel Administración')

async function cargarCarreras() {
  try {
    const res = await api.get('/admin/carreras')
    carreras.value = res.data
    if (carreras.value.length > 0) {
      carreraImportHorarios.value = carreras.value[0].id
      carreraImportEstudiantes.value = carreras.value[0].id
    }
  } catch (e) {
    console.error(e)
  }
}

function descargarPlantilla() {
  window.open('/api/admin/descargar-plantilla', '_blank')
}

function descargarPlantillaEstudiantes() {
  window.open('/api/admin/descargar-plantilla-estudiantes', '_blank')
}

async function procesarImportacion() {
  if (!archivoExcel.value) return

  importing.value = true
  resultadoImportacion.value = null

  try {
    const formData = new FormData()
    formData.append('archivo', archivoExcel.value)
    if (carreraImportHorarios.value) {
      formData.append('carrera_id', carreraImportHorarios.value)
    }

    const res = await api.post('/admin/importar-excel', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    resultadoImportacion.value = res.data
    $q.notify({
      type: 'positive',
      message: '✅ Importación de horarios completada exitosamente.',
      position: 'top',
    })
    
    setTimeout(() => {
      window.location.reload()
    }, 2000)
  } catch (e) {
    const msg = e.response?.data?.message || 'Error al procesar el archivo Excel.'
    $q.notify({
      type: 'negative',
      message: msg,
      position: 'top',
    })
  } finally {
    importing.value = false
  }
}

async function procesarImportacionEstudiantes() {
  if (!archivoEstudiantes.value) return

  importingEstudiantes.value = true
  resultadoEstudiantes.value = null

  try {
    const formData = new FormData()
    formData.append('archivo', archivoEstudiantes.value)
    if (carreraImportEstudiantes.value) {
      formData.append('carrera_id', carreraImportEstudiantes.value)
    }

    const res = await api.post('/admin/importar-estudiantes', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    resultadoEstudiantes.value = res.data
    $q.notify({
      type: 'positive',
      message: '✅ Importación de estudiantes e inscripciones completada exitosamente.',
      position: 'top',
    })
    
    setTimeout(() => {
      window.location.reload()
    }, 2000)
  } catch (e) {
    const msg = e.response?.data?.message || 'Error al procesar el archivo de estudiantes.'
    $q.notify({
      type: 'negative',
      message: msg,
      position: 'top',
    })
  } finally {
    importingEstudiantes.value = false
  }
}

function handleLogout() {
  $q.dialog({
    title: 'Cerrar sesión',
    message: '¿Deseas cerrar tu sesión del panel?',
    cancel: true,
    ok: { label: 'Sí, salir', color: 'negative', flat: true },
    cancel: { label: 'Cancelar', flat: true },
    dark: true,
  }).onOk(() => {
    authStore.logout()
    router.push({ name: 'login-admin' })
  })
}

onMounted(cargarCarreras)
</script>

<style lang="scss" scoped>
.bg-dark-surface { background: var(--color-surface); }

.header-glass {
  background: rgba(15, 23, 42, 0.9) !important;
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.import-btn {
  border-radius: 10px !important;
  font-weight: 700 !important;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.25) !important;
}

.template-modal-btn {
  border-radius: 10px !important;
  font-weight: 600 !important;
  border: 1px dashed rgba(99, 102, 241, 0.4) !important;
  background: rgba(99, 102, 241, 0.05) !important;
}

.admin-sidebar {
  background: #0a0f1e !important;
  border-right: 1px solid rgba(148, 163, 184, 0.08) !important;
}

:deep(.q-drawer),
:deep(.q-drawer__content) {
  background: #0a0f1e !important;
}

.sidebar-header {
  border-bottom: 1px solid rgba(148, 163, 184, 0.08);
}

.menu-item {
  color: rgba(148, 163, 184, 0.8);
  transition: all 0.2s;
  &:hover {
    background: rgba(59, 130, 246, 0.1) !important;
    color: #93c5fd;
  }
}

.menu-active {
  background: rgba(59, 130, 246, 0.15) !important;
  color: #60a5fa !important;
  border: 1px solid rgba(59, 130, 246, 0.2);
}

.logout-btn {
  color: rgba(148, 163, 184, 0.6) !important;
  &:hover {
    color: #f87171 !important;
    background: rgba(239, 68, 68, 0.1) !important;
  }
}

.dark-card {
  background: rgba(15, 23, 42, 0.95) !important;
  border: 1px solid rgba(148, 163, 184, 0.2);
  border-radius: 16px;
}

.info-banner {
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  padding: 12px 16px;
  border-radius: 10px;
}

.success-banner {
  background: rgba(16, 185, 129, 0.1) !important;
  border: 1px solid rgba(16, 185, 129, 0.2) !important;
}
</style>
