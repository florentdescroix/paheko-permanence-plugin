import router from '../router'
// Plugins
import vuetify from './vuetify'

export function registerPlugins (app) {
  app.use(vuetify)
  app.use(router)
}
