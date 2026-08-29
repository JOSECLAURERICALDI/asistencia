import { createRouter, createWebHashHistory } from 'vue-router'
import routes from './routes'

export default function () {
  const Router = createRouter({
    scrollBehavior: () => ({ left: 0, top: 0 }),
    routes,
    history: createWebHashHistory(),
  })

  Router.beforeEach((to, from, next) => {
    let token = localStorage.getItem('token')
    let role = localStorage.getItem('role')

    // Fallback de demostración para ver vistas directamente
    if (!token) {
      if (to.path.startsWith('/admin')) {
        token = 'demo-admin-token'
        role = 'admin'
        localStorage.setItem('token', token)
        localStorage.setItem('role', role)
        localStorage.setItem('user', JSON.stringify({
          id: 1,
          nombre: 'Director de Carrera',
          email: 'director@carsis.edu.bo',
          carrera_id: 1
        }))
      } else if (to.path !== '/login' && to.path !== '/admin/login') {
        token = 'demo-docente-token'
        role = 'docente'
        localStorage.setItem('token', token)
        localStorage.setItem('role', role)
        localStorage.setItem('user', JSON.stringify({
          id: 1,
          nombre: 'MAMANI QUISPE JUAN CARLOS',
          ci: '3456789'
        }))
      }
    }

    const isAuthenticated = !!token

    // Guard protected routes
    if (to.meta.requiresAuth && !isAuthenticated) {
      return next({ name: 'login' })
    }

    if (to.meta.requiresAdmin && role !== 'admin') {
      return next({ name: 'login-admin' })
    }

    if (to.meta.requiresDocente && role !== 'docente') {
      return next({ name: 'login' })
    }

    next()
  })

  return Router
}
