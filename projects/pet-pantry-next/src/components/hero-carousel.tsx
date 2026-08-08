"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import Image from "next/image";

const SLIDES = ["/bg1.png", "/bg3.png", "/bg4.png"];

export default function HeroCarousel() {
  const [index, setIndex] = useState(0);

  useEffect(() => {
    const id = setInterval(() => setIndex((i) => (i + 1) % SLIDES.length), 7000);
    return () => clearInterval(id);
  }, []);

  return (
    <section className="relative h-[500px] overflow-hidden md:h-[720px]">
      {SLIDES.map((src, i) => (
        <div
          key={src}
          className="absolute inset-0 transition-opacity duration-1000 ease-in-out"
          style={{ opacity: i === index ? 1 : 0 }}
        >
          <Image src={src} alt="" fill sizes="100vw" priority={i === 0} className="object-cover" />
        </div>
      ))}
      <div className="absolute inset-0 bg-black/20" />

      <div className="relative z-10 flex h-full flex-col justify-center px-6 md:ml-32 md:px-0">
        <h1 className="font-display text-4xl leading-tight text-white uppercase md:text-5xl">
          High Quality
          <br />
          <span className="text-5xl md:text-6xl">Pet Food</span>
        </h1>
        <p className="mt-3 text-sm text-white/80">Your Pet Deserves the Best</p>
        <Link
          href="/shop"
          className="mt-6 inline-block w-fit rounded-full bg-black px-6 py-2 text-sm font-semibold text-white transition-colors hover:bg-neutral-800"
        >
          Shop Now
        </Link>
      </div>

      <div className="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 gap-2">
        {SLIDES.map((src, i) => (
          <button
            key={src}
            type="button"
            aria-label={`Slide ${i + 1}`}
            onClick={() => setIndex(i)}
            className={`h-3 w-3 rounded-full ${i === index ? "bg-brand-orange" : "bg-white/50"}`}
          />
        ))}
      </div>
    </section>
  );
}
