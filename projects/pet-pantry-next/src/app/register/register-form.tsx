"use client";

import { useActionState } from "react";
import { signup, signInWithGoogle } from "@/lib/actions/auth";

export default function RegisterForm() {
  const [state, action, pending] = useActionState(signup, undefined);

  return (
    <div>
      <form action={action}>
        <div>
          <label htmlFor="name">Name</label>
          <input id="name" name="name" required />
        </div>
        <div>
          <label htmlFor="email">Email</label>
          <input id="email" name="email" type="email" required />
        </div>
        <div>
          <label htmlFor="password">Password</label>
          <input id="password" name="password" type="password" minLength={8} required />
        </div>
        {state?.error && <p role="alert">{state.error}</p>}
        <button type="submit" disabled={pending}>
          {pending ? "Creating account…" : "Sign up"}
        </button>
      </form>
      <form action={signInWithGoogle.bind(null, "/")}>
        <button type="submit">Continue with Google</button>
      </form>
    </div>
  );
}
