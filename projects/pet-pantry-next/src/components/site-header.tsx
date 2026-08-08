import Link from "next/link";
import Image from "next/image";
import { getSessionUser, getSessionProfile } from "@/lib/data/session";
import { getNotifications } from "@/lib/data/notifications";
import { getCartCount } from "@/lib/data/cart";
import { getWishlistCount } from "@/lib/data/wishlist";
import NotificationBell from "./notification-bell";
import AccountMenu from "./account-menu";
import MobileNav from "./mobile-nav";

const navLinks = [
  { href: "/", label: "Home" },
  { href: "/shop", label: "Shop" },
  { href: "/promotions", label: "Promotions", badge: "NEW" },
  { href: "/orders", label: "Orders" },
  { href: "/about", label: "About Us" },
  { href: "/contact", label: "Contact" },
];

export default async function SiteHeader() {
  const user = await getSessionUser();
  const profile = user ? await getSessionProfile() : null;
  const isAdmin = profile?.account_type === "admin";

  const [notifications, cartCount, wishlistCount] = user
    ? await Promise.all([getNotifications(), getCartCount(), getWishlistCount()])
    : [[], 0, 0];

  return (
    <header className="fixed top-0 left-0 z-50 w-full bg-white shadow-sm">
      <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <Link href="/" className="flex items-center gap-2">
          <Image
            src="/logo.png"
            alt="PetPantry+"
            width={40}
            height={40}
            className="h-10 w-10 rounded-full border-2 border-brand-orange object-cover"
          />
          <span className="font-display text-lg text-brand-orange">PetPantry+</span>
        </Link>

        <nav className="hidden items-center gap-8 md:flex">
          {navLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="relative text-sm font-semibold text-neutral-700 hover:text-brand-orange"
            >
              {link.label}
              {link.badge && (
                <span className="absolute -top-2 -right-4 rounded-full bg-red-500 px-1 text-[8px] font-bold text-white">
                  {link.badge}
                </span>
              )}
            </Link>
          ))}
        </nav>

        <div className="hidden items-center gap-3 md:flex">
          {user && <NotificationBell initial={notifications} filter={isAdmin ? "audience=eq.admin" : `user_id=eq.${user.id}`} isAdmin={isAdmin} />}
          {user && (
            <Link
              href="/wishlist"
              aria-label="Wishlist"
              className="relative flex h-9 w-9 items-center justify-center rounded-full text-neutral-700 hover:bg-neutral-100 hover:text-brand-orange"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6 shrink-0">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                />
              </svg>
              {wishlistCount > 0 && (
                <span className="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-brand-orange text-[10px] font-bold text-white">
                  {wishlistCount}
                </span>
              )}
            </Link>
          )}
          {user && (
            <Link
              href="/cart"
              aria-label="Cart"
              className="relative flex h-9 w-9 items-center justify-center rounded-full text-neutral-700 hover:bg-neutral-100 hover:text-brand-orange"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.5} className="h-6 w-6 shrink-0">
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  d="M2.25 3h1.386c.51 0 .955.343 1.087.836l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.98-4.716 2.545-7.235.075-.334-.174-.65-.517-.65H5.106M7.5 14.25L5.106 5.272M6 18.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 18.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"
                />
              </svg>
              {cartCount > 0 && (
                <span className="absolute -top-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-brand-orange text-[10px] font-bold text-white">
                  {cartCount}
                </span>
              )}
            </Link>
          )}
          <AccountMenu loggedIn={!!user} isAdmin={isAdmin} />
        </div>

        <MobileNav loggedIn={!!user} cartCount={cartCount} wishlistCount={wishlistCount} />
      </div>
    </header>
  );
}
