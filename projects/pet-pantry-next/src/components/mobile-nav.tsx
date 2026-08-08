"use client";

import { useState } from "react";
import Link from "next/link";

const links = [
  { href: "/", label: "Home" },
  { href: "/shop", label: "Shop" },
  { href: "/promotions", label: "Promotions" },
  { href: "/orders", label: "Orders" },
  { href: "/about", label: "About Us" },
  { href: "/contact", label: "Contact" },
];

export default function MobileNav({
  loggedIn,
  cartCount,
  wishlistCount,
}: {
  loggedIn: boolean;
  cartCount: number;
  wishlistCount: number;
}) {
  const [open, setOpen] = useState(false);

  return (
    <div className="md:hidden">
      <button
        type="button"
        aria-label="Menu"
        onClick={() => setOpen(true)}
        className="flex h-9 w-9 items-center justify-center bg-transparent text-neutral-700"
      >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8} className="h-6 w-6 shrink-0">
          <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
        </svg>
      </button>

      {open && (
        <button
          type="button"
          aria-label="Close menu"
          onClick={() => setOpen(false)}
          className="fixed inset-0 z-40 bg-black/40"
        />
      )}

      <div
        className={`fixed top-0 left-0 z-50 h-full w-64 transform bg-white shadow-xl transition-transform duration-300 ${
          open ? "translate-x-0" : "-translate-x-full"
        }`}
      >
        <div className="flex items-center justify-between border-b border-neutral-100 px-4 py-4">
          <span className="font-display text-lg text-brand-orange">PetPantry+</span>
          <button
            type="button"
            aria-label="Close menu"
            onClick={() => setOpen(false)}
            className="bg-transparent text-neutral-500"
          >
            ✕
          </button>
        </div>
        <nav className="flex flex-col gap-1 px-4 py-4">
          {links.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              onClick={() => setOpen(false)}
              className="rounded-lg px-2 py-2 font-semibold text-neutral-700 hover:bg-neutral-50 hover:text-brand-orange"
            >
              {link.label}
            </Link>
          ))}
          {loggedIn && (
            <>
              <Link
                href="/wishlist"
                onClick={() => setOpen(false)}
                className="mt-2 flex items-center justify-between rounded-lg border border-neutral-200 px-3 py-2 font-semibold text-neutral-700 shadow-sm"
              >
                Wishlist
                {wishlistCount > 0 && (
                  <span className="rounded-full bg-brand-orange px-2 py-0.5 text-xs font-bold text-white">
                    {wishlistCount}
                  </span>
                )}
              </Link>
              <Link
                href="/cart"
                onClick={() => setOpen(false)}
                className="flex items-center justify-between rounded-lg border border-neutral-200 px-3 py-2 font-semibold text-neutral-700 shadow-sm"
              >
                Cart
                {cartCount > 0 && (
                  <span className="rounded-full bg-brand-orange px-2 py-0.5 text-xs font-bold text-white">
                    {cartCount}
                  </span>
                )}
              </Link>
            </>
          )}
          {!loggedIn && (
            <Link
              href="/login"
              onClick={() => setOpen(false)}
              className="mt-2 rounded-lg bg-brand-orange px-3 py-2 text-center font-semibold text-white"
            >
              Log in
            </Link>
          )}
        </nav>
      </div>
    </div>
  );
}
