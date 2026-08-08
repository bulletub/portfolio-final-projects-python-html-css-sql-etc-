import Link from "next/link";
import { getProducts } from "@/lib/data/products";
import ProductGrid from "@/components/product-grid";

export default async function HomePage() {
  const products = (await getProducts()).slice(0, 8);

  return (
    <main>
      <h1>Everything your pet needs</h1>
      <p>
        Browse our full catalog on the <Link href="/shop">shop page</Link>.
      </p>
      <h2>Featured products</h2>
      <ProductGrid products={products} />
    </main>
  );
}
