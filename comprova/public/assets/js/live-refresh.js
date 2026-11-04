// public/assets/js/live-refresh.js
// Polls a lightweight version endpoint so housekeeping updates show up on the
// reception and guest screens without manual reloads (MVP hypothesis 1).
const POLL_INTERVAL_MS = 8000

function isUserEditing() {
  const activeElement = document.activeElement
  const isTyping = activeElement && ['INPUT', 'SELECT', 'TEXTAREA'].includes(activeElement.tagName)
  return isTyping || document.querySelector('form[data-dirty="true"]') !== null
}

async function fetchVersion(versionUrl) {
  const response = await fetch(versionUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' })
  if (!response.ok) throw new Error(`HTTP ${response.status}`)
  const payload = await response.json()
  return payload.version
}

function startLiveRefresh(versionUrl) {
  let knownVersion = null

  const checkForChanges = async () => {
    if (document.hidden) return
    try {
      const version = await fetchVersion(versionUrl)
      const hasChanged = knownVersion !== null && version !== knownVersion
      knownVersion = version
      if (hasChanged && !isUserEditing()) window.location.reload()
    } catch (error) {
      console.warn('Atualização automática indisponível no momento.', error)
    }
  }

  checkForChanges()
  window.setInterval(checkForChanges, POLL_INTERVAL_MS)
}

const versionUrl = document.body.dataset.versionUrl
if (versionUrl) startLiveRefresh(versionUrl)
