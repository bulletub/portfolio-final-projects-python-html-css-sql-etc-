import { notFound } from "next/navigation";
import { getProductById } from "@/lib/data/products";
import { getApprovedReviews, getReviewEligibility } from "@/lib/data/reviews";
import { isInWishlist } from "@/lib/data/wishlist";
import AddToCartButton from "@/components/add-to-cart-button";
import WishlistToggleButton from "@/components/wishlist-toggle-button";
import ReviewList from "@/components/review-list";
import ReviewForm from "@/components/review-form";

export default async function ProductPage({
  params,
}: {
  params: Promise<{ id: string }>;
}) {
  const { id } = await params;
  const productId = Number(id);
  const product = await getProductById(productId);

  if (!product) {
    notFound();
  }

  const [reviews, eligibility, inWishlist] = await Promise.all([
    getApprovedReviews(productId),
    getReviewEligibility(productId),
    isInWishlist(productId),
  ]);

  return (
    <main className="mx-auto max-w-5xl px-4 py-10">
      <div className="flex flex-col gap-10 md:flex-row">
        <div className="md:w-1/2">
          <div className="flex h-96 items-center justify-center rounded-xl border border-neutral-100 bg-white">
            <img
              src={product.image_path ?? "/products/placeholder.svg"}
              alt={product.name}
              className="max-h-96 max-w-full object-contain"
            />
          </div>
        </div>

        <div className="flex flex-col md:w-1/2">
          <h1 className="text-2xl font-bold text-neutral-900">{product.name}</h1>
          <p className="mt-2 text-sm text-neutral-500">
            {product.category}
            {product.subcategory ? ` · ${product.subcategory}` : ""}
          </p>
          <p className="mt-3 text-sm text-neutral-500">
            {product.stock > 0 ? `${product.stock} in stock` : "Out of stock"}
          </p>
          <p className="mt-2 text-xl font-bold text-brand-orange">₱{product.price.toFixed(2)}</p>
          <p className="mt-4 text-sm text-neutral-600">{product.description}</p>

          <div className="mt-6 flex flex-wrap items-start gap-3">
            <AddToCartButton productId={product.id} stock={product.stock} />
            <WishlistToggleButton productId={product.id} initialInWishlist={inWishlist} />
          </div>
        </div>
      </div>

      <div className="mt-12 border-t border-neutral-100 pt-8">
        <h2 className="mb-4 text-xl font-bold text-neutral-900">Customer Reviews</h2>
        <ReviewList reviews={reviews} />
        {eligibility.canReview && (
          <div className="mt-6">
            <h3 className="mb-3 text-sm font-bold text-neutral-800">Write a review</h3>
            <ReviewForm productId={product.id} />
          </div>
        )}
        {eligibility.alreadyReviewed && (
          <p className="mt-4 text-sm text-neutral-500">You&apos;ve already reviewed this product.</p>
        )}
      </div>
    </main>
  );
}
