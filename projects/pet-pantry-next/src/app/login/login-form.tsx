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
      <form action={action}>
        <input type="hidden" name="next" value={next} />
        <div>
          <label htmlFor="email">Email</label>
          <input id="email" name="email" type="email" required />
        </div>
        <div>
          <label htmlFor="password">Password</label>
          <input id="password" name="password" type="password" required />
        </div>
        {state?.error && <p role="alert">{state.error}</p>}
        {oauthFailed && <p role="alert">Google sign-in failed. Please try again.</p>}
        <button type="submit" disabled={pending}>
          {pending ? "Logging in…" : "Log in"}
        </button>
      </form>
      <form action={signInWithGoogle.bind(null, next)}>
        <button type="submit">Continue with Google</button>
      </form>
    </div>
  );
}
