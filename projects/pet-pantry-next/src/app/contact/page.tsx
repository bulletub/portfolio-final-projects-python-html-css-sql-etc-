export default function ContactPage() {
  return (
    <main className="mx-auto max-w-3xl px-4 py-16 text-center">
      <h1 className="font-display text-3xl text-neutral-900">
        Contact <span className="text-brand-orange">Us</span>
      </h1>
      <p className="mx-auto mt-3 max-w-md text-sm text-neutral-500">
        Questions about an order, a product, or anything else? We&apos;d love to hear from you.
      </p>

      <div className="mx-auto mt-10 grid max-w-md grid-cols-1 gap-4">
        <div className="rounded-xl border border-neutral-100 bg-white p-6 shadow-sm">
          <p className="text-xs font-semibold tracking-wide text-brand-orange uppercase">Email</p>
          <a href="mailto:petpantry@gmail.com" className="mt-1 block text-neutral-800 hover:text-brand-orange">
            petpantry@gmail.com
          </a>
        </div>
        <div className="rounded-xl border border-neutral-100 bg-white p-6 shadow-sm">
          <p className="text-xs font-semibold tracking-wide text-brand-orange uppercase">Phone</p>
          <p className="mt-1 text-neutral-800">+63 929 683 8372</p>
        </div>
        <div className="rounded-xl border border-neutral-100 bg-white p-6 shadow-sm">
          <p className="text-xs font-semibold tracking-wide text-brand-orange uppercase">Location</p>
          <p className="mt-1 text-neutral-800">Quezon City, Philippines</p>
        </div>
      </div>
    </main>
  );
}
