import { getProducts, getCategories } from "@/lib/data/products";
import ProductGrid from "@/components/product-grid";

type ShopSearchParams = Promise<{ category?: string; search?: string }>;

export default async function ShopPage({
  searchParams,
}: {
  searchParams: ShopSearchParams;
}) {
  const { category, search } = await searchParams;
  const [products, categories] = await Promise.all([
    getProducts({ category, search }),
    getCategories(),
  ]);

  return (
    <main>
      <h1>Shop</h1>
      <form className="filters" method="get">
        <input type="search" name="search" placeholder="Search products…" defaultValue={search} />
        <select name="category" defaultValue={category ?? ""}>
          <option value="">All categories</option>
          {categories.map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
        <button type="submit">Filter</button>
      </form>
      <ProductGrid products={products} />
    </main>
  );
}
