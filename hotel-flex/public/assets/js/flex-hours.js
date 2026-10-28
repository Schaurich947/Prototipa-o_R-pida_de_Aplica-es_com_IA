// public/assets/js/flex-hours.js
// Live preview of early check-in / late check-out hours and their cost.
// The server recalculates everything on submit; this only improves feedback.
const currencyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

const formatMoney = (cents) => currencyFormatter.format(cents / 100)
const formatHour = (hour) => `${String(hour).padStart(2, '0')}:00`
const formatHours = (hours) => (hours === 1 ? '1 hora' : `${hours} horas`)

function createSpan(text, className) {
  const span = document.createElement('span')
  span.className = className
  span.textContent = text
  return span
}

function calculateFee(settings, hours) {
  const chargedHours = Math.max(0, hours - settings.freeHours)
  return chargedHours * settings.hourlyRateCents
}

function renderPreview(previewElement, settings, hours, feeCents) {
  const isEarly = settings.kind === 'early'
  const momentHour = isEarly ? settings.baseHour - hours : settings.baseHour + hours
  const parts = [createSpan(`${isEarly ? 'Entrada' : 'Saída'} às ${formatHour(momentHour)}`, 'font-medium')]
  if (hours > 0) {
    parts.push(createSpan(` · ${formatHours(hours)} ${isEarly ? 'antes' : 'depois'} · `, 'text-muted-foreground'))
    parts.push(createSpan(feeCents > 0 ? `+${formatMoney(feeCents)}` : 'sem custo', feeCents > 0 ? 'font-semibold text-success' : 'font-semibold'))
  }
  previewElement.replaceChildren(...parts)
}

function renderBilling(billingElement, kind, feeCents) {
  if (!billingElement) return
  billingElement.dataset[`${kind}Cents`] = String(feeCents)
  const { baseCents, earlyCents, lateCents } = billingElement.dataset
  billingElement.querySelector(`[data-billing-line="${kind}"]`).textContent = formatMoney(feeCents)
  const totalCents = Number(baseCents) + Number(earlyCents) + Number(lateCents)
  billingElement.querySelector('[data-billing-total]').textContent = formatMoney(totalCents)
}

function bindFlexHours(container) {
  const rangeInput = container.querySelector('input[type="range"]')
  const form = container.closest('form')
  const settings = {
    kind: container.dataset.kind,
    baseHour: Number(container.dataset.baseHour),
    hourlyRateCents: Number(container.dataset.hourlyRateCents),
    freeHours: Number(container.dataset.freeHours),
  }

  rangeInput.addEventListener('input', () => {
    const hours = Number(rangeInput.value)
    const feeCents = calculateFee(settings, hours)
    renderPreview(container.querySelector('[data-preview]'), settings, hours, feeCents)
    renderBilling(form?.querySelector('[data-billing]'), settings.kind, feeCents)
    form?.setAttribute('data-dirty', 'true')
  })
}

function bindAutoSubmit(form) {
  form.querySelector('[data-auto-submit-fallback]')?.classList.add('hidden')
  form.querySelector('select')?.addEventListener('change', () => form.requestSubmit())
}

function bindConfirmation(form) {
  form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault()
  })
}

document.querySelectorAll('[data-flex-hours]').forEach(bindFlexHours)
document.querySelectorAll('form[data-auto-submit]').forEach(bindAutoSubmit)
document.querySelectorAll('form[data-confirm]').forEach(bindConfirmation)
