/**
 * plugins/vuetify.js
 *
 * Framework documentation: https://vuetifyjs.com`
 */

// Composables
import { createVuetify } from 'vuetify'
// Styles
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

const customColors = {
  savon: '#CB6CE6',
  electronique: '#C9E265',
  couture: '#FF5757',
  mosaique: '#00C2CB',
  bois: '#004AAD',
  cuir: '#FFDE59',
  velo: '#FF914D',
  tricot: '#FF66C4',
}
customColors.primary = customColors.bois
customColors.info = customColors.mosaique
customColors.success = customColors.electronique
customColors.warning = customColors.velo
customColors.error = customColors.couture
customColors.yellow = customColors.cuir

// https://vuetifyjs.com/en/introduction/why-vuetify/#feature-guides
export default createVuetify({
  locale: {
    locale: 'fr',
  },
  date: {
    locale: {
      fr: 'fr-FR',
    },
    formats: {
      ISODate: 'yyyy-mm-dd',
    },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        colors: customColors,
      },
      dark: {
        colors: customColors,
      },
    },
  },
  defaults: {
    global: {
      hideDetails: true,
    },
    VBtn: {
      color: 'primary',
      size: 'large',
    },
    VCard: {
      elevation: 6,
    },
    VSelect: {
      itemValue: e => e,
    },
    VAutocomplete: {
      itemValue: e => e,
    },
  },
})
