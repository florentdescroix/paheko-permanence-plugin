<template>
  <v-data-table
    :headers="[
      { sortable: false, key: 'date', title: 'Date', value: e => new Date(e.date).toLocaleDateString(), width: 100 },
      { sortable: false, key: 'label', title: 'Libelé', width: 200 },
      { sortable: false, key: 'from', title: 'De', align: 'end', width: 100 },
      { sortable: false, key: 'arrow', align: 'end px-0', width: 10 },
      { sortable: false, key: 'to', title: 'Vers', width: 100 },
    ]"
    :items="Accounting.getTransactions(query)"
  >
    <template #item.label="{ item, value }">
      <router-link v-if="item?.reference?.startsWith('Facture')" :to="`/invoices/${item.id}`">{{ value }}</router-link>
      <template v-else>{{ value }}</template>
    </template>

    <template #item.from="{ item }">
      <template v-for="line of item.lines" :key="line.id">
        <AmountChip v-if="line.credit > 0" :line="line" :to="item.type == TransactionType.CREDIT && item.notes ? `invoices/${item.id}` : null" />
      </template>
    </template>

    <template #item.arrow>
      <v-icon icon="mdi-arrow-right-bold" />
    </template>

    <template #item.to="{ item }">
      <template v-for="line of item.lines" :key="line.id">
        <AmountChip v-if="line.debit > 0" :line="line" />
      </template>
    </template>
  </v-data-table>
</template>

<script lang="ts" setup>
  import { computed } from 'vue'
  import AmountChip from '../components/AmountChip.vue'
  import { Accounting, TransactionType } from '../store/index.js'

  const { type, status } = defineProps<{ type: number, status: number }>()

  const query = computed(() => {
    const query = {}
    if (type !== undefined) query.type = type
    if (status !== undefined) query.status = status
    return query
  })
</script>
