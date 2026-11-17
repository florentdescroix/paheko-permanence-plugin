import { ref } from 'vue'
import { useGet, usePost } from '../composables/fetch'

export const user = ref()

export const Organization = {
  get config () {
    return useGet(`/organization/config`, {})
  },
}

export const User = {
  get onDuty () {
    return useGet('/user?permissions[]=connect')
  },
  get fields () {
    return useGet('/user/fields')
  },
  get services () {
    return useGet('/user/services')
  },
  get fees () {
    return User.services.flatMap(s => s.fees.map(f => ({ ...f, service: s })))
  },
  get: id => useGet(`/user/${id}`),
  findByName: str => useGet(`/user?search=${str.toLowerCase()}`),
  create: user => usePost(`/user`, user),
  update: user => usePost(`/user/${user.id}`, user),
}

export const Accounting = {
  get years () {
    return useGet('/accounting/years')
  },
  get types () {
    return useGet('/accounting/types')
  },
  get accounts () {
    return useGet('/accounting/accounts')
  },
  get templates () {
    return useGet(`/accounting/templates`)
  },
  get model () {
    return useGet('/accounting/model')
  },
  // addTransaction: () => usePost(`/template/transactions`),
  // saveTransaction: data => usePost(`/template/transactions/${data.id}`, data),
  // removeTransaction: id => useDelete(`/template/transactions/${id}`),
  getTransactions: query => useGet(`/accounting/transactions${query ? '?' + new URLSearchParams(query) : ''}`),
  getTransaction: id => useGet(`/accounting/transactions/${id}`),
  account: id => Accounting.accounts.find(acc => acc.id == id),
  balances: (after = null, before = null) => useGet(`/accounting/balances?after=${after}&before=${before}`, {}),
  save: transaction => usePost('/accounting/transactions', { ...transaction, id_creator: user.value.id }),
  createAccount: account => usePost('/accounting/accounts', account),
}

export const Equipment = {
  get config () {
    return useGet('/equipment/config')
  },
  get categories () {
    return useGet('/equipment/category')
  },
  get equipments () {
    return useGet('/equipment')
  },
  get: idOrKey => useGet(`/equipment/${idOrKey}`),
  addEquipment: eq => usePost(`/equipment`, eq),
  addMovement: movement => usePost(`/equipment/${movement.equipment}`, movement),
}

export const HelloAsso = {
  get forms () {
    return useGet(`/helloasso/forms`)
  },
  sync: () => useGet(`/helloasso/forms?sync`),
}

export const stats = (from, to) => useGet(`/stats?from=${from}&to=${to}`)

export const TransactionType = {
  ADVANCED: 0,
  REVENUE: 1,
  EXPENSE: 2,
  TRANSFER: 3,
  DEBT: 4,
  CREDIT: 5,
}

export const AccountType = {
  NONE: 0,
  BANK: 1,
  CASH: 2,
  OUTSTANDING: 3,
  THIRD_PARTY: 4,
  EXPENSE: 5,
  REVENUE: 6,
}
