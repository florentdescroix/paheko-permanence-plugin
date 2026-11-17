<template>
  <v-expansion-panel @group:selected="loadMovements()">
    <v-expansion-panel-title class="text-overline">
      <span>
        <v-badge :color="colorCode[model.category.name]" :content="model.name.split(' - ')[0]" inline />

        <v-badge :color="model.stock - model.out > 0 ? 'success' : 'error'" dot :offset-x="-10">
          <span>
            <b>{{ model.name.split(' - ')[1] }}</b> {{ model.name.split(' - ')[2] }}
            <small>({{ model.category.name }})</small>
          </span>
        </v-badge>

      </span>

      <template #actions>
        <v-btn
          class="my-n1 me-4"
          density="compact"
          :disabled="model.stock == model.out"
          variant="tonal"
          @click.stop="locDialog = true"
        >Louer</v-btn>
      </template>
    </v-expansion-panel-title>

    <v-expansion-panel-text>
      <v-data-table
        :headers="[
          {key: 'date', title: 'Date', value:(e) => new Date(e.date).toLocaleDateString()},
          {key: 'direction', title: 'Direction', value: (e) => e.direction == 'input' ? 'Entrée' : 'Sortie' },
          {key: 'type', title: 'Operation', value: (e) => e.operation?.label },
          {key: 'user', title: 'Membre', value: (e) => e.user?.identity},
          {key: 'ongoing', title: '', value: ongoing},
        ]"
        :hide-default-footer="movements.length <= 10"
        :item-per-page="10"
        :items="movements"
        :search="' '"
      >
        <template #item.ongoing="{ item, value }">
          <v-btn
            v-if="value"
            color="info"
            density="compact"
            variant="tonal"
            @click="pay(item)"
          >
            Retour : {{ duration(item) }}j
          </v-btn>
        </template>
      </v-data-table>
    </v-expansion-panel-text>
  </v-expansion-panel>

  <v-dialog v-model="locDialog" max-width="600">
    <v-card>
      <v-card-title>
        Qui loue ?
      </v-card-title>

      <v-card-text>
        <UserSelect v-model="user" />
      </v-card-text>

      <v-card-actions>
        <v-btn text="Valider" @click="addOutput" />
        <v-btn color="grey" text="Annuler" @click="locDialog = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>

  <PaymentDialog v-model="payDialog" :lines="[line]" @after-save="addInput" />
</template>

<script lang="ts" setup>
  import { computed, ref } from 'vue'
  import UserSelect from '../components/UserSelect.vue'
  import PaymentDialog from '../dialogs/Payment.vue'
  import { Equipment } from '../store/index.js'

  enum colorCode {
    '2€/j' = 'blue',
    '3€/j' = 'yellow',
    '5€/j' = 'red',
    '10€/j' = 'black',
  }

  const payDialog = ref(false)
  const locDialog = ref(false)
  const line = ref()
  const user = ref()

  let selectedMovement

  const { displayAll = false } = defineProps<{ displayAll?: boolean }>()

  const model = defineModel<any>()

  const movements = computed(() => model.value?.movements ? model.value.movements.filter(item => displayAll || ongoing(item)) : [])

  function ongoing (movement) {
    return movement.operation.type == 'temporaire' && !movement.return
  }

  async function loadMovements (force = false) {
    if (force || !model.value?.movements) {
      model.value = await Equipment.get(model.value?.id)
    }
  }

  function duration (movement) {
    const date1 = new Date(movement.date)
    const date2 = movement?.return ? new Date(movement.return.date) : new Date()
    const diffTime = Math.abs(date2 - date1)
    return Math.max(1, Math.floor(diffTime / (1000 * 60 * 60 * 24)))
  }

  async function pay (movement) {
    selectedMovement = movement
    line.value = {
      label: `Location - ${model.value?.name}`,
      account: 7083,
      credit: duration(selectedMovement) * Number.parseFloat(model.value?.category?.name),
    }
    payDialog.value = true
  }

  async function addOutput () {
    const operations = await Equipment.operations
    const operation = operations.outputs.find(op => op.type == 'temporaire' && op.fee == 'payant')
    await Equipment.addMovement({
      direction: 'output',
      operation: operation.id,
      equipment: model.value?.id,
      user: user.value.id,
    })
    loadMovements(true)
    locDialog.value = false
  }

  async function addInput (transaction) {
    const operations = await Equipment.operations
    const operation = operations.inputs.find(op => op.type == 'retour')
    await Equipment.addMovement({
      direction: 'input',
      operation: operation.id,
      equipment: model.value.id,
      links: [selectedMovement.id],
      transactions: [transaction.id],
      user: selectedMovement.user.id,
    })
    loadMovements(true)
  }
</script>
