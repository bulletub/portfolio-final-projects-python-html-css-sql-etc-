const searchInput = document.getElementById('search');
const subcategorySelect = document.getElementById('subcategorySelect');
const sortSelect = document.getElementById('sortSelect');
const productList = document.getElementById('productList');
const resultCount = document.getElementById('resultCount');
const catScroll = document.getElementById('catScroll');
const categorySelect = document.getElementById('categorySelect'); // ✅ New category dropdown

let allProducts = [];
let visibleProducts = [];
let categories = [];
let subcategories = [];
let currentProductId = null;
let currentModalImages = [];
let currentImageIndex = 0;
let newProductId = localStorage.getItem('newProductId');
if(newProductId !== null) newProductId = parseInt(newProductId);

// Fetch products from PHP
async function fetchProducts(){
  try {
    const res = await fetch('get_products.php');
    if(!res.ok) throw new Error('Fetch failed');
    const data = await res.json();
    
    // Handle new response format {products, currency}
    if (data.products) {
      allProducts = data.products;
      // Set currency from API response
      if (data.currency) {
        window.CURRENCY_SYMBOL = data.currency.symbol || '₱';
        window.CURRENCY_CODE = data.currency.code || 'PHP';
      }
    } else {
      // Fallback for old format (array directly)
      allProducts = data;
    }
  } catch(err) {
    console.warn('Fetch failed, using demo data', err);
    allProducts = demoProducts();
  } finally {
    init();
  }
}

// Initialize UI
function init(){
  categories = Array.from(new Set(allProducts.map(p => p.category || 'Uncategorized'))).sort();
  subcategories = Array.from(new Set(allProducts.map(p => p.subcategory || 'Uncategorized'))).sort();

  populateCategoryUI();
  populateSubcategoryUI();

  searchInput.disabled = false;
  subcategorySelect.disabled = false;
  sortSelect.disabled = false;
  categorySelect.disabled = false;

  searchInput.addEventListener('input', applyFilters);
  subcategorySelect.addEventListener('change', applyFilters);
  sortSelect.addEventListener('change', applyFilters);
  categorySelect.addEventListener('change', ()=>{
    updateSubcategories();
    applyFilters();
  });

  applyFilters();
}

// Populate category dropdown
function populateCategoryUI(){
  categorySelect.innerHTML = '<option value="">All Categories</option>' +
    categories.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');
}

// Populate subcategory dropdown
function populateSubcategoryUI(filteredCategory = ''){
  let subs;
  if(filteredCategory){
    subs = Array.from(new Set(allProducts.filter(p => p.category === filteredCategory).map(p => p.subcategory || 'Uncategorized')));
  } else {
    subs = Array.from(new Set(allProducts.map(p => p.subcategory || 'Uncategorized')));
  }
  subs.sort();

  subcategorySelect.innerHTML = '<option value="">All Subcategories</option>' +
    subs.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');

  catScroll.innerHTML = subs.map(c => {
    const icon = encodeURI('https://via.placeholder.com/58?text=' + c.slice(0,2));
    return `<div class="cat" data-sub="${escapeHtml(c)}" onclick="onCatClick('${escapeJs(c)}')">
              <div class="circle"><img src="${icon}" alt="${escapeHtml(c)}" /></div>
              <span>${escapeHtml(c)}</span>
            </div>`;
  }).join('');
}

function updateSubcategories(){
  const selectedCat = categorySelect.value;
  populateSubcategoryUI(selectedCat);
}

function onCatClick(sub){
  subcategorySelect.value = sub;
  applyFilters();
}
window.onCatClick = onCatClick;

// Filters and sorting
function applyFilters(){
  const q = (searchInput.value||'').trim().toLowerCase();
  const selectedCat = categorySelect.value;
  const selectedSub = subcategorySelect.value;
  const sortVal = sortSelect.value;

  let items = allProducts.slice();

  if(q){
    items = items.filter(p => (p.name||'').toLowerCase().includes(q));
  }

  if(selectedCat){
    items = items.filter(p => (p.category||'') === selectedCat);
  }

  if(selectedSub){
    items = items.filter(p => (p.subcategory||'') === selectedSub);
  }

  if(sortVal){
    items.sort((a,b)=>{
      if(sortVal==='price-asc') return parseFloat(a.price||0)-parseFloat(b.price||0);
      if(sortVal==='price-desc') return parseFloat(b.price||0)-parseFloat(a.price||0);
      if(sortVal==='name-asc') return (a.name||'').localeCompare(b.name||'');
      if(sortVal==='name-desc') return (b.name||'').localeCompare(a.name||'');
      return 0;
    });
  }

  visibleProducts = items;
  renderProducts();
}

function renderProducts(){
  productList.innerHTML = '';

  if(visibleProducts.length===0){
    resultCount.textContent='No products found';
    return;
  }

  const grouped = {};

  // Add all products except new product, limit 5 per subcategory
  visibleProducts.forEach(p => {
    if(p.id === newProductId) return; // skip new product for now
    const k = p.subcategory || 'Uncategorized';
    grouped[k] = grouped[k] || [];
    grouped[k].push(p);
  });

  // Add new product at the end of its subcategory group
  if(newProductId !== null){
    const newProd = visibleProducts.find(p => p.id === newProductId);
    if(newProd){
      const k = newProd.subcategory || 'Uncategorized';
      grouped[k] = grouped[k] || [];
      if(!grouped[k].some(p => p.id === newProductId)){
        grouped[k].push(newProd);
      }
    }
  }
  
  // After rendering products the first time with new product shown:
  if(newProductId !== null){
    localStorage.removeItem('newProductId');
    newProductId = null;
  }

  const totalShown = Object.values(grouped).reduce((s,arr)=>s+arr.length,0);
  resultCount.textContent=`Showing ${totalShown} products (${Object.keys(grouped).length} categories)`;

  for(const sub of Object.keys(grouped)){
    const groupDiv = document.createElement('div');
    groupDiv.className='subcategory-group';
    groupDiv.innerHTML=`<div class="subcategory-title">${escapeHtml(sub)}</div>`;
    groupDiv.appendChild(createGridFor(grouped[sub]));
    productList.appendChild(groupDiv);
  }
}

// Create grid cards
function createGridFor(items){
  const grid = document.createElement('div');
  grid.className = 'grid';

  items.forEach(p => {
    const card = document.createElement('div');
    card.className = 'card';

    const img = `<div class="imgwrap"><img src="${escapeHtml(p.image || 'https://via.placeholder.com/180')}" alt="${escapeHtml(p.name)}"></div>`;

    // Disable Add button if stock is 0
    const addBtn = p.stock > 0 ? 
      `<button class="btn cart" onclick="addToCart(${escapeJs(p.id)},1)">Add</button>` : 
      `<button class="btn cart" disabled>Add</button>`;

    // ⭐ New Wishlist button
    const wishlistBtn = `<button class="btn wishlist" onclick="addToWishlist(${escapeJs(p.id)})">♡ Wishlist</button>`;
    const currencySymbol = window.CURRENCY_SYMBOL || '₱';

    card.innerHTML = `
      ${img}
      <h4>${escapeHtml(p.name)}</h4>
      <div><span class="price">${currencySymbol}${parseFloat(p.price || 0).toFixed(2)}</span></div>
      <div class="actions">
        <button class="btn view" onclick="viewProduct(${escapeJs(p.id)})">View</button>
        ${addBtn}
        ${wishlistBtn} <!-- ⭐ Added here -->
      </div>
    `;

    grid.appendChild(card);
  });

  return grid;
}


// View product modal
function viewProduct(id){
  const product = allProducts.find(p => p.id == id);
  if(!product) return;

  currentProductId = id;
  try {
    currentModalImages = product.images && product.images.startsWith('[') ? JSON.parse(product.images) : [product.images || product.image || 'https://via.placeholder.com/300'];
  } catch(e) {
    currentModalImages = [product.image || 'https://via.placeholder.com/300'];
  }
  currentImageIndex = 0;

  document.getElementById('modalName').textContent = product.name;
  document.getElementById('modalDescription').textContent = product.description || 'No description available.';
  document.getElementById('modalStock').textContent = product.stock ?? 'N/A';
  const currencySymbol = window.CURRENCY_SYMBOL || '₱';
  document.getElementById('modalPrice').textContent = currencySymbol + parseFloat(product.price||0).toFixed(2);
  const qtyInput = document.getElementById('modalQuantity');
  qtyInput.value = 1;
  qtyInput.max = product.stock;

  const modalAddBtn = document.getElementById('modalAddToCart');
  modalAddBtn.disabled = (product.stock === 0);

  loadModalImages(currentModalImages);
  document.getElementById('modalImage').src = currentModalImages[currentImageIndex];
  document.getElementById('productModal').classList.remove('hidden');

  // Load reviews for this product
  loadReviews(id);
}

// Load modal thumbnails
function loadModalImages(images){
  const mainImage = document.getElementById('modalImage');
  const thumbsContainer = document.getElementById('modalThumbnails');
  thumbsContainer.innerHTML = '';
  images.forEach((img, index)=>{
    const thumb = document.createElement('img');
    thumb.src = img;
    thumb.alt = 'Thumbnail ' + (index+1);
    thumb.className = 'w-20 h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-orange-500';
    thumb.addEventListener('click', ()=>{
      currentImageIndex = index;
      mainImage.src = img;
    });
    thumbsContainer.appendChild(thumb);
  });
}

// Close modal
document.getElementById('closeModal').addEventListener('click', ()=>{ 
  document.getElementById('productModal').classList.add('hidden'); 
});
document.getElementById('productModal').addEventListener('click', (e)=>{
  if(e.target === document.getElementById('productModal')){
    document.getElementById('productModal').classList.add('hidden');
  }
});

// Reviews
async function loadReviews(productId){
  const list = document.getElementById('reviewsList');
  if(!list) return;
  list.innerHTML = '<div class="text-sm text-gray-500">Loading reviews...</div>';
  try {
    const r = await fetch('get_reviews.php?product_id='+encodeURIComponent(productId));
    const d = await r.json();
    if(!d.success){ list.innerHTML = '<div class="text-red-500 text-sm">'+(d.error||'Failed to load')+'</div>'; return; }
    const avgEl = document.getElementById('reviewsAverage');
    const cntEl = document.getElementById('reviewsCount');
    const count = d.reviews.length;
    const avg = count ? (d.reviews.reduce((s,x)=>s+parseInt(x.rating||0),0)/count).toFixed(1) : '0.0';
    if (avgEl) avgEl.textContent = avg;
    if (cntEl) cntEl.textContent = count;

    if(!count){ list.innerHTML = '<div class="text-sm text-gray-500">No reviews yet. Be the first to review.</div>'; return; }
    list.innerHTML = d.reviews.map(rv=>{
      const stars = '★★★★★'.slice(0,rv.rating) + '☆☆☆☆☆'.slice(0,5-rv.rating);
      const img = rv.image_path ? `<img src="${escapeHtml(rv.image_path)}" class="w-16 h-16 object-cover rounded border" />` : '';
      const badge = rv.is_own ? `<span class=\"ml-2 text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700\">Your review${rv.status && rv.status!=='approved' ? ' • '+rv.status : ''}</span>` : '';
      return `<div class="p-2 border rounded flex items-start gap-3">
                ${img}
                <div class="flex-1">
                  <div class="text-yellow-600 text-sm">${stars}</div>
                  <div class="text-sm">${escapeHtml(rv.review_text||'')}</div>
                  <div class="text-xs text-gray-500 mt-0.5">${escapeHtml(rv.email||'Guest')} • ${new Date(rv.created_at).toLocaleString()} ${badge}</div>
                </div>
              </div>`;
    }).join('');
  } catch(err){ list.innerHTML = '<div class="text-red-500 text-sm">Failed to load.</div>'; }
}

const reviewForm = document.getElementById('reviewForm');
if (reviewForm){
  reviewForm.addEventListener('submit', async (e)=>{
    e.preventDefault();
    if(!currentProductId) return;
    const fd = new FormData();
    fd.append('product_id', currentProductId);
    fd.append('rating', document.getElementById('revRating').value);
    fd.append('review_text', document.getElementById('revText').value);
    if(document.getElementById('revImage').files[0]){
      fd.append('image', document.getElementById('revImage').files[0]);
    }
    const r = await fetch('post_review.php', { method:'POST', body: fd });
    const d = await r.json();
    if(d.success){
      document.getElementById('revText').value='';
      document.getElementById('revImage').value='';
      loadReviews(currentProductId);
      alert('Review submitted');
    } else {
      alert(d.error || 'Failed to submit');
    }
  });
}

// Quantity buttons
document.getElementById('increaseQty').addEventListener('click', ()=>{
  const qtyInput = document.getElementById('modalQuantity');
  qtyInput.value = Math.min(parseInt(qtyInput.value)+1, parseInt(qtyInput.max));
});
document.getElementById('decreaseQty').addEventListener('click', ()=>{
  const qtyInput = document.getElementById('modalQuantity');
  qtyInput.value = Math.max(1, parseInt(qtyInput.value)-1);
});

// Add to cart
async function addToCart(productId, quantity){
  try {
    const res = await fetch('cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `add_id=${productId}&quantity=${quantity}`
    });
    const data = await res.json();
    if(data.status === 'success') {
      alert('Added to cart!');

      // Immediately update header cart count
      const cartCountElem = document.querySelector('#cart-count');
      if(cartCountElem && data.cart_count != null){
        cartCountElem.textContent = data.cart_count;
      }
    } else {
      alert('Error: ' + data.message);
    }
  } catch(err) {
    console.error(err);
    alert('Failed to add to cart.');
  }
}

document.getElementById('modalAddToCart').addEventListener('click', ()=>{
  const qty = parseInt(document.getElementById('modalQuantity').value) || 1;
  addToCart(currentProductId, qty);
});

// Helpers
function escapeHtml(s){ 
  if(s==null) return ''; 
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); 
}
function escapeJs(s){ 
  if(s==null) return "''"; 
  return "'"+String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/\n/g,'\\n')+"'"; 
}

// Demo products fallback
function demoProducts(){
  const cats=['Cat Supplies','Dog Supplies'];
  const subs=['Cat Food','Cat Toys','Dog Beds','Dog Clothing','Accessories','Dental & Ear Care'];
  const out=[]; let id=1;
  for(const c of cats){
    for(const s of subs){
      for(let i=1;i<=3;i++){
        out.push({
          id:id++,
          name:`${s} Product ${i}`,
          price:(Math.random()*300+20).toFixed(2),
          image:`https://picsum.photos/seed/${encodeURIComponent(s+i)}/300/240`,
          category:c,
          subcategory:s,
          description:'Demo description',
          stock:Math.floor(Math.random()*20)+1,
          images:null
        });
      }
    }
  }
  return out;
}

// Start
fetchProducts();

// ========================
// Wishlist Integration ⭐
// ========================

// Add to wishlist
async function addToWishlist(productId){
  try {
    const res = await fetch('wishlist_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `add_id=${productId}`
    });
    const data = await res.json();

    if(data.status === 'success') {
      alert('Added to wishlist!');

      // Immediately update header wishlist count
      const wishlistCountElem = document.querySelector('#wishlist-count');
      if(wishlistCountElem && data.wishlist_count != null){
        wishlistCountElem.textContent = data.wishlist_count;
      }
    } else {
      alert('Error: ' + data.message);
    }
  } catch(err) {
    console.error(err);
    alert('Failed to add to wishlist.');
  }
}

// Remove from wishlist (optional use in wishlist.php page)
async function removeFromWishlist(productId){
  try {
    const res = await fetch('wishlist_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `remove_id=${productId}`
    });
    const data = await res.json();

    if(data.status === 'success') {
      alert('Removed from wishlist!');

      const wishlistCountElem = document.querySelector('#wishlist-count');
      if(wishlistCountElem && data.wishlist_count != null){
        wishlistCountElem.textContent = data.wishlist_count;
      }
    } else {
      alert('Error: ' + data.message);
    }
  } catch(err) {
    console.error(err);
    alert('Failed to remove from wishlist.');
  }
}

