"use client";

import { useState } from "react";
import Link from "next/link";
import { logout } from "@/lib/actions/auth";

export default function AccountMenu({
  loggedIn,
  isAdmin,
}: {
  loggedIn: boolean;
  isAdmin: boolean;
}) {
  const [open, setOpen] = useState(false);

  if (!loggedIn) {
    return (
      <Link href="/login" className="text-sm font-semibold text-neutral-700 hover:text-brand-orange">
        Log in
      </Link>
    );
  }

  return (
    <div className="relative">
      <button
        type="button"
        aria-label="Account"
        onClick={() => setOpen((o) => !o)}
        className="flex h-9 w-9 items-center justify-center rounded-full bg-transparent text-neutral-700 hover:bg-neutral-100 hover:text-brand-orange"
      >
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={1.5}
          width={24}
          height={24}
          className="h-6 w-6 shrink-0"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975M15 6.75a3 3 0 11-6 0 3 3 0 016 0z"
          />
          <circle cx="12" cy="12" r="9.75" strokeWidth={1.5} />
        </svg>
      </button>
      {open && (
        <div className="absolute right-0 top-full z-50 mt-2 w-44 rounded-lg border border-neutral-200 bg-white py-2 text-sm shadow-lg">
          {isAdmin && (
            <Link
              href="/admin"
              className="block px-4 py-2 text-neutral-700 hover:bg-neutral-50 hover:text-brand-orange"
              onClick={() => setOpen(false)}
            >
              Admin
            </Link>
          )}
          <Link
            href="/orders"
            className="block px-4 py-2 text-neutral-700 hover:bg-neutral-50 hover:text-brand-orange"
            onClick={() => setOpen(false)}
          >
            Orders
          </Link>
          <form action={logout}>
            <button
              type="submit"
              className="block w-full bg-transparent px-4 py-2 text-left font-normal text-neutral-700 hover:bg-neutral-50 hover:text-brand-orange"
            >
              Log out
            </button>
          </form>
        </div>
      )}
    </div>
  );
}
