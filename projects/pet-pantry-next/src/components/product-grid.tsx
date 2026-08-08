import Link from "next/link";
import type { Tables } from "@/lib/supabase/types";

export default function ProductGrid({ products }: { products: Tables<"products">[] }) {
  if (products.length === 0) {
    return <p>No products found.</p>;
  }

  return (
    <div className="product-grid">
      {products.map((product) => (
        <Link key={product.id} href={`/products/${product.id}`} className="product-card">
          <img src={product.image_path ?? "/products/placeholder.svg"} alt={product.name} />
          <div className="product-info">
            <strong>{product.name}</strong>
            <span className="price">₱{product.price.toFixed(2)}</span>
            <span>{product.stock > 0 ? `${product.stock} in stock` : "Out of stock"}</span>
          </div>
        </Link>
      ))}
    </div>
  );
}
