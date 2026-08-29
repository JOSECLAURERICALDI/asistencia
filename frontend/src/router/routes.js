import LoginDocente from '../pages/LoginDocente.vue'
import LoginAdmin from '../pages/LoginAdmin.vue'
import DocenteLayout from '../layouts/DocenteLayout.vue'
import AdminLayout from '../layouts/AdminLayout.vue'
import MateriasDelDia from '../pages/MateriasDelDia.vue'
import NominaEstudiantes from '../pages/NominaEstudiantes.vue'
import Dashboard from '../pages/admin/Dashboard.vue'
import DocentesSinMarcar from '../pages/admin/DocentesSinMarcar.vue'
import EstudiantesFaltas from '../pages/admin/EstudiantesFaltas.vue'
import Reportes from '../pages/admin/Reportes.vue'
import HorariosCarrera from '../pages/HorariosCarrera.vue'
import AsistenciasEdicion from '../pages/admin/AsistenciasEdicion.vue'
import GestionAdministradores from '../pages/admin/GestionAdministradores.vue'
import GestionCarreras from '../pages/admin/GestionCarreras.vue'
import GestionEstudiantes from '../pages/admin/GestionEstudiantes.vue'
import GestionDocentes from '../pages/admin/GestionDocentes.vue'
import GestionHorarios from '../pages/admin/GestionHorarios.vue'
import HistorialEstudiante from '../pages/admin/HistorialEstudiante.vue'
import ErrorNotFound from '../pages/ErrorNotFound.vue'

const routes = [
  // ── DOCENTE ────────────────────────────────────────────────
  {
    path: '/login',
    name: 'login',
    component: LoginDocente,
    meta: { guest: true },
  },
  {
    path: '/',
    component: DocenteLayout,
    meta: { requiresAuth: true, requiresDocente: true },
    children: [
      {
        path: '',
        redirect: '/materias',
      },
      {
        path: 'materias',
        name: 'materias',
        component: MateriasDelDia,
      },
      {
        path: 'nomina/:horarioId',
        name: 'nomina',
        component: NominaEstudiantes,
        props: true,
      },
    ],
  },

  // ── ADMIN / DIRECCIÓN ────────────────────────────────────────
  {
    path: '/admin/login',
    name: 'login-admin',
    component: LoginAdmin,
    meta: { guest: true },
  },
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true, requiresAdmin: true },
    children: [
      {
        path: '',
        redirect: '/admin/dashboard',
      },
      {
        path: 'dashboard',
        name: 'admin-dashboard',
        component: Dashboard,
      },
      {
        path: 'historial-estudiante',
        name: 'admin-historial-estudiante',
        component: HistorialEstudiante,
      },
      {
        path: 'horarios-carrera',
        name: 'admin-horarios-carrera',
        component: HorariosCarrera,
      },
      {
        path: 'asistencias',
        name: 'admin-asistencias',
        component: AsistenciasEdicion,
      },
      {
        path: 'estudiantes',
        name: 'admin-estudiantes',
        component: GestionEstudiantes,
      },
      {
        path: 'docentes-gestion',
        name: 'admin-docentes-gestion',
        component: GestionDocentes,
      },
      {
        path: 'horarios-gestion',
        name: 'admin-horarios-gestion',
        component: GestionHorarios,
      },
      {
        path: 'administradores',
        name: 'admin-administradores',
        component: GestionAdministradores,
      },
      {
        path: 'carreras',
        name: 'admin-carreras',
        component: GestionCarreras,
      },
      {
        path: 'docentes-sin-marcar',
        name: 'admin-docentes',
        component: DocentesSinMarcar,
      },
      {
        path: 'faltas',
        name: 'admin-faltas',
        component: EstudiantesFaltas,
      },
      {
        path: 'reportes',
        name: 'admin-reportes',
        component: Reportes,
      },
    ],
  },

  // 404
  {
    path: '/:catchAll(.*)*',
    component: ErrorNotFound,
  },
]

export default routes
