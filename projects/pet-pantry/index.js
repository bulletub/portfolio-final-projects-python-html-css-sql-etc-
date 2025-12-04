// Flatten productsData into one array
let allProducts = [];
if (typeof productsData === "object") {
  allProducts = Object.values(productsData).flat();
}

// Ensure unique products by id
const seen = new Set();
allProducts = allProducts.filter((p) => {
  if (seen.has(p.id)) return false;
  seen.add(p.id);
  return true;
});

let currentProductId = null;
let currentModalImages = [];
let currentImageIndex = 0;

/* ========== VIEW PRODUCT MODAL ========== */
function viewProduct(id) {
  const product = allProducts.find((p) => p.id == id);
  if (!product) return;

  currentProductId = id;

  // Parse images (fallback to single image or placeholder)
  try {
    currentModalImages =
      product.images && product.images.startsWith("[")
        ? JSON.parse(product.images)
        : [product.image || "https://via.placeholder.com/400"];
  } catch {
    currentModalImages = [product.image || "https://via.placeholder.com/400"];
  }
  currentImageIndex = 0;

  // Fill modal fields
  document.getElementById("modalName").textContent = product.name;
  document.getElementById("modalDescription").textContent =
    product.description || "No description available.";
  document.getElementById("modalStock").textContent = product.stock ?? "N/A";
  const currencySymbol = window.CURRENCY_SYMBOL || '₱';
  // Price is already converted in product data from API
  document.getElementById("modalPrice").textContent = currencySymbol + parseFloat(
    product.price || 0
  ).toFixed(2);

  const qtyInput = document.getElementById("modalQuantity");
  qtyInput.value = 1;
  qtyInput.max = product.stock;
  document.getElementById("modalAddToCart").disabled = product.stock === 0;

  // Load images
  loadModalImages(currentModalImages);
  document.getElementById("modalImage").src =
    currentModalImages[currentImageIndex];

  // Load reviews for this product
  loadReviews(id);

  // Show modal
  document.getElementById("productModal").classList.remove("hidden");
}

/* ========== LOAD THUMBNAILS ========== */
function loadModalImages(images) {
  const thumbsContainer = document.getElementById("modalThumbnails");
  const mainImage = document.getElementById("modalImage");
  thumbsContainer.innerHTML = "";

  images.forEach((img, index) => {
    const thumb = document.createElement("img");
    thumb.src = img;
    thumb.alt = `Thumbnail ${index + 1}`;
    thumb.className =
      "w-20 h-20 object-cover rounded-lg cursor-pointer border-2 " +
      (index === 0 ? "border-orange-500" : "border-transparent hover:border-orange-500");

    thumb.addEventListener("click", () => {
      currentImageIndex = index;
      mainImage.src = img;

      // highlight selected thumb
      Array.from(thumbsContainer.children).forEach((el, i) => {
        el.classList.toggle("border-orange-500", i === index);
        el.classList.toggle("border-transparent", i !== index);
      });
    });

    thumbsContainer.appendChild(thumb);
  });
}

/* ========== CLOSE MODAL ========== */
document.getElementById("closeModal").addEventListener("click", () => {
  document.getElementById("productModal").classList.add("hidden");
});
document
  .getElementById("productModal")
  .addEventListener("click", (e) => {
    if (e.target === document.getElementById("productModal")) {
      document.getElementById("productModal").classList.add("hidden");
    }
  });

/* ========== QUANTITY BUTTONS ========== */
document.getElementById("increaseQty").addEventListener("click", () => {
  const qtyInput = document.getElementById("modalQuantity");
  qtyInput.value = Math.min(
    parseInt(qtyInput.value) + 1,
    parseInt(qtyInput.max)
  );
});
document.getElementById("decreaseQty").addEventListener("click", () => {
  const qtyInput = document.getElementById("modalQuantity");
  qtyInput.value = Math.max(1, parseInt(qtyInput.value) - 1);
});

/* ========== ADD TO CART ========== */
async function addToCart(productId, quantity) {
  try {
    const res = await fetch("cart_action.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `add_id=${productId}&quantity=${quantity}`,
    });
    const data = await res.json();
    if (data.status === "success") {
      alert("Added to cart!");
      const cartCountElem = document.querySelector("#cart-count");
      if (cartCountElem && data.cart_count != null) {
        cartCountElem.textContent = data.cart_count;
      }
    } else {
      alert("Error: " + data.message);
    }
  } catch (err) {
    console.error(err);
    alert("Failed to add to cart.");
  }
}

document.getElementById("modalAddToCart").addEventListener("click", () => {
  const qty = parseInt(document.getElementById("modalQuantity").value) || 1;
  addToCart(currentProductId, qty);
});

/* ========== LOAD REVIEWS ========== */
async function loadReviews(productId) {
  const list = document.getElementById("reviewsList");
  if (!list) return;
  
  list.innerHTML = '<div class="text-sm text-gray-500">Loading reviews...</div>';
  
  try {
    const r = await fetch("get_reviews.php?product_id=" + encodeURIComponent(productId));
    const d = await r.json();
    
    const avgEl = document.getElementById("reviewsAverage");
    const cntEl = document.getElementById("reviewsCount");
    const count = d.reviews ? d.reviews.length : 0;
    const avg = count ? (d.reviews.reduce((s, x) => s + parseInt(x.rating || 0), 0) / count).toFixed(1) : "0.0";
    
    if (avgEl) avgEl.textContent = avg;
    if (cntEl) cntEl.textContent = count;
    
    if (!count) {
      list.innerHTML = '<div class="text-sm text-gray-500">No reviews yet. Be the first to review.</div>';
      return;
    }
    
    list.innerHTML = d.reviews.map((rv) => {
      const stars = "★".repeat(parseInt(rv.rating || 0)) + "☆".repeat(5 - parseInt(rv.rating || 0));
      const date = rv.created_at ? new Date(rv.created_at).toLocaleDateString() : "";
      return `
        <div class="border-b pb-3">
          <div class="flex items-center gap-2 mb-1">
            <span class="text-yellow-500 text-sm">${stars}</span>
            <span class="text-xs text-gray-500">${date}</span>
          </div>
          ${rv.review_text ? `<p class="text-sm text-gray-700">${escapeHtml(rv.review_text)}</p>` : ""}
          ${rv.image_path ? `<img src="${escapeHtml(rv.image_path)}" class="w-20 h-20 object-cover rounded mt-2" />` : ""}
        </div>
      `;
    }).join("");
  } catch (err) {
    console.error("Error loading reviews:", err);
    list.innerHTML = '<div class="text-sm text-red-500">Failed to load reviews.</div>';
  }
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}
