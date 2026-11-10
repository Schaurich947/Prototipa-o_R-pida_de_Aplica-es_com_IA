// public/assets/js/date-range.js
// Shows the number of nights while the guest picks check-in and check-out,
// and keeps check-out at least one day after check-in. The server validates again.
const DAY_MS = 24 * 60 * 60 * 1000

function parseDate(value) {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return null
  const [year, month, day] = value.split('-').map(Number)
  return Date.UTC(year, month - 1, day)
}

function formatDate(timestamp) {
  return new Date(timestamp).toISOString().slice(0, 10)
}

function bindDateRange(container) {
  const checkIn = container.querySelector('[data-check-in]')
  const checkOut = container.querySelector('[data-check-out]')
  const output = container.querySelector('[data-nights-output]')

  const update = () => {
    const start = parseDate(checkIn.value)
    const end = parseDate(checkOut.value)
    if (start !== null) {
      checkOut.min = formatDate(start + DAY_MS)
      if (end === null || end <= start) checkOut.value = formatDate(start + DAY_MS)
    }
    const nights = start !== null && parseDate(checkOut.value) !== null ? Math.round((parseDate(checkOut.value) - start) / DAY_MS) : null
    output.textContent = nights !== null && nights > 0 ? String(nights) : '—'
  }

  checkIn.addEventListener('change', update)
  checkOut.addEventListener('change', update)
}

document.querySelectorAll('[data-date-range]').forEach(bindDateRange)
