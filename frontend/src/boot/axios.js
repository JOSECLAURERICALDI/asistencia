import axios from 'axios'

const isRemote = typeof window !== 'undefined' && window.location.hostname.includes('xpertiaplus.com')

const api = axios.create({
  baseURL: isRemote ? 'https://api.asistencia.xpertiaplus.com/api' : '/api',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

// Inject stored token on every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
  }
  return config
})

// Handle 401 globally without redirect loops
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      localStorage.removeItem('role')
      if (!window.location.hash.includes('login')) {
        window.location.href = '/#/login'
      }
    }
    return Promise.reject(error)
  }
)

export { api }
export default ({ app }) => {
  app.config.globalProperties.$api = api
}
