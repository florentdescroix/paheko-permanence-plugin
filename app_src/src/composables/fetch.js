import { reactive } from 'vue'

const BASE = import.meta.env.VITE_API_BASE

const values = reactive({})
const promises = {}

/**
 * Always return an object, that'll be populated when the response arrives
 * As `values` is reactive, all component using it will react when the value changes
 * @param {String} path from the API base url
 * @param {any} defaultValue
 * @returns a thennable object so it can be await
 */
export function useGet (path, defaultValue = []) {
  const promise = promises[path] ??= query(path)
    .then(res => values[path] = res) // that triggers reactivity
    .finally(() => setTimeout(() => delete promises[path], 10 * 60 * 1000)) // Revoke the cache after 10 minutes

  return new Proxy(values[path] ?? defaultValue, {
    get (target, prop, receiver) {
      // Add a `then` function to the object throught a Proxy
      return prop === 'then' ? promise.then.bind(promise) : Reflect.get(target, prop, receiver)
    },
  })
}

/**
 * Call the API and wait for it's result.
 * If a cache exists on the same path (or sub-path), removes it
 * @param {String} path from the API base url
 * @param {Object} body to send
 * @returns a Promise
 */
export async function usePost (path, body) {
  const out = await query(path, { method: 'POST', body })
  for (const prop in promises) {
    if (prop.startsWith(path)) {
      delete promises[prop]
    }
  }
  return out
}
export async function useDelete (path) {
  const out = await query(path, { method: 'DELETE' })
  for (const prop in promises) {
    if (path.startsWith(prop)) {
      delete promises[prop]
    }
  }
  return out
}

/**
 * Custom fetch for the API
 * @param {String} path from the API base url
 * @param {Object} body for POST requests
 * @returns response of the API
 */
async function query (path, options = {}) {
  options.headers ??= {}
  options.headers['Content-Type'] = 'application/json'
  options.credentials = 'include'
  if (options.body) {
    options.body = JSON.stringify(options.body)
  }
  const resp = await fetch(`${BASE}${path}`, options)
  const json = await resp.json()
  if (resp.status !== 200) {
    throw json
  }
  return json
}
