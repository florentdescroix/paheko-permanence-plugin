<template>
  <v-card class="mb-4" title="Période">
    <v-card-text>
      <v-row>
        <v-col><v-date-input v-model="from" input-format="dd/mm/yyyy" label="Du" /></v-col>
        <v-col><v-date-input v-model="to" input-format="dd/mm/yyyy" label="Au" /></v-col>
      </v-row>
    </v-card-text>
  </v-card>

  <template v-if="data">

    <v-card class="mb-4" title="Membres">
      <v-card-text>
        <p>Actifs sur la période : <strong>{{ data.users.value }}</strong></p>
        <p>Nouveaux sur la période : <strong>{{ data.users.new }}</strong></p>
        <p>Renouveaux sur la période : <strong>{{ data.users.renew }}</strong></p>
      </v-card-text>
    </v-card>

    <v-card class="mb-4" title="Ages">
      <v-card-subtitle>
        Moyenne : {{ data.users.age.mean.toFixed(1) }}<br>
        Médiane : {{ data.users.age.median }}
      </v-card-subtitle>

      <v-card-text>
        <v-sparkline
          :fill="true"
          :gradient="gradient"
          :height="100"
          interactive
          :labels="ages.map(e => e % 5 == 0 ? e : '')"
          :line-width="4"
          :model-value="values(data.users.age.list, ages)"
          tooltip
          type="bar"
          :width="400"
        />
      </v-card-text>
    </v-card>

    <template v-for="service in data.subscriptions.services" :key="service.key">
      <v-card class="mb-4" :title="service.key">
        <v-card-subtitle>
          Total : {{ service.value }}<br>

          <template v-for="fee in service.fees" :key="fee.key">
            <template v-if="fee.key != service.key">
              {{ fee.key }} : {{ fee.value }}<br>
            </template>
          </template></v-card-subtitle>

        <v-card-text>
          <v-sparkline
            :fill="true"
            :gradient="gradient"
            :height="100"
            interactive
            :labels="months.map(m => (new Date(m)).toLocaleDateString('fr-FR', { month: 'short'}))"
            :line-width="25"
            :model-value="values(service.list, months, d => d.slice(0, -3))"
            tooltip
            type="bar"
            :width="400"
          />
        </v-card-text>
      </v-card>
    </template>
  </template>
</template>

<route lang="json">
{
  "name": "Statistiques",
  "meta": {
    "order": 5,
    "nocard": true,
    "admin": true,
    "icon": "mdi-chart-areaspline"
  }
}
</route>

<script lang="ts" setup>
  import { ref, watchEffect } from 'vue'
  import { stats } from '../store'
  import { toISODateString } from '../utils'

  const from = ref(new Date('2025-09-01'))
  const to = ref(new Date('2026-08-31'))
  const gradient = ['#f72047', '#ffd200', '#1feaea']

  const data = ref()

  watchEffect(async () => {
    const startDate = toISODateString(from.value)
    const endDate = toISODateString(to.value)
    data.value = await stats(startDate, endDate)
  })

  const months = []
  const currentDate = new Date(from.value)
  const stopDate = new Date(to.value)
  while (currentDate <= stopDate) {
    currentDate.setDate(1)
    const dateStr = toISODateString(currentDate).slice(0, -3)
    months.push(dateStr)
    currentDate.setMonth(currentDate.getMonth() + 1)
  }

  const ages = ref([])
  for (let i = 10; i <= 90; i++) {
    ages.value.push(i)
  }

  function values (list, keys, keyModifier = undefined) {
    const values = Object.fromEntries(keys.map(m => [m, 0]))
    for (const val of list) {
      const key = keyModifier ? keyModifier(val.key) : val.key
      if (key in values)
        values[key] += val.value
    }
    return Object.values(values)
  }
</script>
