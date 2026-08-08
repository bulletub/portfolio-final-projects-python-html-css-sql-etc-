import Image from "next/image";
import Link from "next/link";

export default function SiteFooter() {
  return (
    <footer className="bg-neutral-900 text-neutral-300">
      <div className="mx-auto grid max-w-7xl grid-cols-1 gap-12 px-8 py-16 md:grid-cols-5">
        <div>
          <div className="mb-3 flex items-center gap-2">
            <Image
              src="/logo.png"
              alt="PetPantry+"
              width={40}
              height={40}
              className="h-10 w-10 rounded-full border-2 border-brand-orange object-cover"
            />
            <span className="font-display text-lg text-brand-orange">PetPantry+</span>
          </div>
          <p className="max-w-xs text-sm text-neutral-400">
            Questions about an order or your account? Email us at{" "}
            <a href="mailto:petpantry@gmail.com" className="text-brand-orange underline">
              petpantry@gmail.com
            </a>
            .
          </p>
          <address className="mt-3 text-sm not-italic text-neutral-400">
            Quezon City
            <br />
            +63 929 683 8372
          </address>
        </div>

        <div>
          <h3 className="mb-3 text-sm font-bold tracking-wide text-orange-500 uppercase">Corporate</h3>
          <Link href="/about" className="text-sm text-neutral-400 hover:text-brand-orange">
            About Us
          </Link>
        </div>

        <div>
          <h3 className="mb-3 text-sm font-bold tracking-wide text-orange-500 uppercase">Customer Service</h3>
          <Link href="/contact" className="text-sm text-neutral-400 hover:text-brand-orange">
            Contact Us
          </Link>
        </div>

        <div>
          <h3 className="mb-3 text-sm font-bold tracking-wide text-orange-500 uppercase">Services</h3>
          <Link href="/shop" className="text-sm text-neutral-400 hover:text-brand-orange">
            Shop
          </Link>
        </div>

        <div>
          <h3 className="mb-3 text-sm font-bold tracking-wide text-orange-500 uppercase">Sign up for offers</h3>
          <p className="mb-3 text-sm text-neutral-400">Get the latest deals straight to your inbox.</p>
          <div className="flex max-w-xs">
            <input
              type="email"
              placeholder="Your email"
              className="w-full rounded-l-full border-0 px-4 py-2 text-sm text-neutral-900 focus:outline-none"
            />
            <button
              type="button"
              aria-label="Subscribe"
              className="rounded-r-full bg-brand-orange px-4 py-2 text-white hover:bg-brand-orange-dark"
            >
              ➤
            </button>
          </div>
        </div>
      </div>

      <div className="border-t border-neutral-800 px-8 py-4">
        <p className="text-center text-sm text-neutral-500">© 2025 PetPantry+. All rights reserved.</p>
      </div>
    </footer>
  );
}
