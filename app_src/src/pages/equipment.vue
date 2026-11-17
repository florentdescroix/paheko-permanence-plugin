<template>

  <div class="d-flex align-center">
    <v-spacer />
    <v-switch v-model="displayAll" color="primary" label="Afficher l'historique" />
  </div>

  <v-expansion-panels class="mb-5" elevation="0" variant="accordion">
    <Tool
      v-for="eq,i in Equipment.equipments"
      :key="eq.id"
      v-model="Equipment.equipments[i]"
      :display-all="displayAll"
    />
  </v-expansion-panels>
</template>

<route lang="json">
{
  "name": "Matériel",
  "meta": {
    "order": 3,
    "admin": false,
    "icon": "mdi-circular-saw"
  }
}
</route>

<script lang="ts" setup>
  import { ref } from 'vue'
  import Tool from '../components/Tool.vue'
  import { Equipment } from '../store/index.js'

  const displayAll = ref(false)
</script>
