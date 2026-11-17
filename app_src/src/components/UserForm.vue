<template>
  <v-row>
    <template v-for="field of User.fields" :key="field.name">
      <v-col v-if="!['numero', 'date_inscription'].includes(field.name)" cols="12" :sm="field.type == 'multiple' ? 12 : 6">

        <component
          :is="compByType[field.type]"
          v-if="field.type != 'multiple'"
          v-model="model[field.name]"
          :false-value="0"
          :hint="field.help"
          :label="field.label"
          :prepend-inner-icon="icons[field.name]"
          :true-value="1"
        />

        <template v-else>
          <div> {{ field.label }} :</div>

          <v-checkbox-btn
            v-for="option of field.options"
            :key="option"
            v-model="model[field.name]"
            :label="option"
            :multiple="true"
            style="float: left; min-width: max-content; width: 25%;"
            :value="option"
          />
        </template>
      </v-col>
    </template>
  </v-row>
</template>

<script lang="ts" setup>
  import { VCheckbox, VNumberInput, VTextField } from 'vuetify/components'
  import { User } from '../store/index.js'

  const model = defineModel<object>()

  const compByType = {
    text: VTextField,
    email: VTextField,
    tel: VTextField,
    number: VNumberInput,
    year: VNumberInput,
    checkbox: VCheckbox,
  }

  const icons = {
    nom: 'mdi-account',
    prenom: 'mdi-account',
    ville: 'mdi-city',
    annee_naissance: 'mdi-cake',
    email: 'mdi-email',
    telephone: 'mdi-phone',
  }
</script>
