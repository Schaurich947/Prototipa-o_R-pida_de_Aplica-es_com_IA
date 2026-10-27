// public/assets/js/late-checkout.js
// Live preview of the proportional late check-out fee. The server recalculates
// the real value on submit; this script only improves feedback while sliding.
const currencyFormatter = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

function formatMoney(cents) {
  return currencyFormatter.format(cents / 100)
}

function formatHour(hour) {
  return `${String(hour).padStart(2, '0')}:00`
}

function formatHours(hours) {
  return hours === 1 ? '1 hora' : `${hours} horas`
}

function createSpan(text, className) {
  const span = document.createElement('span')
  span.className = className
  span.textContent = text
  return span
}

function renderPreview(previewElement, hours, hourlyRateCents, standardCheckoutHour) {
  const checkoutTime = formatHour(standardCheckoutHour + hours)
  if (hours === 0) {
    previewElement.replaceChildren(createSpan(`Saída padrão às ${checkoutTime}`, 'text-muted-foreground'))
    return
  }
  previewElement.replaceChildren(
    createSpan(`+${formatHours(hours)}`, 'font-medium'),
    document.createTextNode(' = '),
    createSpan(`+${formatMoney(hours * hourlyRateCents)}`, 'font-semibold text-success'),
    createSpan(` · saída às ${checkoutTime}`, 'text-muted-foreground'),
  )
}

function renderBilling(billingElement, additionalCents) {
  if (!billingElement) return
  const baseCents = Number(billingElement.dataset.baseCents)
  billingElement.querySelector('[data-billing-additional]').textContent = formatMoney(additionalCents)
  billingElement.querySelector('[data-billing-total]').textContent = formatMoney(baseCents + additionalCents)
}

function bindLateCheckout(container) {
  const rangeInput = container.querySelector('input[type="range"]')
  const previewElement = container.querySelector('[data-preview]')
  const billingElement = container.closest('form')?.querySelector('[data-billing]')
  const hourlyRateCents = Number(container.dataset.hourlyRateCents)
  const standardCheckoutHour = Number(container.dataset.standardCheckoutHour)

  rangeInput.addEventListener('input', () => {
    const hours = Number(rangeInput.value)
    renderPreview(previewElement, hours, hourlyRateCents, standardCheckoutHour)
    renderBilling(billingElement, hours * hourlyRateCents)
    container.closest('form')?.setAttribute('data-dirty', 'true')
  })
}

function bindAutoSubmit(form) {
  form.querySelector('[data-auto-submit-fallback]')?.classList.add('hidden')
  form.querySelector('select')?.addEventListener('change', () => form.requestSubmit())
}

document.querySelectorAll('[data-late-checkout]').forEach(bindLateCheckout)
document.querySelectorAll('form[data-auto-submit]').forEach(bindAutoSubmit)
