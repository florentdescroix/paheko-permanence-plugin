<template>
  <template v-if="month">
    <v-row>
      <v-spacer />

      <v-col cols="3">
        <v-select
          v-model="year"
          item-title="label"
          :items="Accounting.years"
        />
      </v-col>

      <v-spacer />
    </v-row>

    <v-tabs v-model="month" align-tabs="center">
      <v-tab v-for="m in months" :key="m.from" class="text-capitalize" :value="m">
        {{ new Date(m.start_date).toLocaleDateString('fr', { month: 'long' }) }}
      </v-tab>
    </v-tabs>

    <v-expansion-panels class="mt-4">
      <template v-for="section of sections" :key="section.label">
        <EconomicSection :section="section" />
      </template>
    </v-expansion-panels>

    <div class="mx-auto mt-5 text-center">
      <strong>TOTAL MOIS: {{ total.toFixed(2) }}€</strong>
    </div>
  </template>
</template>

<route lang="json">
{
  "name": "Suivis économique",
  "meta": {
    "order": 0,
    "admin": true,
    "icon": "mdi-finance"
  }
}
</route>

<script lang="ts" setup>
  import { ref, watch } from 'vue'
  import EconomicSection from '../components/EconomicSection.vue'
  import { Accounting, AccountType } from '../store/index.js'
  import { toISODateString } from '../utils.js'

  const months = ref()
  const year = ref()
  const month = ref()
  const total = ref(0)
  const sections = ref([])

  let model, monthValues, yearValues

  Accounting.years.then(res => year.value = res[0])

  watch(month, async () => {
    [model, monthValues, yearValues] = await Promise.all([
      Accounting.model,
      Accounting.balances(month.value.start_date, month.value.end_date),
      Accounting.balances(year.value.start_date, year.value.end_date),
    ])
    sections.value = []
    total.value = 0
    for (const section of model) {
      const val = calcBalances(section)
      sections.value.push(val)
      total.value += val.by_year ? val.balance / 12 : val.balance
    }
  })

  watch(year, () => {
    if (year.value) {
      const start = new Date(`${year.value.start_date} 12:00:00`)
      const end = new Date(`${year.value.end_date} 12:00:00`)

      months.value = []
      while (start < end) {
        const month = {
          start_date: toISODateString(start),
        }
        start.setMonth(start.getMonth() + 1)
        const to = new Date(start)
        to.setDate(to.getDate() - 1)
        month.end_date = toISODateString(to)
        months.value.push(month)
      }
      month.value = months.value.at(-1)
    }
  })

  function calcBalances (model, values = monthValues) {
    const section = { label: model.label, goal: model.goal, by_year: model.by_year }
    values = model.by_year ? yearValues : values

    section.balance = 0
    for (const id of model.accounts || []) {
      const val = id in values ? (values[id].balance / 100 * (values[id].type == AccountType.EXPENSE ? -1 : 1)) : 0
      section.accounts ??= []
      section.accounts.push({ id, balance: val })
      section.balance += val
    }
    for (const child of model.children || []) {
      const val = calcBalances(child, values)
      section.children ??= []
      section.children.push(val)
      section.balance += val.balance
    }
    return section
  }
</script>
