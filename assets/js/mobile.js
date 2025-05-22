/**
 * Mobile-specific JavaScript for Vamsi Crackers E-commerce Platform
 */

document.addEventListener("DOMContentLoaded", () => {
  // Mobile menu functionality
  initMobileMenu()

  // Mobile bottom navigation
  initMobileBottomNav()

  // Touch-friendly product cards
  initTouchFriendlyCards()

  // Mobile-specific image handling
  initLazyLoading()

  // Form enhancements for mobile
  initMobileFormEnhancements()

  // Detect and handle orientation changes
  handleOrientationChanges()

  // Mobile swipe functionality
  initSwipeActions()

  // Mobile-specific checkout enhancements
  initMobileCheckout()
})

/**
 * Initialize mobile menu functionality
 */
function initMobileMenu() {
  const menuToggle = document.querySelector(".mobile-menu-toggle")
  const mobileMenu = document.querySelector(".mobile-menu")
  const menuClose = document.querySelector(".mobile-menu-close")
  const overlay = document.createElement("div")
  overlay.className = "mobile-menu-overlay"
  document.body.appendChild(overlay)

  if (menuToggle && mobileMenu) {
    // Toggle menu on hamburger icon click
    menuToggle.addEventListener("click", (e) => {
      e.preventDefault()
      mobileMenu.classList.add("active")
      overlay.classList.add("active")
      document.body.classList.add("menu-open")
    })

    // Close menu on X button click
    if (menuClose) {
      menuClose.addEventListener("click", (e) => {
        e.preventDefault()
        closeMenu()
      })
    }

    // Close menu on overlay click
    overlay.addEventListener("click", () => {
      closeMenu()
    })

    // Handle submenu toggles
    const submenuItems = document.querySelectorAll(".mobile-menu-items ul li.has-submenu > a")
    submenuItems.forEach((item) => {
      item.addEventListener("click", function (e) {
        e.preventDefault()
        const parent = this.parentNode
        parent.classList.toggle("active")
      })
    })
  }

  function closeMenu() {
    mobileMenu.classList.remove("active")
    overlay.classList.remove("active")
    document.body.classList.remove("menu-open")
  }
}

/**
 * Initialize mobile bottom navigation
 */
function initMobileBottomNav() {
  const bottomNavItems = document.querySelectorAll(".mobile-bottom-nav-item")

  if (bottomNavItems.length) {
    // Set active state based on current page
    const currentPath = window.location.pathname

    bottomNavItems.forEach((item) => {
      const link = item.getAttribute("data-href")
      if (link && currentPath.includes(link)) {
        item.classList.add("active")
      }

      item.addEventListener("click", function () {
        const href = this.getAttribute("data-href")
        if (href) {
          window.location.href = href
        }
      })
    })
  }
}

/**
 * Make product cards more touch-friendly
 */
function initTouchFriendlyCards() {
  const productCards = document.querySelectorAll(".product-card")

  productCards.forEach((card) => {
    // Add touch feedback
    card.addEventListener("touchstart", function () {
      this.classList.add("touch-active")
    })

    card.addEventListener("touchend", function () {
      this.classList.remove("touch-active")
    })

    // Separate click handlers for card and buttons
    const cardButtons = card.querySelectorAll(".btn")
    const cardLink = card.querySelector(".product-card-link")

    cardButtons.forEach((button) => {
      button.addEventListener("click", (e) => {
        e.stopPropagation() // Prevent card click
      })
    })

    if (cardLink) {
      card.addEventListener("click", (e) => {
        if (e.target === card || e.target.closest(".product-card-image") || e.target.closest(".product-card-title")) {
          window.location.href = cardLink.getAttribute("href")
        }
      })
    }
  })
}

/**
 * Initialize lazy loading for images
 */
function initLazyLoading() {
  if ("loading" in HTMLImageElement.prototype) {
    // Browser supports native lazy loading
    const lazyImages = document.querySelectorAll('img[loading="lazy"]')
    lazyImages.forEach((img) => {
      img.src = img.dataset.src
    })
  } else {
    // Fallback for browsers that don't support native lazy loading
    let lazyImages = document.querySelectorAll(".lazy-image") // Changed from const to let

    if ("IntersectionObserver" in window) {
      const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const image = entry.target
            image.src = image.dataset.src
            image.classList.remove("lazy-image")
            imageObserver.unobserve(image)
          }
        })
      })

      lazyImages.forEach((image) => {
        imageObserver.observe(image)
      })
    } else {
      // Fallback for older browsers without IntersectionObserver
      let active = false

      const lazyLoad = () => {
        if (active === false) {
          active = true

          setTimeout(() => {
            lazyImages.forEach((image) => {
              if (
                image.getBoundingClientRect().top <= window.innerHeight &&
                image.getBoundingClientRect().bottom >= 0 &&
                getComputedStyle(image).display !== "none"
              ) {
                image.src = image.dataset.src
                image.classList.remove("lazy-image")

                lazyImages = lazyImages.filter((img) => img !== image)

                if (lazyImages.length === 0) {
                  document.removeEventListener("scroll", lazyLoad)
                  window.removeEventListener("resize", lazyLoad)
                  window.removeEventListener("orientationchange", lazyLoad)
                }
              }
            })

            active = false
          }, 200)
        }
      }

      document.addEventListener("scroll", lazyLoad)
      window.addEventListener("resize", lazyLoad)
      window.addEventListener("orientationchange", lazyLoad)
      lazyLoad()
    }
  }
}

/**
 * Enhance forms for mobile devices
 */
function initMobileFormEnhancements() {
  // Prevent zoom on iOS by setting font-size to 16px or larger
  const formInputs = document.querySelectorAll("input, select, textarea")

  formInputs.forEach((input) => {
    // Add better touch feedback
    input.addEventListener("focus", function () {
      this.parentNode.classList.add("input-focused")
    })

    input.addEventListener("blur", function () {
      this.parentNode.classList.remove("input-focused")
    })

    // Handle numeric inputs better on mobile
    if (input.type === "number") {
      // Some mobile browsers have issues with number inputs
      input.addEventListener("keydown", (e) => {
        // Allow: backspace, delete, tab, escape, enter
        if (
          [46, 8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
          // Allow: Ctrl+A
          (e.keyCode === 65 && e.ctrlKey === true) ||
          // Allow: Ctrl+C
          (e.keyCode === 67 && e.ctrlKey === true) ||
          // Allow: Ctrl+V
          (e.keyCode === 86 && e.ctrlKey === true) ||
          // Allow: home, end, left, right
          (e.keyCode >= 35 && e.keyCode <= 39)
        ) {
          return
        }
        // Ensure that it's a number and stop the keypress if not
        if ((e.shiftKey || e.keyCode < 48 || e.keyCode > 57) && (e.keyCode < 96 || e.keyCode > 105)) {
          e.preventDefault()
        }
      })
    }
  })

  // Enhance quantity inputs for touch
  const quantityInputs = document.querySelectorAll(".quantity-input")

  quantityInputs.forEach((wrapper) => {
    const input = wrapper.querySelector("input")
    const decreaseBtn = wrapper.querySelector(".quantity-decrease")
    const increaseBtn = wrapper.querySelector(".quantity-increase")

    if (input && decreaseBtn && increaseBtn) {
      // Make buttons larger for touch
      decreaseBtn.classList.add("touch-friendly")
      increaseBtn.classList.add("touch-friendly")

      // Add touch feedback
      ;[decreaseBtn, increaseBtn].forEach((btn) => {
        btn.addEventListener("touchstart", function () {
          this.classList.add("touch-active")
        })

        btn.addEventListener("touchend", function () {
          this.classList.remove("touch-active")
        })
      })
    }
  })
}

/**
 * Handle orientation changes
 */
function handleOrientationChanges() {
  window.addEventListener("orientationchange", () => {
    // Adjust UI elements after orientation change
    setTimeout(() => {
      // Fix iOS height issue after orientation change
      document.documentElement.style.height = "100%"
      setTimeout(() => {
        window.scrollTo(0, 1)
      }, 500)

      // Recalculate product grid layout
      const productGrid = document.querySelector(".product-grid")
      if (productGrid) {
        productGrid.style.opacity = "0.5"
        setTimeout(() => {
          productGrid.style.opacity = "1"
        }, 300)
      }

      // Adjust image sliders if any
      const sliders = document.querySelectorAll(".mobile-slider")
      sliders.forEach((slider) => {
        // Trigger recalculation for sliders
        const event = new Event("resize")
        window.dispatchEvent(event)
      })
    }, 300)
  })
}

/**
 * Initialize swipe actions for mobile
 */
function initSwipeActions() {
  // For product image galleries
  const productGalleries = document.querySelectorAll(".product-gallery")

  productGalleries.forEach((gallery) => {
    let startX, startY, distX, distY
    let startTime
    const threshold = 150 // Minimum distance for swipe
    const restraint = 100 // Maximum perpendicular distance
    const allowedTime = 300 // Maximum time allowed for swipe

    gallery.addEventListener(
      "touchstart",
      (e) => {
        const touchObj = e.changedTouches[0]
        startX = touchObj.pageX
        startY = touchObj.pageY
        startTime = new Date().getTime()
      },
      false,
    )

    gallery.addEventListener(
      "touchend",
      (e) => {
        const touchObj = e.changedTouches[0]
        distX = touchObj.pageX - startX
        distY = touchObj.pageY - startY
        const elapsedTime = new Date().getTime() - startTime

        if (elapsedTime <= allowedTime) {
          if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
            // Horizontal swipe
            if (distX > 0) {
              // Swipe right - previous image
              const prevBtn = gallery.querySelector(".gallery-prev")
              if (prevBtn) prevBtn.click()
            } else {
              // Swipe left - next image
              const nextBtn = gallery.querySelector(".gallery-next")
              if (nextBtn) nextBtn.click()
            }
          }
        }
      },
      false,
    )
  })

  // For cart items (swipe to delete)
  const cartItems = document.querySelectorAll(".cart-item")

  cartItems.forEach((item) => {
    let startX, startY, distX, distY
    let startTime
    const threshold = 150
    const restraint = 100
    const allowedTime = 300

    item.addEventListener(
      "touchstart",
      (e) => {
        const touchObj = e.changedTouches[0]
        startX = touchObj.pageX
        startY = touchObj.pageY
        startTime = new Date().getTime()

        // Reset any previously swiped items
        document.querySelectorAll(".cart-item.swiped").forEach((swipedItem) => {
          if (swipedItem !== item) {
            swipedItem.classList.remove("swiped")
          }
        })
      },
      false,
    )

    item.addEventListener(
      "touchmove",
      (e) => {
        const touchObj = e.changedTouches[0]
        distX = touchObj.pageX - startX

        if (distX < 0 && Math.abs(distX) < 100) {
          item.style.transform = `translateX(${distX}px)`
        }
      },
      false,
    )

    item.addEventListener(
      "touchend",
      (e) => {
        const touchObj = e.changedTouches[0]
        distX = touchObj.pageX - startX
        distY = touchObj.pageY - startY
        const elapsedTime = new Date().getTime() - startTime

        if (elapsedTime <= allowedTime) {
          if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
            if (distX < 0) {
              // Swipe left - show delete button
              item.classList.add("swiped")
            } else {
              // Swipe right - hide delete button
              item.classList.remove("swiped")
            }
          } else {
            // Reset position
            item.style.transform = ""
          }
        } else {
          // Reset position
          item.style.transform = ""
        }
      },
      false,
    )
  })
}

/**
 * Mobile-specific checkout enhancements
 */
function initMobileCheckout() {
  // Make checkout steps scrollable on mobile
  const checkoutSteps = document.querySelector(".checkout-steps")

  if (checkoutSteps) {
    // Scroll to active step
    const activeStep = checkoutSteps.querySelector(".active")
    if (activeStep) {
      setTimeout(() => {
        activeStep.scrollIntoView({
          behavior: "smooth",
          block: "nearest",
          inline: "center",
        })
      }, 300)
    }
  }

  // Enhance address form with autocomplete
  const addressInputs = document.querySelectorAll('input[name="address"]')

  addressInputs.forEach((input) => {
    // Check if browser supports Geolocation API
    if (navigator.geolocation) {
      const locationBtn = document.createElement("button")
      locationBtn.type = "button"
      locationBtn.className = "location-btn"
      locationBtn.innerHTML = '<i class="fas fa-map-marker-alt"></i>'
      locationBtn.title = "Use my current location"

      input.parentNode.style.position = "relative"
      input.parentNode.appendChild(locationBtn)

      locationBtn.addEventListener("click", () => {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            // Here you would typically use a reverse geocoding service
            // For demo purposes, we'll just show coordinates
            const lat = position.coords.latitude
            const lng = position.coords.longitude

            // In a real implementation, you would call a geocoding service
            // For now, just update with coordinates
            input.value = `Location detected (${lat.toFixed(6)}, ${lng.toFixed(6)})`

            // Trigger a custom event that your checkout logic might listen for
            const event = new CustomEvent("locationDetected", {
              detail: { latitude: lat, longitude: lng },
            })
            document.dispatchEvent(event)
          },
          (error) => {
            console.error("Error getting location:", error)
            alert("Could not detect your location. Please enter your address manually.")
          },
        )
      })
    }
  })

  // Enhance payment method selection for touch
  const paymentMethods = document.querySelectorAll(".payment-method")

  paymentMethods.forEach((method) => {
    method.addEventListener("click", function () {
      // Remove active class from all methods
      paymentMethods.forEach((m) => {
        m.classList.remove("active")
      })

      // Add active class to clicked method
      this.classList.add("active")

      // Check the radio button
      const radio = this.querySelector('input[type="radio"]')
      if (radio) {
        radio.checked = true
      }
    })
  })
}

/**
 * Check if device is iOS
 */
function isIOS() {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
}

/**
 * Check if device is Android
 */
function isAndroid() {
  return /Android/.test(navigator.userAgent)
}

/**
 * Detect if using a mobile device
 */
function isMobile() {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
}

/**
 * Optimize images based on connection speed
 */
window.addEventListener("load", () => {
  if ("connection" in navigator) {
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection

    if (connection) {
      if (connection.effectiveType === "slow-2g" || connection.effectiveType === "2g") {
        // Load low quality images for slow connections
        document.querySelectorAll("img[data-src-low]").forEach((img) => {
          img.src = img.getAttribute("data-src-low")
        })

        // Disable animations for slow connections
        document.body.classList.add("reduce-motion")
      }

      if (connection.saveData) {
        // User has requested reduced data usage
        document.body.classList.add("save-data")

        // Disable auto-playing videos
        document.querySelectorAll("video[autoplay]").forEach((video) => {
          video.removeAttribute("autoplay")
          video.pause()
        })
      }
    }
  }
})
