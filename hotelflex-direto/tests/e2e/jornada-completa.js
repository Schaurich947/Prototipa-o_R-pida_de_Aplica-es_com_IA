// tests/e2e/jornada-completa.js
// Teste de navegador da jornada: reservar, pagar via Pix, recusa e reenvio, aprovação,
// hora extra cobrada, adiamento, cancelamento com devolução, painel do hotel e perfil.
// Pré-requisitos: banco recém-criado (php bin/setup-database.php) e o app rodando.
// Uso: BASE_URL=http://localhost/PROTO_IA_03.11/hotelflex-direto node tests/e2e/jornada-completa.js
const fs = require('fs')
const os = require('os')
const path = require('path')
const { chromium } = require('playwright')

const BASE = process.env.BASE_URL || 'http://localhost/hotelflex-direto'
const OUT = fs.mkdtempSync(path.join(os.tmpdir(), 'hotelflex-e2e-'))
const PDF = path.join(OUT, 'comprovante.pdf')
const FAKE = path.join(OUT, 'falso.pdf')
fs.writeFileSync(PDF, '%PDF-1.4\n1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n2 0 obj << /Type /Pages /Kids [] /Count 0 >> endobj\ntrailer << /Root 1 0 R >>\n%%EOF\n')
fs.writeFileSync(FAKE, '<?php echo "não é um PDF"; ?>')
const errors = []
const log = (...a) => console.log(...a)
const flash = async (p) => (await p.locator('.alert').allInnerTexts()).join(' | ') || '(sem flash)'
const inDays = (n) => { const d = new Date(); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10) }

async function login(browser, email, viewport = { width: 1280, height: 900 }) {
  const page = await (await browser.newContext({ viewport })).newPage()
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()) })
  page.on('pageerror', (e) => errors.push(e.message))
  page.on('dialog', (d) => d.accept())
  await page.goto(BASE + '/login')
  await page.fill('#login-email', email); await page.fill('#login-password', 'Demo@1234')
  await page.getByRole('button', { name: 'Entrar' }).click(); await page.waitForLoadState('networkidle')
  return page
}
const charges = async (p) => (await p.locator('li:has(> div > span.badge)').allInnerTexts()).map((t) => t.replace(/\s+/g, ' ').slice(0, 90))

;(async () => {
  const browser = await chromium.launch()
  const ana = await login(browser, 'ana@hotelflex.test')
  await ana.goto(`${BASE}/hospede/nova-reserva?entrada=${inDays(6)}&diarias=2&cidade=São Paulo`)
  await ana.locator('article').first().getByRole('button', { name: 'Reservar' }).click(); await ana.waitForLoadState('networkidle')
  const reservationUrl = ana.url(); log('1 reservar ->', reservationUrl.replace(BASE, ''), await flash(ana))
  log('1 status:', await ana.locator('main header .badge').first().innerText(), '| pix:', await ana.locator('.font-mono').first().innerText())
  await ana.screenshot({ path: OUT + '/01-aguardando-pagamento.png', fullPage: true })
  await ana.setInputFiles('input[name=receipt]', FAKE); await ana.getByRole('button', { name: 'Enviar comprovante' }).click(); await ana.waitForLoadState('networkidle')
  log('2 arquivo falso:', await flash(ana))
  await ana.setInputFiles('input[name=receipt]', PDF); await ana.getByRole('button', { name: 'Enviar comprovante' }).click(); await ana.waitForLoadState('networkidle')
  log('2 comprovante:', await flash(ana), '| etapa:', await ana.locator('[aria-current=step]').innerText())

  const staff = await login(browser, 'recepcao.centro@hotelflex.test')
  await staff.goto(BASE + '/hotel/pagamentos'); log('3 a conferir:', await staff.locator('article h2').allInnerTexts())
  await staff.screenshot({ path: OUT + '/02-pagamentos-hotel.png', fullPage: true })
  const anaCard = () => staff.locator('article:has-text("Ana Souza")')
  await anaCard().locator('input[name=reason]').fill('Valor diferente do total da reserva'); await anaCard().getByRole('button', { name: 'Recusar' }).click(); await staff.waitForLoadState('networkidle')
  log('3 recusar:', await flash(staff))
  await ana.goto(reservationUrl); log('3 ana vê:', await flash(ana))
  await ana.setInputFiles('input[name=receipt]', PDF); await ana.getByRole('button', { name: 'Enviar comprovante' }).click(); await ana.waitForLoadState('networkidle')
  await staff.goto(BASE + '/hotel/pagamentos'); await anaCard().getByRole('button', { name: 'Aprovar pagamento' }).click(); await staff.waitForLoadState('networkidle')
  log('4 aprovar:', await flash(staff))
  await ana.goto(reservationUrl); log('4 status ana:', await ana.locator('main header .badge').first().innerText())

  await ana.locator('#guest-late_checkout').fill('3'); await ana.locator('form:has(#guest-late_checkout)').getByRole('button', { name: 'Pedir ao hotel' }).click(); await ana.waitForLoadState('networkidle')
  await staff.goto(BASE + '/hotel/solicitacoes'); await staff.locator('article:has-text("Ana Souza"):has-text("Saída tardia")').getByRole('button', { name: 'Aprovar' }).click(); await staff.waitForLoadState('networkidle')
  log('5 aprovar saída tardia:', await flash(staff))
  await ana.goto(reservationUrl); log('5 cobranças:', await charges(ana))
  await ana.fill('#change-check-in', inDays(7)); await ana.fill('#change-nights', '3'); await ana.getByRole('button', { name: 'Salvar novas datas' }).click(); await ana.waitForLoadState('networkidle')
  log('6 adiar:', await flash(ana)); log('6 cobranças:', await charges(ana))
  await ana.screenshot({ path: OUT + '/03-reserva-cobrancas.png', fullPage: true })
  log('6 whatsapp hotel:', await ana.locator('a.btn-whatsapp').getAttribute('href'))
  await ana.getByRole('button', { name: 'Cancelar reserva' }).click(); await ana.waitForLoadState('networkidle')
  log('7 cancelar:', await flash(ana), '| cobranças:', await charges(ana))

  await staff.goto(BASE + '/hotel/liberacao')
  log('8 elisa:', (await staff.locator('article:has-text("Elisa Prado")').innerText()).includes('Aguardando pagamento'), '| check-in elisa?', await staff.locator('article:has-text("Elisa Prado")').getByRole('button', { name: 'Registrar check-in' }).count())
  await staff.locator('article:has(h2:text("Quarto 101"))').getByRole('button', { name: 'Liberar para o Hóspede' }).click(); await staff.waitForLoadState('networkidle')
  log('8 liberar 101:', await flash(staff))
  await staff.screenshot({ path: OUT + '/04-painel.png', fullPage: true })

  await ana.goto(BASE + '/perfil'); await ana.fill('#whatsapp-phone', '(49) 99127-7070'); await ana.getByRole('button', { name: 'Salvar preferências' }).click(); await ana.waitForLoadState('networkidle')
  log('9 perfil:', await flash(ana))
  const mobile = await login(browser, 'admin@hotelflex.test', { width: 390, height: 844 })
  log('10 admin menu:', (await mobile.locator('nav[aria-label="Navegação principal"]').innerText()).replace(/\s+/g, ' '), '| scrollWidth', await mobile.evaluate(() => document.documentElement.scrollWidth))
  await mobile.screenshot({ path: OUT + '/05-admin-celular.png' })
  log('erros de console:', errors.filter((e) => !e.includes('404')))
  log('capturas de tela em', OUT)
  await browser.close()
})().catch((e) => { console.error(e); process.exit(1) })
