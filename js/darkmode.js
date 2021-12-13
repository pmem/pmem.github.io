// Dark mode

const dm_off = 'icon-dark'
const dm_on = 'icon-sun1'

document.addEventListener('DOMContentLoaded', function () {
  const darkSwitch = document.getElementById('darkSwitch')
  const toggle = document.getElementById('darkSwitchToggle')

  function initTheme() {
    const e = null !== localStorage.getItem('darkSwitch') && 'dark' === localStorage.getItem('darkSwitch')
    if (e) {
      toggle.classList.add(dm_on)
      document.body.setAttribute('data-theme', 'dark')
    } else {
      toggle.classList.add(dm_off)
      document.body.removeAttribute('data-theme')
    }
  }

  function resetTheme() {
    if (toggle.classList.contains(dm_off)) {
      document.body.setAttribute('data-theme', 'dark')
      localStorage.setItem('darkSwitch', 'dark')
    } else {
      document.body.removeAttribute('data-theme')
      localStorage.removeItem('darkSwitch')
    }
    toggle.classList.toggle(dm_off)
    toggle.classList.toggle(dm_on)
  }

  window.addEventListener('load', () => {
    darkSwitch &&
      (initTheme(),
      darkSwitch.addEventListener('click', () => {
        resetTheme()
      }))
  })
})
