<template>
  <v-select v-model="model" :item-title="title" :item-value="e => e.id" :items="accounts">
    <template #item="{props, item}">
      <v-list-item v-bind="props">
        <template #prepend>
          <v-icon v-bind="icons[item.type]" />
        </template>
      </v-list-item>
    </template>

    <template #append-item>
      <v-list-item class="text-center font-italic" :disabled="account ? !account.bookmark : false" @click="advanced = !advanced">
        Afficher {{ advanced ? 'moins' : 'plus' }}
      </v-list-item>
    </template>
  </v-select>
</template>

<script lang="ts" setup>
  import { computed, ref, watch } from 'vue'
  import { Accounting, AccountType } from '../store/index.js'

  const icons = {
    [AccountType.REVENUE]: { color: 'green', icon: 'mdi-plus-circle' },
    [AccountType.EXPENSE]: { color: 'red', icon: 'mdi-minus-circle' },
    [AccountType.CASH]: { color: 'amber', icon: 'mdi-hand-coin' },
    [AccountType.BANK]: { color: 'amber', icon: 'mdi-bank-circle' },
    [AccountType.OUTSTANDING]: { color: 'amber', icon: 'mdi-checkbook' },
    [AccountType.THIRD_PARTY]: { color: 'blue', icon: 'mdi-account-circle' },
  }

  const { types = [] } = defineProps<{ types?: Array }>()

  const model = defineModel<number>()
  const advanced = ref(false)

  const account = computed(() => Accounting.account(model.value))

  const accounts = computed(() => {
    return Accounting.accounts
      .filter(a => types.includes(a.type) && (advanced.value || a.bookmark))
      .toSorted((a, b) => !a.bookmark + a.code < !b.bookmark + b.code ? -1 : 1)
  })

  watch(() => types, () => {
    advanced.value = account.value ? !account.value.bookmark : false
  })

  function title (acc) {
    return [AccountType.EXPENSE, AccountType.REVENUE].includes(acc.type) ? (acc?.description ?? acc.label) : acc.label
  }
</script>
