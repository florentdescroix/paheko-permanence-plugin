<template>
  <v-autocomplete
    v-model="selection"
    v-model:search="search"
    :custom-filter="() => true"
    :item-title="e => e.identity"
    :item-value="e => e"
    :items="users"
    label="Membre"
    :loading="loading"
    prepend-inner-icon="mdi-account"
  >
    <template #item="{ props, item }">
      <v-list-item v-bind="props">
        <template #append>
          <v-icon :color="item.active ? 'success' : 'error'" icon="mdi-circle" />
        </template>
      </v-list-item>
    </template>

    <template v-if="$slots.append" #append>
      <slot name="append" />
    </template>

    <template #no-data>
      <v-list-item v-if="loading">
        Recherche en cours...
      </v-list-item>

      <v-list-item v-else-if="search.length > 2">
        Aucun·e membre trouvé·e
      </v-list-item>

      <v-list-item v-else>
        Saisissez au moins 3 lettres
      </v-list-item>
    </template>
  </v-autocomplete>
</template>

<script lang="ts" setup>
  import { ref, watch } from 'vue'
  import { User } from '../store/index.js'

  const model = defineModel<object>()

  const selection = ref()
  const loading = ref(false)
  const users = ref([])
  const search = ref('')

  watch(search, async () => {
    loading.value = true
    users.value = search.value.length > 2 ? (await User.findByName(search.value)) : []
    loading.value = false
  })

  watch(selection, async () => {
    if (selection.value?.id) {
      model.value = await User.get(selection.value.id)
    }
  })
</script>
