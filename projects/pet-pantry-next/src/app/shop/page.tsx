import { getProducts, getCategories, getSubcategories, type ProductSort } from "@/lib/data/products";
import ProductGrid from "@/components/product-grid";

type ShopSearchParams = Promise<{
  category?: string;
  subcategory?: string;
  search?: string;
  sort?: string;
}>;

export default async function ShopPage({ searchParams }: { searchParams: ShopSearchParams }) {
  const { category, subcategory, search, sort } = await searchParams;
  const [products, categories, subcategories] = await Promise.all([
    getProducts({ category, subcategory, search, sort: sort as ProductSort }),
    getCategories(),
    getSubcategories(),
  ]);

  const groups = new Map<string, typeof products>();
  for (const product of products) {
    const key = product.subcategory ?? "Other";
    groups.set(key, [...(groups.get(key) ?? []), product]);
  }

  return (
    <main className="mx-auto max-w-7xl px-4 py-10">
      <div className="mb-8 -mx-4 bg-neutral-900 px-4 py-16 text-center text-white">
        <h1 className="font-display text-3xl md:text-4xl">Shop</h1>
        <p className="mt-2 text-sm text-neutral-300">Everything your pet needs, in one place.</p>
      </div>

      <div className="flex flex-col gap-6 md:flex-row">
        <aside className="w-full flex-shrink-0 md:w-60">
          <form className="flex flex-col gap-3 rounded-xl border border-neutral-100 bg-white p-4 shadow-sm" method="get">
            <div>
              <label className="mb-1 block text-xs font-semibold text-neutral-500">Search</label>
              <input
                type="search"
                name="search"
                placeholder="Search products…"
                defaultValue={search}
                className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-neutral-500">Category</label>
              <select
                name="category"
                defaultValue={category ?? ""}
                className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
              >
                <option value="">All Categories</option>
                {categories.map((c) => (
                  <option key={c} value={c}>
                    {c}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-neutral-500">Subcategory</label>
              <select
                name="subcategory"
                defaultValue={subcategory ?? ""}
                className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
              >
                <option value="">All Subcategories</option>
                {subcategories.map((s) => (
                  <option key={s} value={s}>
                    {s}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="mb-1 block text-xs font-semibold text-neutral-500">Sort By</label>
              <select
                name="sort"
                defaultValue={sort ?? "default"}
                className="w-full rounded-lg border border-neutral-200 px-3 py-2 text-sm"
              >
                <option value="default">Default</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name_asc">Name: A-Z</option>
                <option value="name_desc">Name: Z-A</option>
              </select>
            </div>
            <button
              type="submit"
              className="rounded-full bg-brand-orange px-4 py-2 text-sm font-semibold text-white hover:bg-brand-orange-dark"
            >
              Apply
            </button>
          </form>
        </aside>

        <div className="flex-1">
          <p className="mb-4 text-sm text-neutral-500">
            Showing {products.length} product{products.length === 1 ? "" : "s"} ({groups.size} categor
            {groups.size === 1 ? "y" : "ies"})
          </p>
          {products.length === 0 && <p className="text-neutral-500">No products found.</p>}
          <div className="flex flex-col gap-10">
            {[...groups.entries()].map(([label, items]) => (
              <div key={label}>
                <h2 className="mb-4 text-lg font-bold text-neutral-800">{label}</h2>
                <ProductGrid products={items} />
              </div>
            ))}
          </div>
        </div>
      </div>
    </main>
  );
}
