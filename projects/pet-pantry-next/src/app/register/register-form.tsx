"use client";

import { useActionState } from "react";
import { signup, signInWithGoogle } from "@/lib/actions/auth";

export default function RegisterForm() {
  const [state, action, pending] = useActionState(signup, undefined);

  return (
    <div>
      <form action={action} className="flex flex-col gap-4">
        <div>
          <label htmlFor="name" className="mb-1 block text-sm font-medium text-neutral-700">
            Name
          </label>
          <input
            id="name"
            name="name"
            required
            className="w-full rounded-lg border border-neutral-300 px-4 py-3 text-sm focus:border-brand-orange focus:ring-2 focus:ring-orange-200 focus:outline-none"
          />
        </div>
        <div>
          <label htmlFor="email" className="mb-1 block text-sm font-medium text-neutral-700">
            Email
          </label>
          <input
            id="email"
            name="email"
            type="email"
            required
            className="w-full rounded-lg border border-neutral-300 px-4 py-3 text-sm focus:border-brand-orange focus:ring-2 focus:ring-orange-200 focus:outline-none"
          />
        </div>
        <div>
          <label htmlFor="password" className="mb-1 block text-sm font-medium text-neutral-700">
            Password
          </label>
          <input
            id="password"
            name="password"
            type="password"
            minLength={8}
            required
            className="w-full rounded-lg border border-neutral-300 px-4 py-3 text-sm focus:border-brand-orange focus:ring-2 focus:ring-orange-200 focus:outline-none"
          />
        </div>
        {state?.error && (
          <p className="rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700">
            {state.error}
          </p>
        )}
        <button
          type="submit"
          disabled={pending}
          className="w-full rounded-lg bg-brand-orange py-3 font-medium text-white hover:bg-brand-orange-dark disabled:opacity-60"
        >
          {pending ? "Creating account…" : "Sign up"}
        </button>
      </form>
      <form action={signInWithGoogle.bind(null, "/")} className="mt-3">
        <button
          type="submit"
          className="w-full rounded-lg border border-neutral-300 bg-white py-3 text-sm font-medium text-neutral-700 hover:bg-neutral-50"
        >
          Continue with Google
        </button>
      </form>
    </div>
  );
}
