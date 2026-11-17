<template>

  <UserForm v-model="user" />

  <v-row>
    <v-col cols="12" sm="6">
      <ServiceSelect
        v-model="fee"
        v-model:payement="payDialog"
        v-model:user="user"
        @after-leave="user = {}"
      />
    </v-col>

    <v-col class="ma-auto" cols="12">
      <v-btn
        :disabled="!isValid(user) || !fee"
        text="Procéder au payement"
        type="submit"
        width="100%"
        @click="pay"
      />
    </v-col>
  </v-row>
</template>

<route lang="json">
{
  "name": "Nouvelle adhésion",
  "meta": {
    "order": 1,
    "admin": false,
    "icon": "mdi-file-plus"
  }
}
</route>

<script lang="ts" setup>
  import { ref } from 'vue'
  import ServiceSelect from '../components/ServiceSelect.vue'
  import UserForm from '../components/UserForm.vue'
  import { User } from '../store/index.js'

  const payDialog = ref(false)
  const user = ref({})
  const fee = ref()

  function isValid (user) {
    for (const field of User.fields) {
      if (field.name != 'numero' && field.required && !user[field.name]) {
        return false
      }
    }
    return true
  }

  async function pay () {
    user.value = await (user.value.id ? User.update(user.value) : User.create(user.value))
    payDialog.value = true
  }
</script>
