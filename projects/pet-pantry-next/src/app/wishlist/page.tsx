import Link from "next/link";
import { getWishlistItems } from "@/lib/data/wishlist";
import WishlistRemoveButton from "./wishlist-remove-button";

export default async function WishlistPage() {
  const items = await getWishlistItems();

  if (items.length === 0) {
    return (
      <main>
        <h1>Your wishlist</h1>
        <p>
          Nothing saved yet. <Link href="/shop">Browse products</Link>.
        </p>
      </main>
    );
  }

  return (
    <main>
      <h1>Your wishlist</h1>
      <div className="product-grid">
        {items.map((item) =>
          item.product ? (
            <div key={item.id} className="product-card">
              <Link href={`/products/${item.product.id}`}>
                <img
                  src={item.product.image_path ?? "/products/placeholder.svg"}
                  alt={item.product.name}
                />
                <div className="product-info">
                  <strong>{item.product.name}</strong>
                  <span className="price">₱{item.product.price.toFixed(2)}</span>
                </div>
              </Link>
              <div className="product-info">
                <WishlistRemoveButton wishlistId={item.id} />
              </div>
            </div>
          ) : null
        )}
      </div>
    </main>
  );
}
