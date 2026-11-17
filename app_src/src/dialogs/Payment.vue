<template>
  <v-dialog v-if="option" v-model="model" max-width="600" @after-leave="close">
    <v-card :disabled="loading" :loading="loading">
      <v-card-title>
        Procéder au payment
      </v-card-title>

      <v-card-text>
        <v-alert v-if="done" type="success">
          Le payement est bien enregistré !<br>
          <slot v-if="$slots.done" name="done" />
          <!-- <v-btn v-if="model?.label == 'Sumup'" @click="openReceipt">Envoyer un reçu</v-btn> -->
        </v-alert>

        <template v-else-if="account">
          <v-alert v-if="account.description" type="info">
            {{ account.description }}
          </v-alert>
        </template>

        <v-container v-if="!done">
          <v-row>
            <v-col cols="12" sm="6">
              <AccountSelect
                v-model="payementLine.id_account"
                :label="option.accounts[1].label"
                :types="option.accounts[1].types"
              />
            </v-col>

            <v-col cols="12" sm="5">
              <v-number-input
                v-model="payementLine[option.accounts[1].direction]"
                decimal-separator=","
                :disabled="lines.length > 1"
                label="Montant"
                :min="0"
                :precision="2"
                prepend-inner-icon="mdi-currency-eur"
              />
            </v-col>

            <v-col cols="12" sm="1">
              <v-date-input v-model="date" />
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>

      <v-card-actions>
        <v-spacer />

        <v-btn
          v-if="!done"
          :disabled="(!payementLine.credit && !payementLine.debit) || !payementLine.id_account"
          text="Valider"
          @click="process()"
        />

        <v-btn color="grey" :text="done ? 'Fermer' : 'Annuler'" @click="close()" />
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog v-model="loading" max-width="500" persistent>
    <v-card :loading="loading">
      <v-card-text>
        Payement en cours...
      </v-card-text>
    </v-card>
  </v-dialog>

  <v-dialog max-width="500" :model-value="receiptDialog !== false">
    <v-card :disabled="receiptMail === 'loading'" :loading="receiptMail === true">
      <v-card-text>
        <v-text-field v-model="receiptMail" label="Mail" />
      </v-card-text>

      <v-card-actions>
        <v-btn text="Envoyer" @click="sendReceipt()" />
        <v-btn color="grey" text="Annuler" @click="receiptDialog = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts" setup>
  import { computed, ref, watch } from 'vue'
  import AccountSelect from '../components/AccountSelect.vue'
  import { Accounting, TransactionType } from '../store'
  import { toISODateString } from '../utils.js'

  const emit = defineEmits(['after-leave'])

  const { type = TransactionType.REVENUE, lines = [], amount, onBeforeSave = () => {}, onAfterSave = () => {} } = defineProps<{
    type?: number
    lines?: Array
    amount?: number
    onBeforeSave?: Function
    onAfterSave?: Function
  }>()

  const model = defineModel<boolean>()

  const done = ref(false)
  const loading = ref(false)

  const account = computed(() => Accounting.account(payementLine.value.id_account))
  const date = ref()
  const option = computed(() => Accounting.types[type])
  // const receipt = ref()
  const receiptMail = ref('')
  const receiptDialog = ref(false)
  const payementLine = ref({})

  watch(model, val => {
    if (val) {
      done.value = false
      payementLine.value[option.value.accounts[1].direction] = amount ?? lines.reduce((sum, l) => sum + (l.debit ?? 0 + l.credit ?? 0), 0)
    }
  })

  // function openReceipt () {
  //   receiptMail.value = ''
  //   receiptDialog.value = true
  // }

  async function sendReceipt () {
    receiptDialog.value = 'loading'
    receiptDialog.value = false
  }

  async function process () {
    loading.value = true
    let transaction = {
      type,
      amount,
      label: lines.map(l => l.label).join(', '),
      lines: [...lines, payementLine.value],
      linked_transactions: lines.map(l => l.linked_transaction).filter(v => !!v),
    }
    if (date.value) {
      transaction.date = toISODateString(date.value)
    }
    if (transaction.lines.length == 2) {
      delete transaction.lines[0].label
      if (transaction.lines[0].amount != transaction.lines[1].amount) {
        transaction.lines[0].amount = transaction.lines[1].amount
      }
    }

    await onBeforeSave(transaction)
    try {
      transaction = await Accounting.save(transaction)
    } catch (error) {
      loading.value = false
      throw error
    }
    await onAfterSave(transaction)

    loading.value = false
    done.value = true
  }

  function close () {
    model.value = false
    done.value = false
    loading.value = false
    payementLine.value = {}
    emit('after-leave')
  }
</script>
