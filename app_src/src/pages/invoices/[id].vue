<template>
  <div id="invoice" class="position-relative d-flex flex-column pa-14" :class="{readonly: readOnly}">

    <div class="d-print-none position-absolute d-flex left-0 top-0 pt-3 w-100 justify-center">
      <v-chip>
        <v-switch v-model="devis" false-icon="no" inset>
          <template #prepend>FACTURE</template>
          <template #append>DEVIS</template>
        </v-switch>
      </v-chip>
    </div>

    <header class="text-disabled mb-2" style="min-height: 1cm;">
      <img :src="Organization.config.files.logo" style="height: 1.5cm;">

      <div class="float-right text-right font-weight-bold">
        <div class="mb-n6">
          {{ transaction.reference }}
          <br>
          <text-input v-model="transaction.date" class="d-print-none" type="date" />
          <span class="d-none d-print-inline">Le {{ textDate }}</span>
        </div>
      </div>
    </header>

    <main class="flex-fill d-flex flex-column">
      <div>
        <div class="border border-opacity-100 pa-2" style="width: 8cm">
          {{ Organization.config.org_name }}<br>

          <template v-for="value,i in (Organization.config.org_address || '').split('\n')" :key="i">
            {{ value }}<br>
          </template>

          {{ Organization.config.org_infos }}<br>
          Contact : <text-input v-model="invoice.contact" />
        </div>
      </div>

      <div class="ml-auto position-relative">
        <div class="position-absolute" style="height: 1cm; bottom: 100%">
          <label class="cursor-pointer">
            <img v-if="clientImg" alt="Logo client" :src="clientImg" style="height: 1cm">

            <input
              v-show="!clientImg"
              class="d-print-none"
              type="file"
              @change="uploadImage"
            >
          </label>
        </div>

        <div class="position-relative pa-2 border border-opacity-100">
          <AccountSelect
            v-model="transaction.lines[1].id_account"
            class="d-print-none position-absolute pa-0"
            flat
            style="width: calc(100% + 28px); height: 100%; top: 0; left: 0; outline: 1px solid black;"
            :types="[AccountType.THIRD_PARTY]"
            variant="solo"
            @update:model-value="changeClient()"
          />

          <div class="position-relative">
            <text-input
              v-model="invoice.client.name"
              placeholder="Nom"
              style="width: 8cm"
            />

            <br>

            <text-input
              v-model="invoice.client.address"
              :class="{'d-print-none': !invoice.client.address}"
              :multiple="true"
              placeholder="Adresse"
              style="width: 8cm"
            />
          </div>
        </div>
      </div>

      <div class="mt-4 d-print-none">
        <AccountSelect
          v-model="transaction.lines[0].id_account"
          label="Type"
          prepend-inner-icon="mdi-invoice-text"
          :types="[AccountType.REVENUE]"
        />
      </div>

      <div class="ga-1 mt-8 mb-4">
        <b>Objet</b><b v-if="devis"> du devis</b> :
        <text-input v-model="transaction.label" placeholder="C'est quoi ?" />
      </div>

      <div class="flex-fill">
        <table class="w-100" style="border-collapse: collapse;">
          <thead>
            <tr class="bg-bois">
              <th class="border pa-1" width="55%">Désignation</th>
              <th class="border pa-1" width="15%">Quantité</th>
              <th class="border pa-1" width="15%">Prix unitaire TTC (€)</th>
              <th class="border pa-1" width="15%">Prix total TTC (€)</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="line, i of invoice.lines" :key="i" :class="{'d-print-none' : line.qte * line.price == 0}">
              <th class="border px-1 text-left">
                <text-input v-model="line.label" class="w-100" :multiple="true" />
              </th>

              <td class="border px-1 text-right">
                <text-input
                  v-model="line.qte"
                  max="999"
                  min="0"
                  type="number"
                />
              </td>

              <td class="border px-1 text-right">
                <text-input
                  v-model="line.price"
                  max="9999.99"
                  min="0"
                  type="number"
                />
              </td>

              <td class="border px-1 text-right">
                {{ (line.qte * line.price).toFixed(2) }}

                <v-btn
                  class="d-print-none position-absolute ms-2 text-caption"
                  color="error"
                  size="x-small"
                  @click="invoice.lines.splice(i, 1)"
                ><v-icon icon="mdi-close" /></v-btn>
              </td>
            </tr>

            <tr>
              <td class="text-center" colspan="4" style="height: 1cm">
                <v-btn class="d-print-none" size="small" @click="addLine">
                  <v-icon icon="mdi-plus" />
                </v-btn>
              </td>
            </tr>
          </tbody>

          <tfoot>

            <tr class="text-right">
              <td />
              <td />
              <th>TOTAL TTC</th>
              <th>{{ sum }}€</th>
            </tr>

            <tr class="text-right" :class="{'d-print-none': invoice.discount == 0}">
              <td />
              <td />
              <td>Remise</td>

              <td>
                <text-input
                  v-model="invoice.discount"
                  :max="sum"
                  min="0"
                  type="number"
                />€
              </td>
            </tr>

            <tr class="text-right" :class="{'d-print-none': invoice.discount == 0}">
              <td />
              <td />
              <th>À RÉGLER</th>
              <th>{{ sum - invoice.discount }}€</th>
            </tr>
          </tfoot>
        </table>

        <div class="text-right font-italic text-disabled">TVA non applicable - article 293 B du CGI.
        </div>
      </div>

      <div v-if="devis" class="flex-fill text-center">
        Merci de nous retourner ce devis signé avec la mention "bon pour accord"
      </div>

      <div v-else class="text-caption">
        Conditions de paiement :
        <ul class="ps-4">
          <li>
            Paiement à réception de facture
          </li>

          <li class="my-1">
            IBAN : <span class="border border-opacity-100 pa-1">FR76 1660 7002 1968 2211 8548 314</span>
          </li>

          <li>
            Tout incident de paiement est passible d'intérêt de retard. Le montant des pénalités résulte de
            l'application aux sommes restant dues d'un taux d'intérêt légal en vigueur au moment de l'incident.
          </li>

          <li>
            Indemnité forfaitaire pour frais de recouvrement due au créancier en cas de retard de paiement: 40€
          </li>
        </ul>
      </div>
    </main>

    <footer class="mt-8 text-center text-disabled">
      Association loi 1901 enregistrée sous le numéro n° W343026331 à la Préfecture de l’Hérault<br>
      N° de SIRET 89089089000027
    </footer>
  </div>

  <div class="text-center mt-2">
    <v-btn class="d-print-none me-2" @click="print">
      Imprimer (PDF)
    </v-btn>

    <v-btn v-if="readOnly" class="d-print-none ms-2" color="secondary" @click="duplicate">
      Dupliquer la facture
    </v-btn>

    <v-btn v-else-if="!readOnly && !devis" class="d-print-none ms-2" color="secondary" @click="confirmDialog = true">
      Enregistrer en compta
    </v-btn>
  </div>

  <v-dialog v-model="confirmDialog" max-width="400" persistent>
    <v-card>
      <v-card-text>
        Cette action va créer une créance d'un montant de
        <v-chip class="my-1 bg-success font-weight-bold">
          {{ transaction.lines[0].credit }}€
        </v-chip>
        de type
        <v-chip class="my-1 font-weight-bold">
          {{ Accounting.account(transaction.lines[0].id_account).label }}
        </v-chip>
        pour
        <v-chip class="my-1 bg-info font-weight-bold">
          {{ invoice.client.name }}
        </v-chip>
        en date du
        <v-chip class="my-1 font-weight-bold">
          {{ textDate }}
        </v-chip>
      </v-card-text>

      <v-card-actions>
        <v-spacer />
        <v-btn text="Valider" @click="save()" />
        <v-btn color="grey" text="Annuler" @click="confirmDialog = false" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<route lang="json">
{
  "name": "Nouvelle facture"
}
</route>

<script lang="ts" setup>
  import { computed, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import AccountSelect from '../../components/AccountSelect.vue'
  import TextInput from '../../components/TextInput.vue'
  import { Accounting, AccountType, Organization, TransactionType } from '../../store'
  import { toISODateString } from '../../utils.js'

  let refNum = '01'

  const route = useRoute()
  const router = useRouter()
  const readOnly = computed(() => route.params.id != 'new')

  const confirmDialog = ref(false)

  const devis = ref(true)
  const clientImg = ref('')

  const invoice = ref({
    client: {},
    contact: '',
    discount: 0,
    lines: [],
  })

  const transaction = ref({
    id: readOnly.value ? route.params.id : undefined,
    type: TransactionType.CREDIT,
    label: '',
    date: toISODateString(),
    reference: '',
    notes: '',
    lines: [
      {
        id_account: undefined,
        credit: 0,
      },
      {
        id_account: undefined,
        debit: 0,
      },
    ],
  })

  const textDate = computed(() => (new Date(transaction.value.date)).toLocaleDateString('fr-FR', { day: 'numeric', weekday: 'long', month: 'long', year: 'numeric' }))
  const sum = computed(() => invoice.value.lines.reduce((acc, l) => acc + (l.qte * l.price), 0).toFixed(2))

  watch(() => route.params.id, async () => {
    await Organization.config
    invoice.value.contact = Organization.config.org_email

    if (readOnly.value) {
      devis.value = false
      transaction.value = await Accounting.getTransaction(route.params.id)
      if (!transaction.value.reference || !transaction.value.reference.startsWith('Facture n°')) {
        router.push('/invoices')
      } else {
        invoice.value = JSON.parse(transaction.value.notes)
      }
    }
    if (!transaction.value.lines[1].id_account) {
      await Accounting.accounts
      transaction.value.lines[1].id_account = Accounting.accounts.find(acc => acc.code == 411).id // Other tier
      changeClient()
      addLine()
    }
    updateRef()
  }, { immediate: true })

  watch(invoice, val => {
    transaction.value.notes = JSON.stringify(val)
    transaction.value.lines[0].credit = transaction.value.lines[1].debit = sum.value - val.discount
  }, { deep: true })

  watch(devis, async () => updateRef())
  watch(() => transaction.value.date, updateRef)

  function addLine () {
    invoice.value.lines.push({
      label: '',
      qte: 1,
      price: 0,
    })
  }

  async function changeClient () {
    await Accounting.accounts
    const acc = Accounting.account(transaction.value.lines[1].id_account)
    invoice.value.client.name = acc.label
    invoice.value.client.address = acc.description
  }

  async function updateRef () {
    if (transaction.value.id) return
    const transactions = await Accounting.getTransactions({ type: TransactionType.CREDIT, date: transaction.value.date })
    const cpt = transactions.length
    refNum = String(cpt + 1).padStart(2, '0')
    transaction.value.reference = `${devis.value ? 'Devis' : 'Facture'} n°${transaction.value.date.replaceAll('-', '') + '-' + refNum}`
  }

  function duplicate () {
    router.push('/invoices/new')
    delete transaction.value.id
    transaction.value.date = toISODateString()
  }

  function convertBase64 (file) {
    return new Promise((resolve, reject) => {
      const fileReader = new FileReader()
      fileReader.readAsDataURL(file)

      fileReader.addEventListener('load', () => {
        resolve(fileReader.result)
      })

      fileReader.addEventListener('error', error => {
        reject(error)
      })
    })
  }

  async function uploadImage (event) {
    const file = event.target.files[0]
    clientImg.value = await convertBase64(file)
  }

  async function print () {
    const oldTitle = document.title
    document.title = transaction.value.reference
    window.print()
    document.title = oldTitle
  }

  async function save () {
    const result = await Accounting.save(transaction.value)
    confirmDialog.value = false
    router.push(`/invoices/${result.id}`)
  }
</script>

<style lang="scss">
#invoice {
  width: 210mm;
  height: 297mm;
  border: 1px solid black;
  * {
    font-family: 'Oswald';
    font-size: 11pt;
  }
  main {
    font-size: 13pt;
  }

  &.readonly {
    .d-print-none {
      display: none !important;
    }

    .d-print-inline {
      display: inline !important;
    }

    input,
    textarea {
      background: transparent !important;
      border: none !important;
      outline: none !important;
      -webkit-appearance: textfield !important;
      -moz-appearance: textfield !important;
      appearance: textfield !important;
    }
  }
}

@media print {
  @page {
    size: 210mm 297mm;
    margin: 0;
  }

  .d-print-none {
    display: none !important;
  }

  input,
  textarea {
    background: transparent !important;
    border: none !important;
    outline: none !important;
    -webkit-appearance: textfield !important;
    -moz-appearance: textfield !important;
    appearance: textfield !important;
  }

  body {
    visibility: hidden;
    width: 210mm;
    height: 297mm;
    overflow: hidden;
  }

  #invoice {
    visibility: visible;
    border: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
  }
}
</style>
