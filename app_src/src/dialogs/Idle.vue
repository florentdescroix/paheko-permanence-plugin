<template>
  <v-dialog max-width="600" :model-value="model && !!user" persistent>
    <v-card>
      <v-card-text>
        <strong>{{ user?.identity }}</strong> c'est toujours toi ?
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn text="Oui" @click="model = false;" />
        <v-btn color="grey" text="Non" @click="user = undefined; model = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts" setup>
  import { user } from '../store/index.js'

  const model = defineModel<boolean>()

</script>
