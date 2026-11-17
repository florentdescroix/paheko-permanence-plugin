<template>

  <div class="d-flex align-center">
    <v-btn v-if="expertMode" :disabled="!canSaveAll" @click="confirmAll = true">Enregistrer tout</v-btn>
    <v-spacer />
    <v-switch v-model="expertMode" color="primary" label="Mode expert" />
  </div>

  <template v-for="type, tId in Accounting.types" :key="type">
    <v-card v-if="templateByType[tId].length > 0" class="mb-4" :title="type.label">

      <v-card-text>
        <v-row
          v-for="tpl of templateByType[tId]"
          :key="tpl.id"
          density="compact"
        >
          <v-col :cols="expertMode ? 6 : 9">
            <div class="float-left me-4">
              <v-checkbox-btn v-if="expertMode" v-model="selected" :value="tpl" />

              <v-btn
                v-else
                class="ms-1"
                color="info"
                density="compact"
                icon="mdi-file-document-edit"
                variant="text"
                @click="openEdit(tpl)"
              />
            </div>

            <div class="font-weight-bold">{{ tpl.label }}</div>

            <div v-if="Accounting.types[tpl.type]" class="text-medium-emphasis">
              <template v-for="desc in tpl.simple[tpl.type][Accounting.types[tpl.type].accounts[0].direction]">
                {{ desc.split(' — ')[1] }}
              </template>
            </div>
          </v-col>

          <v-col v-if="expertMode" cols="3">
            <v-date-input
              v-model="tpl.date"
              class="me-2"
              :class="{'v-field--error': selected.includes(tpl) && !tpl.date}"
              density="compact"
              input-format="dd/mm/yyyy"
              label="Date"
              prepend-icon=""
              prepend-inner-icon="$calendar"
            />
          </v-col>

          <v-col class="text-right" cols="3">
            <v-number-input
              v-if="expertMode"
              v-model="tpl.amount"
              class="me-2"
              :class="{'v-field--error': selected.includes(tpl) && !tpl.amount}"
              control-variant="hidden"
              decimal-separator=","
              density="compact"
              label="Montant"
              :precision="2"
              prepend-inner-icon="mdi-currency-eur"
            />

            <strong v-else>{{ tpl.amount }}€</strong>
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>
  </template>

  <v-dialog max-width="400" :model-value="!!selectedTpl" persistent>
    <v-card>
      <v-card-title>
        {{ selectedTpl.label }}
      </v-card-title>

      <v-card-text>
        <v-date-input
          v-model="selectedTpl.date"
          input-format="dd/mm/yyyy"
          label="Date"
          prepend-icon=""
          prepend-inner-icon="$calendar"
        />

        <v-number-input
          v-model="selectedTpl.amount"
          control-variant="hidden"
          decimal-separator=","
          label="Montant"
          :precision="2"
          prepend-inner-icon="mdi-currency-eur"
        />
      </v-card-text>

      <v-card-actions>
        <v-spacer />

        <v-btn
          :disabled="!selectedTpl.date || !selectedTpl.amount"
          text="Enregistrer"
          @click="save()"
        />

        <v-btn color="grey" text="Annuler" @click="selectedTpl = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog max-width="400" :model-value="confirmAll" persistent>
    <v-card>
      <v-card-text>
        Confirmer-vous l'ajout de {{ selected.length }} opérations ?
      </v-card-text>

      <v-card-actions>
        <v-spacer />

        <v-btn text="Je confirme" @click="saveAll()" />

        <v-btn color="error" text="Annuler" @click="confirmAll = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<route lang="json">
{
  "name": "Modèles",
  "meta": {
    "order": 4,
    "nocard": true,
    "admin": true,
    "icon": "mdi-draw-pen"
  }
}
</route>

<script lang="ts" setup>
  import { computed, ref } from 'vue'
  import { Accounting } from '../store/index.js'
  import { toISODateString } from '../utils.js'

  const selectedTpl = ref(false)
  const expertMode = ref(false)
  const confirmAll = ref(false)
  const selected = ref([])

  const templateByType = computed(() => Object.fromEntries(Object.values(Accounting.types).map(t => [t.id, Accounting.templates.filter(e => e.type == t.id)])))

  const canSaveAll = computed(() => selected.value.length > 0 && selected.value.every(el => el.date && el.amount))

  async function openEdit (tpl) {
    const now = new Date()
    now.setDate(1)
    tpl.date = toISODateString(now)
    selectedTpl.value = tpl
  }

  async function save () {
    await Accounting.save(selectedTpl.value)
    selectedTpl.value = false
  }

  async function saveAll () {
    const copy = [...selected.value]
    for (const tr of copy) {
      const index = selected.value.indexOf(tr)
      await Accounting.save(tr)
      selected.value.splice(index, 1)
    }
    confirmAll.value = false
  }
</script>
