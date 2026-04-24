const stickyEl = document.querySelector('.sticky-progress-bar')
const rect = document.querySelector('.sticky-progress-bar').getBoundingClientRect()
const topPosition = rect.top + 17 + window.scrollY
if (stickyEl) {
  window.addEventListener('scroll', function () {
    determineFixed(topPosition)
  })

  document.addEventListener('DOMContentLoaded', function (event) {
    determineFixed(topPosition)
  })
}

function determineFixed (topPosition) {
  if (document.body.scrollTop >= topPosition || document.scrollingElement.scrollTop >= topPosition || document.documentElement.scrollTop >= topPosition) {
    stickyEl.classList.add('fixed')
  } else {
    stickyEl.classList.remove('fixed')
  }
}
