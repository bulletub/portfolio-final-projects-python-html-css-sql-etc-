"use client";

import { useActionState } from "react";
import { useSearchParams } from "next/navigation";
import { login, signInWithGoogle } from "@/lib/actions/auth";

export default function LoginForm() {
  const searchParams = useSearchParams();
  const next = searchParams.get("next") ?? "/";
  const oauthFailed = searchParams.get("error") === "oauth_failed";

  const [state, action, pending] = useActionState(login, undefined);

  return (
    <div>
      <form action={action} className="flex flex-col gap-4">
        <input type="hidden" name="next" value={next} />
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
            required
            className="w-full rounded-lg border border-neutral-300 px-4 py-3 text-sm focus:border-brand-orange focus:ring-2 focus:ring-orange-200 focus:outline-none"
          />
        </div>
        {state?.error && (
          <p className="rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700">
            {state.error}
          </p>
        )}
        {oauthFailed && (
          <p className="rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-sm text-red-700">
            Google sign-in failed. Please try again.
          </p>
        )}
        <button
          type="submit"
          disabled={pending}
          className="w-full rounded-lg bg-yellow-500 py-3 font-medium text-black hover:bg-yellow-600 disabled:opacity-60"
        >
          {pending ? "Logging in…" : "Log in"}
        </button>
      </form>
      <form action={signInWithGoogle.bind(null, next)} className="mt-3">
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
