export function toISODateString (date = new Date()) {
  const offset = date.getTimezoneOffset()
  const offsetDate = new Date(date.getTime() - (offset * 60 * 1000))
  return offsetDate.toISOString().split('T', 1)[0]
}