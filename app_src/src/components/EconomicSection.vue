<template>
  <v-expansion-panel eager hide-actions>
    <v-expansion-panel-title>
      <v-row>
        <v-col class="font-weight-bold" cols="4">{{ section.label }}</v-col>

        <v-col cols="8">
          <v-progress-linear
            :color="color"
            height="25"
            :max="section.goal"
            :model-value="section.balance"
            :reverse="section.balance < 0"
          >
            {{ section.balance.toFixed(2) }}{{ section.goal ? ` / ${section.goal}` : '' }}€
            <template v-if="section.by_year">soit {{ (section.balance/12).toFixed(2) }}{{ section.goal ? ` / ${section.goal/12}` : '' }}€ par mois</template>
          </v-progress-linear>
        </v-col>
      </v-row>
    </v-expansion-panel-title>

    <v-expansion-panel-text>
      <v-expansion-panels>
        <EconomicSection v-for="child of section.children" :key="child.label" :section="child" />
      </v-expansion-panels>

      <v-container v-if="section?.accounts">
        <v-row v-for="{id, balance} of section.accounts" :key="id">
          <v-col cols="4">{{ Accounting.account(id)?.description ?? Accounting.account(id)?.label }}</v-col>
          <v-col cols="8"> {{ balance }}€</v-col>
        </v-row>
      </v-container>
    </v-expansion-panel-text>
  </v-expansion-panel>
</template>

<script lang="ts" setup>
  import { computed } from 'vue'
  import { Accounting } from '../store'

  const { section } = defineProps<{
    section: { label: string, children?: array, accounts?: array, goal?: number }
  }>()

  const color = computed(() => {
    if (section.goal == 0) return 'grey'
    if (section.balance < 0) return 'red'
    if (section.balance < section.goal * 1 / 3) return 'orange'
    if (section.balance < section.goal * 2 / 3) return 'yellow'
    if (section.balance < section.goal) return 'blue'
    return 'green'
  })
</script>
