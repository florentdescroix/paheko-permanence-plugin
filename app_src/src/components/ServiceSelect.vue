<template>
  <v-select
    v-model="model"
    :item-title="fee => fee.service.label + (fee.label != fee.service.label ? ` ${fee.label.toLowerCase()}` : '') + ` (${fee.amount/100}€)`"
    :items="User.fees"
    label="Cotisation"
    prepend-inner-icon="mdi-calendar-month"
  />

  <PaymentDialog
    v-model="payDialog"
    :amount="model?.amount / 100"
    @after-leave="model = undefined; $emit('after-leave')"
    @after-save="afterPay"
    @before-save="beforePay"
  >
    <template #done>
      <ul>
        <li>Nom : <strong>{{ user.nom }}</strong></li>
        <li>Prénom : <strong>{{ user.prenom }}</strong></li>
        <li>Jusqu'au : <strong>{{ expiry.toLocaleDateString() }}</strong></li>
      </ul>
    </template>
  </PaymentDialog>
</template>

<script lang="ts" setup>
  import { computed } from 'vue'
  import PaymentDialog from '../dialogs/Payment.vue'
  import { User } from '../store/index.js'

  defineEmits(['after-leave'])

  const model = defineModel<object>()
  const payDialog = defineModel<boolean>('payement')
  const user = defineModel<object>('user', { required: true })

  const expiry = computed(() => new Date(user.value?.services?.[0]?.expiry_date))

  async function beforePay (transaction) {
    transaction.subscription = {
      id_user: user.value.id,
      id_service: model.value.service.id,
      id_fee: model.value.id,
    }
  }

  async function afterPay (transaction) {
    user.value.services.unshift(transaction.subscription)
  }
</script>
