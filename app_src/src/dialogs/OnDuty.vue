<template>
  <v-dialog max-width="600" persistent>
    <v-card :disabled="User.onDuty.length === 0" :loading="User.onDuty.length === 0">
      <v-card-text>
        <v-autocomplete
          v-model="user"
          bg-color="surface"
          :item-title="e => e.identity"
          :items="User.onDuty"
          label="Qui va là ?"
        />
      </v-card-text>
    </v-card>
  </v-dialog>
</template>

<script lang="ts" setup>
  import { user, User } from '../store/index.js'

</script>
