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
    <main>
      <img
        src={product.image_path ?? "/products/placeholder.svg"}
        alt={product.name}
        style={{ maxWidth: 400, width: "100%", borderRadius: 8 }}
      />
      <h1>{product.name}</h1>
      <p className="price">₱{product.price.toFixed(2)}</p>
      <p>{product.description}</p>
      <div style={{ display: "flex", gap: "0.5rem", alignItems: "flex-start" }}>
        <AddToCartButton productId={product.id} stock={product.stock} />
        <WishlistToggleButton productId={product.id} initialInWishlist={inWishlist} />
      </div>

      <h2>Reviews</h2>
      <ReviewList reviews={reviews} />
      {eligibility.canReview && (
        <>
          <h3>Write a review</h3>
          <ReviewForm productId={product.id} />
        </>
      )}
      {eligibility.alreadyReviewed && <p>You&apos;ve already reviewed this product.</p>}
    </main>
  );
}
