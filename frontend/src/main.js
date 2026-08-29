import { createApp } from 'vue'
import { Quasar, Notify, Dialog, Loading } from 'quasar'

import '@quasar/extras/roboto-font/roboto-font.css'
import '@quasar/extras/material-icons/material-icons.css'
import 'quasar/src/css/index.sass'
import './css/app.css'

import App from './App.vue'
import createRouter from './router'
import { createPinia } from 'pinia'
import axiosBoot from './boot/axios'

const pinia = createPinia()
const router = createRouter()

const myApp = createApp(App)

myApp.use(pinia)
myApp.use(router)

myApp.use(Quasar, {
  plugins: {
    Notify,
    Dialog,
    Loading,
  },
  config: {
    notify: {
      position: 'top-right',
    }
  }
})

axiosBoot({ app: myApp })

myApp.mount('#q-app')
