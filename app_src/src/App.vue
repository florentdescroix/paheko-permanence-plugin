<template>
  <v-app class="d-flex justify-center bg-transparent">
    <div id="background">
      <div class="bg-savon" />
      <div class="bg-electronique" />
      <div class="bg-couture" />
      <div class="bg-mosaique" />
      <div class="bg-bois" />
      <div class="bg-cuir" />
      <div class="bg-velo" />
      <div class="bg-tricot" />
    </div>

    <v-app-bar>
      <router-link class="mx-3 text-center" to="/">
        <img
          v-if="Organization.config?.files?.logo"
          class="d-block"
          :src="Organization.config.files.logo"
          style="height: 40px;"
        >
      </router-link>

      <v-spacer />

      <template v-if="user">
        Permanence ouverte par&nbsp;<b>{{ user.identity }}</b>
        <v-icon v-tooltip="'changer'" class="ml-3" icon="mdi-logout" @click="user = undefined" />
      </template>

      <v-spacer />

      <div class="mx-3">{{ now.toLocaleString([], {
        weekday: "long", day: '2-digit', month: 'short',
        hour: '2-digit', minute: '2-digit'
      }) }}</div>
    </v-app-bar>

    <v-navigation-drawer border="0" class="bg-transparent pa-4" width="350">
      <v-card>
        <v-card-title>Permanence</v-card-title>
        <v-list :items="pagesPerm" />
      </v-card>

      <v-card class="mt-4">
        <v-card-title>Administration</v-card-title>
        <v-list :items="pagesAdmin" />
      </v-card>
    </v-navigation-drawer>

    <v-main v-if="user">
      <v-container class="pa-4" max-width="900">
        <router-view v-slot="{ Component }">
          <component :is="Component" v-if="$route.meta.nocard" />

          <v-card v-else class="overflow-auto">
            <v-card-text>
              <component :is="Component" />
            </v-card-text>
          </v-card>
        </router-view>
      </v-container>
    </v-main>

    <OnDutyDialog :model-value="!user" />
    <IdleDialog v-model="idle" />

    <v-dialog v-model="errorDialog" width="400">
      <v-alert
        v-model="errorDialog"
        closable
        color="error"
        title="Erreur"
      >
        {{ errorMessage }}
        <template #close="{props}">
          <v-icon v-bind="props" icon="mdi-close-circle" size="large" />
        </template>
      </v-alert>
    </v-dialog>

  </v-app>
</template>

<script lang="ts" setup>
  import { onErrorCaptured, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import IdleDialog from './dialogs/Idle.vue'
  import OnDutyDialog from './dialogs/OnDuty.vue'
  import { Organization, user } from './store/index.js'

  const router = useRouter()

  const errorDialog = ref(false)
  const errorMessage = ref('')
  const idle = ref(false)

  const now = ref(new Date())

  const pagesPerm = []
  const pagesAdmin = []

  let lastInteration = Date.now()

  document.addEventListener('mousemove', () => {
    lastInteration = Date.now()
  })

  for (const route of router.options.routes) {
    addRouteToMenu(route)
  }
  pagesPerm.sort((a, b) => a.order < b.order ? -1 : 1)
  pagesAdmin.sort((a, b) => a.order < b.order ? -1 : 1)

  onErrorCaptured(error => {
    errorMessage.value = error.message
    errorDialog.value = true
  })

  setInterval(() => {
    now.value = new Date()
    if (user.value && now.value.getTime() - lastInteration >= (20 * 60 * 1000)) {
      idle.value = true
    }
    if (user.value && now.value.getTime() - lastInteration >= (120 * 60 * 1000)) {
      idle.value = false
      user.value = undefined
    }
  }, 1000)

  function addRouteToMenu (route) {
    if (route?.meta) {
      const target = route?.meta?.admin ? pagesAdmin : pagesPerm
      target.push({
        title: route.name,
        order: route.meta.order,
        props: { to: route.path, prependIcon: route.meta.icon },
      })
    }
    if (route.children) {
      for (const child of route.children) {
        addRouteToMenu({ ...child, path: route.path + '/' + child.path })
      }
    }
  }
</script>

<style lang="scss">
@import url('@/assets/fonts.scss');

#background {
  position: fixed;
  width: 100vw;
  height: calc(100vh - 64px);
  z-index: 0;
  top: 64px;
  left: 0;
  overflow-y: scroll;
}

#background div {
  float: left;
  height: 50%;
  width: 25%;
}
</style>
