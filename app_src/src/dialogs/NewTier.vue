<template>
  <v-dialog max-width="600" :model-value="model" persistent>
    <v-card>
      <v-card-title>
        Création d'un·e usager·ère
      </v-card-title>

      <v-card-text>
        <v-row>
          <v-col cols="6">
            <v-select v-model="tier.code" :item-value="e => e.value" :items="prefixes" label="Type" />
          </v-col>

          <v-col cols="6">
            <v-text-field v-model="tier.label" label="Titre" />
          </v-col>
        </v-row>

        <v-row>
          <v-col>
            <v-textarea v-model="tier.description" label="Adresse" />
          </v-col>
        </v-row>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn text="Valider" @click="save" />
        <v-btn color="grey" text="Annuler" @click="model = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts" setup>
  import { ref } from 'vue'
  import { Accounting } from '../store/index.js'

  const prefixes = [
    { title: 'Client', value: '411C' },
    { title: 'Pro.', value: '411P' },
  ]

  const model = defineModel<boolean>()

  const tier = ref({
    label: '',
    description: '',
    code: '',
  })

  async function save () {
    await Accounting.createAccount(tier.value)
    model.value = false
  }

</script>
