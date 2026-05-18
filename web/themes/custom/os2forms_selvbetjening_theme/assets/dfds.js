import './scss/dfds.scss'
import * as DKFDS from 'dkfds'

document.addEventListener('DOMContentLoaded', function () {
  DKFDS.init()
  // DKFDS expects .fds-modal-container as a direct child of <body>
  // so that its inert-handling doesn't disable the modal content.
  const containers = document.querySelectorAll('.fds-modal-container')
  for (let i = 0; i < containers.length; i++) {
    document.body.appendChild(containers[i])
  }
})
