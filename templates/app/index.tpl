<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/vuetify@4.1.7/dist/vuetify.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@latest/css/materialdesignicons.min.css" />
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=roboto:400,500,700" />
  </head>
  <body>
    <div id="app">
      <v-app>
        <v-container>
          <v-text-field label="My field" />
        </v-container>
      </v-app>
    </div>

    <script type="importmap">
        {
            "imports": {
            "vue": "https://cdn.jsdelivr.net/npm/vue@latest/dist/vue.esm-browser.js",
            "vuetify": "https://cdn.jsdelivr.net/npm/vuetify@4.1.7/dist/vuetify.esm.js"
            }
        }
    </script>
        
    <script>
      const { createApp } = Vue
      const { createVuetify } = Vuetify
      const vuetify = createVuetify()
      const app = createApp()
      app.use(vuetify).mount('#app')
    </script>
  </body>
</html>