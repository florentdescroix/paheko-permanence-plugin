<template>
  <v-chip
    v-tooltip="line.label"
    class="my-1 position-relative"
    :class="{'pe-0': line.credit, 'ps-0': line.debit}"
    color="grey-lighten-2"
    variant="flat"
  >
    <span class="acc_name">{{ accountLabel(line.id_account) }}</span>

    <template #[slotName]>
      <v-chip
        class="font-weight-bold"
        :class="{'ms-1': line.credit, 'me-1': line.debit}"
        :color="color(line)"
        variant="flat"
      >
        {{ (line.credit + line.debit)/100 }}€
      </v-chip>
    </template>

  </v-chip>
</template>

<script lang="ts" setup>
  import { computed } from 'vue'
  import { Accounting, AccountType } from '../store/index.js'

  const { line = {} } = defineProps<{
    line?: object
  }>()

  const slotName = computed(() => line.debit ? 'prepend' : 'append')

  function color (line) {
    const type = Accounting.account(line.id_account)?.type
    switch (type) {
      case AccountType.EXPENSE:
      case AccountType.REVENUE: {
        return line.credit > 0 ? 'success' : 'error'
      }
      case AccountType.THIRD_PARTY: {
        return 'info'
      }
      case AccountType.BANK:
      case AccountType.OUTSTANDING:
      case AccountType.CASH: {
        return 'yellow'
      }
      default: {
        return 'surface'
      }
    }
  }

  function accountLabel (accountId) {
    const acc = Accounting.account(accountId)
    if (!acc) return
    return [AccountType.EXPENSE, AccountType.REVENUE].includes(acc.type) ? acc?.description ?? acc.label : acc.label
  }
</script>

<style lang="css">
.acc_name {
  max-width: 142px;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>
