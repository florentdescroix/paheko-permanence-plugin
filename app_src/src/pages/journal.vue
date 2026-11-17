<template>
  <TransactionList />
</template>

<route lang="json">
{
  "name": "Journal comptable",
  "meta": {
    "order": 1,
    "admin": true,
    "icon": "mdi-cash-register"
  }
}
</route>

<script lang="ts" setup>
  import TransactionList from '../components/TransactionList.vue'
</script>
