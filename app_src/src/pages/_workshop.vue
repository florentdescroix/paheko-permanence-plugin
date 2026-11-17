<template>
  <v-card>
    <v-card-text>
      <v-expansion-panels class="mb-5">
        <v-expansion-panel v-for="w, k in ateliers" :key="k" hide-actions>
          <v-expansion-panel-title class="text-overline cursor-pointer" :class="'bg-' + normalizeStr(w.posteTravail)">
            <div>
              {{ w.posteTravail }}
              <div class="text-h6 font-weight-regular">
                {{ w.title }} ({{ w.participants.length }}/{{ w.places }})
              </div>
            </div>

            <span class="float-right text-right">
              {{ new Date(w.datetime).toLocaleString([], {
                weekday: "long", day: '2-digit', month: 'long',
                hour: '2-digit', minute: '2-digit'
              }) }}<br>
              {{ w.duree }} heures<br>
              par {{ w.par }}
            </span>
          </v-expansion-panel-title>

          <v-expansion-panel-text>
            <v-container>
              <v-row>
                <template v-for="person, p of w.participants" :key="p">
                  <v-col class="text-right" cols="6" md="2" sm="3">
                    {{ person.nom }}
                  </v-col>

                  <v-col cols="6" md="2" sm="3">
                    {{ person.telephone }}
                  </v-col>
                </template>

                <template v-if="w.participants.length < w.places">
                  <v-spacer />
                  <v-btn text="Inscription" @click="openSubscribeDialog" />
                </template>
              </v-row>
            </v-container>
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>

      <v-btn class="w-100" text="Ajouter un atelier" @click="openCreateDialog" />
    </v-card-text>
  </v-card>

  <v-dialog v-model="subscribeDialog.open" max-width="600">
    <v-card :disabled="subscribeDialog.loading" :loading="subscribeDialog.loading">
      <v-container>
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field v-model="subscription.nom" label="Nom" prepend-inner-icon="mdi-account" />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field v-model="subscription.telephone" label="Téléphone" prepend-inner-icon="mdi-phone" />
          </v-col>
        </v-row>
      </v-container>

      <v-card-actions>
        <v-spacer />

        <v-btn
          :disabled="!subscription.nom || !subscription.telephone"
          text="Valider"
          @click="subscribeWorkshop"
        />

        <v-btn color="grey" text="Annuler" @click="subscribeDialog.open = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>

  <v-dialog v-model="createDialog.open" max-width="600">
    <v-card :disabled="createDialog.loading" :loading="createDialog.loading">
      <v-container>
        <v-row>
          <v-col cols="12" sm="6">
            <v-text-field v-model="workshop.title" label="Intitulé" prepend-inner-icon="mdi-pencil" />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field v-model="workshop.par" label="Proposé par" prepend-inner-icon="mdi-account" />
          </v-col>

          <v-col cols="12" sm="6">
            <v-select
              v-model="workshop.type"
              :items="['Bénévole', 'Professionnel']"
              label="Type"
              prepend-inner-icon="mdi-professional-hexagon"
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-select
              v-model="workshop.posteTravail"
              item-value="title"
              :items="postes_travail"
              label="Poste"
              prepend-inner-icon="mdi-stool"
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field
              v-model="workshop.datetime"
              label="Début"
              prepend-inner-icon="mdi-calendar-clock"
              type="datetime-local"
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field
              v-model="workshop.duree"
              label="Durée (heure)"
              prepend-inner-icon="mdi-timer-sand"
              type="number"
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field
              v-model="workshop.places"
              label="Nombre de places"
              prepend-inner-icon="mdi-account-group"
              type="number"
            />
          </v-col>

          <v-col cols="12" sm="6">
            <v-text-field v-model="workshop.prix" label="Prix" prepend-inner-icon="mdi-currency-eur" type="number" />
          </v-col>
        </v-row>
      </v-container>

      <v-card-actions>
        <v-spacer />

        <v-btn
          :disabled="!workshop.title || !workshop.par || !workshop.type || !workshop.posteTravail || !workshop.datetime || !workshop.duree || !workshop.places || !workshop.prix"
          text="Valider"
          @click="createWorkshop"
        />

        <v-btn color="grey" text="Annuler" @click="createDialog.open = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>

</template>

<script lang="ts" setup>
  import { reactive, ref } from 'vue'

  const workshop = ref()
  const subscription = ref()
  const createDialog = reactive({ open: false, loading: false })
  const subscribeDialog = reactive({ open: false, loading: false })

  function normalizeStr (str) {
    return str.toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '')
  }

  // function toggleWorkshop (w) {
  //   workshop.value = workshop.value == w ? undefined : w
  // }

  function openSubscribeDialog () {
    subscription.value = { atelier: workshop.value.id }
    subscribeDialog.open = true
    subscribeDialog.loading = false
  }

  function openCreateDialog () {
    createDialog.open = true
    createDialog.loading = false
    workshop.value = {}
  }

  async function subscribeWorkshop () {
    subscribeDialog.loading = true
    const newSubscription = await serverCall('subscribeWorkshop', subscription.value)
    workshop.value.participants.push(newSubscription)
    subscribeDialog.loading = false
    subscribeDialog.open = false
  }

  async function createWorkshop () {
    createDialog.loading = true
    const newWorkshop = await serverCall('createWorkshop', workshop.value)
    newWorkshop.participants = []
    value.ateliers.push(newWorkshop)
    createDialog.loading = false
    createDialog.open = false
    sort()
  }

  function sort () {
    value.ateliers.sort((a, b) => new Date(a.datetime) < new Date(b.datetime) ? -1 : 1)
  }

  for (const w of value.ateliers) {
    w.participants = value.reservations_atelier.filter(e => e.atelier == w.id)
  }
  sort()
</script>
