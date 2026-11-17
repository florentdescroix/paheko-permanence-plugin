<template>
  <v-row>
    <v-col cols="12">
      <UserSelect v-model="user">
        <template v-if="user" #append>
          <v-btn icon="mdi-pencil" variant="tonal" @click="edit()" />
        </template>
      </UserSelect>
    </v-col>

    <template v-if="user">
      <v-col cols="12">
        <v-alert :type="active ? 'success' : 'warning'">
          Adhésion {{ active ? "valide jusqu'au" : "expirée depuis le" }} {{ expiry.toLocaleDateString() }}
        </v-alert>
      </v-col>

      <v-col cols="12">
        <ServiceSelect v-model="fee" v-model:payement="payDialog" v-model:user="user" />
      </v-col>

      <v-col cols="12">
        <v-btn :disabled="!fee" text="Procéder au payement" width="100%" @click="pay()" />
      </v-col>
    </template>
  </v-row>

  <v-dialog v-if="user" v-model="editDialog" max-width="600">
    <v-card prepend-icon="mdi-account" title="Profil membre">
      <v-card-text>
        <UserForm v-model="user" />
      </v-card-text>

      <v-divider />

      <v-card-actions>
        <v-spacer />
        <v-btn text="Enregistrer" @click="update" />
        <v-btn color="grey" text="Fermer" @click="editDialog = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<route lang="json">
{
  "name": "Recherche de membre",
  "meta": {
    "order": 0,
    "admin": false,
    "icon": "mdi-file-search"
  }
}
</route>

<script lang="ts" setup>
  import { computed, ref } from 'vue'
  import ServiceSelect from '../components/ServiceSelect.vue'
  import UserForm from '../components/UserForm.vue'
  import UserSelect from '../components/UserSelect.vue'
  import { User } from '../store/index.js'

  const payDialog = ref(false)
  const editDialog = ref(false)
  const user = ref()
  const fee = ref()

  const expiry = computed(() => new Date(user.value?.services?.[0]?.expiry_date || 0))
  const active = computed(() => new Date() <= expiry.value)

  async function pay () {
    payDialog.value = true
  }

  async function update () {
    user.value = await User.update(user.value)
    editDialog.value = false
  }

  async function edit () {
    editDialog.value = true
  }
</script>
