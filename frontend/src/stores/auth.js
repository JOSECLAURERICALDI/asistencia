import { defineStore } from 'pinia'
import { api } from 'boot/axios'

const defaultUser = { id: 1, ci: '3456789', nombre: 'JUAN CARLOS', apellido: 'MAMANI QUISPE' }

function getSavedUser() {
  try {
    const raw = localStorage.getItem('user')
    if (raw && raw !== 'null' && raw !== 'undefined') {
      return JSON.parse(raw)
    }
  } catch (e) {}
  return defaultUser
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || 'demo-admin-token',
    user: getSavedUser(),
    role: localStorage.getItem('role') || 'admin',
  }),

  getters: {
    isAuthenticated: (state) => !!state.token,
    isDocente: (state) => state.role === 'docente',
    isAdmin: (state) => state.role === 'admin',
  },

  actions: {
    async loginDocente(ci) {
      const res = await api.post('/docente/login', { ci })
      this.setAuth(res.data.token, res.data.docente, 'docente')
      return res.data
    },

    async loginAdmin(email, password) {
      const res = await api.post('/admin/login', { email, password })
      this.setAuth(res.data.token, res.data.admin, 'admin')
      return res.data
    },

    setAuth(token, user, role) {
      this.token = token
      this.user = user
      this.role = role
      localStorage.setItem('token', token)
      localStorage.setItem('user', JSON.stringify(user))
      localStorage.setItem('role', role)
      api.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },

    logout() {
      const endpoint = this.role === 'docente' ? '/docente/logout' : '/admin/logout'
      api.post(endpoint).catch(() => {})
      this.token = null
      this.user = null
      this.role = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('role')
      delete api.defaults.headers.common['Authorization']
    },

    initAuth() {
      if (this.token) {
        api.defaults.headers.common['Authorization'] = `Bearer ${this.token}`
      }
    },
  },
})
