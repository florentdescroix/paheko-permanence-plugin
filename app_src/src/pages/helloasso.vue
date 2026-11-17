<template>

  <template v-for="form in HelloAsso.forms" :key="form.id">
    <h3 class="text-center">{{ form.name }}</h3>

    <v-expansion-panels class="mb-5" multiple>
      <v-expansion-panel v-for="tier in form.tiers" :key="tier.id">

        <v-expansion-panel-title>
          <strong>{{ tier.label }}</strong>&nbsp;({{ tier.items.length }})
        </v-expansion-panel-title>

        <v-expansion-panel-text>
          <v-table>
            <v-row v-for="item of tier.items" :key="item.id" density="comfortable">
              <v-col cols="5">
                <v-icon :color="!item.matching_user ? 'error' : (item.matching_user.active ? 'success' : 'warning')" icon="mdi-circle" />
                {{ item.person }}
              </v-col>

              <v-col>{{ item?.matching_user?.phone }}</v-col>
            </v-row>
          </v-table>
        </v-expansion-panel-text>

      </v-expansion-panel>
    </v-expansion-panels>

  </template>

  <!-- <v-row class="mt-8">
    <v-col class="ma-auto" cols="12">
      <v-btn :disabled="loading" width="100%" @click="sync()">
        <v-progress-circular v-if="loading" indeterminate />
        <template v-else>Recharger</template>
      </v-btn>
    </v-col>
  </v-row> -->
</template>

<route lang="json">
{
  "name": "HelloAsso",
  "meta": {
    "order": 3,
    "admin": true,
    "icon": "mdi-alpha-h-circle-outline"
  }
}
</route>

<script lang="ts" setup>
  // import { ref } from 'vue'
  import { HelloAsso } from '../store/index.js'

  // const loading = ref(false)

  // async function sync () {
  //   loading.value = true
  //   await HelloAsso.sync()
  //   loading.value = false
  // }
</script>
