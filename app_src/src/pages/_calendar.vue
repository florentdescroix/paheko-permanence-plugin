<template>
  <v-container v-if="loading" class="d-flex justify-center align-center">
    <v-progress-circular color="white" indeterminate size="x-large" />
  </v-container>

  <table v-else>
    <tbody>
      <tr v-for="event,e of events" :key="e">
        <td>{{ event.dtstart }}</td>
        <td>{{ event.dtend }}</td>
        <td>{{ event.summary }}</td>
      </tr>
    </tbody>
  </table>
</template>

<script lang="ts" setup>
  import { ref } from 'vue'

  const serverCall = inject('serverCall')
  const events = ref([])
  const loading = ref(true)

  const end = Math.floor(Date.now() / 1000)
  const start = end - 604_800

  serverCall('fetch', 'https://framagenda.org/remote.php/dav/public-calendars/JRJRcEo2maoMGADf/?export&accept=jcal&expand=1&start=' + start + '&end=' + end)
    .then(res => {
      const result = JSON.parse(res)
      events.value = []
      for (const el of result[2]) {
        const event = {}
        for (const prop of el[1]) {
          event[prop[0]] = prop[3]
        }
        events.value.push(event)
      }
      loading.value = false
    })
</script>
