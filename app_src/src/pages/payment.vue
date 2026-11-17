<template>
  <v-tabs v-model="type" class="mb-4">
    <template v-for="opt of Accounting.types" :key="opt.id">
      <v-tab v-if="opt.id != TransactionType.ADVANCED" :value="opt.id">
        {{ opt.label }}
      </v-tab>
    </template>
  </v-tabs>

  <template v-if="type == 99">
    <v-data-table
      :headers="[
        { sortable: false, key: 'date', title: 'Date', value: e => new Date(e.date).toLocaleDateString(), width: 100 },
        { sortable: false, key: 'label', title: 'Libelé', width: 200 },
        { sortable: false, key: 'tier', title: 'Tier', value: e => Accounting.account(e.lines[1].id_account).label },
        { sortable: false, key: 'remains', title: 'Reste', value: e => (e.lines.reduce((acc, l) => acc + l.credit, 0) - e.paid) / 100 },
        { sortable: false, key: 'select', title: 'Régler', value: 'select' },
      ]"
      :items="Accounting.getTransactions({ type: TransactionType.CREDIT, status: 1})"
    >
      <template #item.remains="{ item, value }">
        <template v-if="lines.some(l => l.linked_transaction == item.id)">
          <v-number-input
            v-model="lines.find(l => l.linked_transaction == item.id).credit"
            control-variant="hidden"
            decimal-separator=","
            density="compact"
            inset
            size="4"
            suffix="€"
          />
        </template>

        <template v-else>
          {{ value }}€
        </template>
      </template>

      <template #item.select="{ item, internalItem }">
        <v-checkbox-btn
          v-model="lines"
          multiple
          :value="{
            id_account: item.lines[1].id_account,
            label: item.label,
            credit: internalItem.columns.remains,
            linked_transaction: item.id,
          }"
          :value-comparator="(a, b) => a.linked_transaction == b.linked_transaction"
        />
      </template>
    </v-data-table>
  </template>

  <template v-else>
    <v-alert v-if="option?.help" class="mb-4" type="info">
      {{ option.help }}
    </v-alert>

    <v-row v-for="line, i of lines" :key="i">
      <v-col :cols="lines.length > 1 ? 11 : 12">
        <v-row>
          <v-col cols="12" sm="12">
            <v-text-field v-model="line.label" label="Libelé" prepend-inner-icon="mdi-pencil" />
          </v-col>

          <v-col cols="12" sm="6">
            <AccountSelect
              v-model="line.id_account"
              :label="option?.accounts[0].label"
              :types="option?.accounts[0].types"
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-number-input
              v-model="line[option?.accounts[0].direction]"
              decimal-separator=","
              label="Montant"
              :precision="2"
              prepend-inner-icon="mdi-currency-eur"
            />
          </v-col>
        </v-row>
      </v-col>

      <v-col v-if="lines.length > 1" class="border-s-sm d-flex justify-center align-center" cols="1">
        <v-btn
          color="error"
          density="compact"
          icon="mdi-delete"
          variant="text"
          @click="lines.splice(i, 1)"
        />
      </v-col>

      <v-divider v-if="lines.length > 1" />
    </v-row>

    <v-row>
      <v-col v-if="type < 3" class="ml-auto" cols="auto">
        <v-btn text="Ajouter une ligne" variant="tonal" @click="lines.push({})" />
      </v-col>
    </v-row>
  </template>

  <v-btn
    class="mt-8"
    :disabled="lines.length === 0 || lines.some(l => (!l.debit && !l.credit) || !l.label || !l.id_account)"
    text="Procéder au payement"
    width="100%"
    @click="checkAmount()"
  />

  <PaymentDialog v-model="payDialog" :lines="lines" :type="type" @after-save="clear" />

  <v-dialog v-model="negativeDialog" max-width="600">
    <v-card title="Attention">

      <v-card-text>
        Un montant saisis est <b>négatif</b>, cela implique une opération inverse. <br>
        <br>
        Celà n'est utile que pour catégoriser une dépense comme une recette et inversement (par exmemple : remboursement, charges liées à une recette, etc) <br>
        <br>
        Es tu sûr·e de vouloir continuer ?
      </v-card-text>

      <v-card-actions>
        <v-btn @click="negativeDialog = false; pay()">Je sais ce que je fais</v-btn>
        <v-btn color="error" @click="negativeDialog = false">J'ai fais une erreur</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

</template>

<route lang="json">
{
  "name": "Paiement",
  "meta": {
    "order": 2,
    "admin": false,
    "icon": "mdi-hand-coin"
  }
}
</route>

<script lang="ts" setup>
  import { computed, ref, watch } from 'vue'
  import AccountSelect from '../components/AccountSelect.vue'
  import PaymentDialog from '../dialogs/Payment.vue'
  import { Accounting, TransactionType } from '../store/index.js'

  const payDialog = ref(false)
  const type = ref(1)
  const lines = ref([{}])
  const option = computed(() => Accounting.types[type.value])
  const negativeDialog = ref(false)

  watch(type, clear)

  async function checkAmount () {
    for (const line of lines.value) {
      if (line.debit < 0 || line.credit < 0) {
        negativeDialog.value = true
        return
      }
    }
    pay()
  }

  async function pay () {
    payDialog.value = true
  }

  function clear () {
    lines.value = type.value == 99 ? [] : [{}]
  }
</script>
