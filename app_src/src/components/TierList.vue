<template>
  <v-data-table
    fixed-header
    :headers="[
      { sortable: true, value: 'label', title: 'Titre' },
      { sortable: true, value: 'code', title: 'Code' },
      { sortable: true, value: 'description', title: 'Adresse' },
      { sortable: true, key: 'debit', align: 'center', title: 'Facturé', value: e => e.debit / 100 + '€' },
      { sortable: true, key: 'credit', align: 'center', title: 'Payé', value: e => e.credit / 100 + '€' },
      { sortable: true, key: 'balance', align: 'center', title: 'Solde' },
    ]"
    :hide-default-footer="tiers.length <= 10"
    :items="tiers"
    :items-per-page="10"
    :sort-by="[{ key: 'code', order: 'asc' }]"
  >
    <template #item.balance="{ value }">
      <v-chip
        class="font-weight-bold"
        :class="`bg-${value == 0 ? 'success' : value < 0? 'error' : 'warning'}`"
        :text="(value/100) + '€'"
      />
    </template>
  </v-data-table>
</template>

<script lang="ts" setup>
  import { computed } from 'vue'
  import { Accounting, AccountType } from '../store/index.js'

  const tiers = computed(() => Accounting.accounts.filter(a => a.type == AccountType.THIRD_PARTY))
</script>
