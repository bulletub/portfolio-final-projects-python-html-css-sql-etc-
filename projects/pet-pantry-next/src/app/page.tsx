import Image from "next/image";
import { getBestSellers, getFeaturedProducts, getNewArrivals } from "@/lib/data/products";
import HeroCarousel from "@/components/hero-carousel";
import ProductRow from "@/components/product-row";

const BRANDS = ["brand1.png", "brand2.png", "brand3.png", "brand4.png", "brand5.png"];

export default async function HomePage() {
  const [bestSellers, featured, newArrivals] = await Promise.all([
    getBestSellers(),
    getFeaturedProducts(),
    getNewArrivals(),
  ]);

  return (
    <main>
      <HeroCarousel />

      <section className="mx-auto max-w-7xl px-4 py-16">
        <h2 className="mb-10 text-center text-2xl font-extrabold">
          <span className="text-brand-orange">Best</span> Sellers
        </h2>
        <ProductRow products={bestSellers} />
      </section>

      <section className="mx-auto max-w-7xl px-4 py-16">
        <h2 className="mb-10 text-center text-2xl font-extrabold">
          <span className="text-brand-orange">Featured</span> Items
        </h2>
        <ProductRow products={featured} />
      </section>

      <section className="mx-auto max-w-7xl px-4 py-16">
        <h2 className="mb-10 text-center text-2xl font-extrabold">
          <span className="text-brand-orange">New</span> Arrivals
        </h2>
        <ProductRow products={newArrivals} />
      </section>

      <section className="relative mt-8 flex min-h-[60vh] items-center">
        <Image src="/bg4.png" alt="" fill sizes="100vw" className="object-cover" />
        <div className="relative z-10 max-w-xl px-6 text-neutral-700 md:ml-24">
          <p className="text-sm font-semibold text-brand-orange">Taste Guarantee</p>
          <h2 className="mt-2 text-2xl font-bold md:text-3xl">
            Taste it, love it or we&apos;ll replace it… Guaranteed!
          </h2>
          <p className="mt-4 text-sm">
            Every bag of PetPantry+ is backed by our taste guarantee — if your dog or cat doesn&apos;t love it,
            we&apos;ll make it right.
          </p>
          <button
            type="button"
            className="mt-6 rounded-full bg-black px-6 py-2 text-sm font-semibold text-white hover:bg-neutral-800"
          >
            Find out more
          </button>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-14">
        <h2 className="mb-10 text-center text-2xl font-extrabold">
          <span className="text-brand-orange">Popular</span> Brands
        </h2>
        <div className="mx-auto grid max-w-xl grid-cols-5 gap-6 rounded-lg border border-neutral-100 bg-white p-6 shadow-sm">
          {BRANDS.map((brand) => (
            <img
              key={brand}
              src={`/brand/${brand}`}
              alt=""
              className="h-20 w-20 rounded-full object-contain transition-opacity hover:opacity-80"
            />
          ))}
        </div>
      </section>
    </main>
  );
}
