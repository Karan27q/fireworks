document.addEventListener("DOMContentLoaded", () => {
  // Toggle sidebar on mobile
  const menuToggle = document.getElementById("menu-toggle")
  const sidebar = document.querySelector(".sidebar")

  if (menuToggle) {
    menuToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active")
    })
  }

  // User dropdown toggle
  const userDropdownToggle = document.getElementById("user-dropdown-toggle")
  const userDropdownMenu = document.getElementById("user-dropdown-menu")

  if (userDropdownToggle && userDropdownMenu) {
    userDropdownToggle.addEventListener("click", () => {
      userDropdownMenu.classList.toggle("active")
    })

    // Close dropdown when clicking outside
    document.addEventListener("click", (event) => {
      if (!userDropdownToggle.contains(event.target) && !userDropdownMenu.contains(event.target)) {
        userDropdownMenu.classList.remove("active")
      }
    })
  }

  // Data table sorting
  const dataTable = document.querySelector(".data-table")
  if (dataTable) {
    const headers = dataTable.querySelectorAll("th")

    headers.forEach((header) => {
      header.addEventListener("click", function () {
        const index = Array.from(headers).indexOf(this)
        sortTable(dataTable, index)
      })
    })
  }

  // Function to sort table
  function sortTable(table, column) {
    const tbody = table.querySelector("tbody")
    const rows = Array.from(tbody.querySelectorAll("tr"))

    // Sort rows
    const sortedRows = rows.sort((a, b) => {
      const aValue = a.cells[column].textContent.trim()
      const bValue = b.cells[column].textContent.trim()

      // Check if values are numbers
      if (!isNaN(aValue) && !isNaN(bValue)) {
        return Number.parseFloat(aValue) - Number.parseFloat(bValue)
      }

      return aValue.localeCompare(bValue)
    })

    // Clear table body
    while (tbody.firstChild) {
      tbody.removeChild(tbody.firstChild)
    }

    // Add sorted rows
    sortedRows.forEach((row) => {
      tbody.appendChild(row)
    })
  }
})
