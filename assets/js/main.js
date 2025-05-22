$(document).ready(() => {
  // Add to cart functionality
  $(".add-to-cart-btn").on("click", function () {
    const productId = $(this).data("product-id")

    $.ajax({
      url: "ajax/add_to_cart.php",
      type: "POST",
      data: {
        product_id: productId,
        quantity: 1,
      },
      success: (response) => {
        const data = JSON.parse(response)

        if (data.success) {
          // Update cart count
          const currentCount = Number.parseInt($(".cart-count").text()) || 0
          $(".cart-count").text(currentCount + 1)

          // Show success message
          alert("Product added to cart successfully!")
        } else {
          alert(data.message)
        }
      },
      error: () => {
        alert("An error occurred. Please try again.")
      },
    })
  })

  // Mobile menu toggle
  $(".mobile-menu-toggle").on("click", () => {
    $(".nav-menu").toggleClass("active")
  })
})
