<template>
  <v-tabs v-model="tab">
    <v-tab v-for="tabName in tabs" :key="tabName">
      {{ tabName }}
    </v-tab>
  </v-tabs>

  <v-window v-model="tab" class="mt-2">
    <v-window-item>
      <TransactionList :type="TransactionType.CREDIT" />
      <v-btn text="Nouvelle facture" to="/invoices/new" width="100%" />

    </v-window-item>

    <v-window-item>
      <TierList />
      <v-btn text="Nouveau tier" width="100%" @click="newTier = true" />
      <NewTier v-model="newTier" />
    </v-window-item>
  </v-window>

</template>

<route lang="json">
{
  "name": "Facturation",
  "meta": {
    "order": 2,
    "noBg": false,
    "admin": true,
    "icon": "mdi-file-document-edit"
  }
}
</route>

<script lang="ts" setup>
  import { ref } from 'vue'
  import TierList from '../../components/TierList.vue'
  import TransactionList from '../../components/TransactionList.vue'
  import NewTier from '../../dialogs/NewTier.vue'
  import { TransactionType } from '../../store/index.js'

  const tabs = ['Factures', 'Tiers']
  const tab = ref(0)
  const newTier = ref(false)
</script>

<style>
iframe {
  width: 210mm;
  height: 298mm;
}
</style>
