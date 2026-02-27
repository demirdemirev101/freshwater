import { useEffect, useRef, useState } from "react";
import gsap from "gsap";
import heroSlides from "./heroSlides";
import "../../styles/home-styles/hero.css";

const AUTO_ADVANCE_MS = 8200;
const STAGGER_STEP = 0.14;

const Hero = () => {
  const [index, setIndex] = useState(0);
  const rootRef = useRef(null);
  const tlRef = useRef(null);
  const timerRef = useRef(null);

  useEffect(() => {
    // Preload backgrounds to avoid visual "jumps" when switching slides.
    const images = heroSlides.map((slide) => {
      const img = new Image();
      img.src = slide.bg;
      return img;
    });

    return () => {
      images.forEach((img) => {
        img.src = "";
      });
    };
  }, []);

  useEffect(() => {
    const root = rootRef.current;
    if (!root) return;

    const q = gsap.utils.selector(root);
    const bg = q(".hero-bg");

    if (tlRef.current) tlRef.current.kill();
    if (timerRef.current) clearTimeout(timerRef.current);

    const slide = heroSlides[index];

    const tl = gsap.timeline({
      defaults: { ease: "power2.out" },
    });

    tlRef.current = tl;

    // Reset all animated parts to a clean base state.
    tl.set(q(".hero-el"), { autoAlpha: 0, y: 0, scale: 1 });
    tl.set(bg, { opacity: 0.92, scale: 1 });

    // Smooth background fade-in without "floating" movement.
    tl.fromTo(bg, { opacity: 0.92 }, { opacity: 1, duration: 1.05, ease: "sine.out" });

    // Slide 1: stagger reveal (one by one).
    if (slide.type === "compact") {
      tl.fromTo(
        [
          ...q(".el-compact"),
          ...q(".el-compact-text"),
          ...q(".bubble-1"),
          ...q(".bubble-2"),
          ...q(".hero-btn"),
        ],
        { autoAlpha: 0, y: 0, scale: 1 },
        {
          autoAlpha: 1,
          y: 0,
          scale: 1,
          duration: 0.62,
          ease: "sine.out",
          stagger: STAGGER_STEP,
        },
        "-=0.62"
      );
    }

    // Slide 2: text-first sequential reveal.
    if (slide.type === "text") {
      tl.fromTo(
        [
          ...q(".el-title-text"),
          ...q(".el-title-text-big"),
          ...q(".el-subtitle"),
          ...q(".hero-btn"),
        ],
        { autoAlpha: 0, y: 0 },
        {
          autoAlpha: 1,
          y: 0,
          duration: 0.62,
          ease: "sine.out",
          stagger: STAGGER_STEP + 0.02,
        },
        "-=0.62"
      );
    }

    // Slide 3: reveal -> merge -> bubbles -> CTA.
    if (slide.type === "uniq") {
      const logo = q(".el-logo");
      const title = q(".el-title-uniq");
      const uniq = q(".uniq-uniq");
      const box = q(".uniq-box");
      const sink = q(".uniq-sink");
      const withSink = q(".uniq-withSink");
      const bubbleUniq = q(".bubble-uniq");
      const bubbleBox = q(".bubble-uniqbox");
      const btn = q(".hero-btn");

      tl.set(withSink, { autoAlpha: 0, y: 0 });

      tl.fromTo(
        [...logo, ...title],
        { autoAlpha: 0, y: 0 },
        {
          autoAlpha: 1,
          y: 0,
          duration: 0.6,
          ease: "sine.out",
          stagger: STAGGER_STEP,
        },
        "-=0.62"
      )
        .fromTo(
          [...uniq, ...box, ...sink],
          { autoAlpha: 0, y: 0, scale: 1 },
          {
            autoAlpha: 1,
            y: 0,
            scale: 1,
            duration: 0.6,
            ease: "sine.out",
            stagger: STAGGER_STEP,
          },
          "-=0.34"
        )
        // Merge transition without popping movement.
        .to(withSink, { autoAlpha: 1, y: 0, duration: 0.32, ease: "sine.out" }, "-=0.08")
        .to([box, sink], { autoAlpha: 0, duration: 0.32, ease: "sine.out" }, "<")
        // Bubble reveal in sequence.
        .fromTo(
          [...bubbleUniq, ...bubbleBox],
          { autoAlpha: 0, scale: 1, y: 0 },
          {
            autoAlpha: 1,
            scale: 1,
            y: 0,
            duration: 0.66,
            ease: "sine.out",
            stagger: 0.16,
          },
          "-=0.02"
        )
        .fromTo(
          btn,
          { autoAlpha: 0, y: 0 },
          { autoAlpha: 1, y: 0, duration: 0.58, ease: "sine.out" },
          "-=0.22"
        );
    }

    timerRef.current = setTimeout(() => {
      setIndex((i) => (i + 1) % heroSlides.length);
    }, AUTO_ADVANCE_MS);

    return () => {
      tl.kill();
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [index]);

  const slide = heroSlides[index];

  return (
    <section className="hero" ref={rootRef}>
      <div
        className="hero-bg"
        style={{ backgroundImage: `url(${slide.bg})` }}
      />

      <div className="hero-content" key={slide.id}>
        {slide.type === "compact" && (
          <>
            <img src="/images/home-images/hero/compact.png" className="hero-el el-compact" alt="" />
            <div className="hero-el el-compact-text">
              комплект за филтриране на вода
            </div>
            <img src="/images/home-images/hero/BubbleWithSink1.png" className="hero-el bubble-1" alt="" />
            <img src="/images/home-images/hero/BubbleWithSink2.png" className="hero-el bubble-2" alt="" />
            <button className="hero-el hero-btn" style={slide.buttonPosition} type="button">
              ПОРЪЧАЙ СЕГА
            </button>
          </>
        )}

        {slide.type === "text" && (
          <>
            <div className="hero-el el-title-text">Винаги истински чиста</div>
            <div className="hero-el el-title-text-big">Газирана вода</div>
            <div className="hero-el el-subtitle">
              Със системите за филтриране и газиране на вода
            </div>
            <button className="hero-el hero-btn" style={slide.buttonPosition} type="button">
              ИЗБЕРИ СВОЯТА
            </button>
          </>
        )}

        {slide.type === "uniq" && (
          <>
            <img src="/images/home-images/hero/White-logo-mini.png" className="hero-el el-logo" alt="" />

            <div className="hero-el el-title-uniq">
              Филтриращи<br />системи<br />за вода
            </div>

            <div className="el-uniq-products">
              <img src="/images/home-images/hero/UNIQ.png" className="hero-el uniq-uniq" alt="" />
              <img src="/images/home-images/hero/UNIQ-BOX.png" className="hero-el uniq-box" alt="" />
              <img src="/images/home-images/hero/UNIQ-sink.png" className="hero-el uniq-sink" alt="" />
              <img src="/images/home-images/hero/UNIQ-BOX-withSink.png" className="hero-el uniq-withSink" alt="" />

              <div className="hero-el bubble bubble-uniq">
                <img src="/images/home-images/hero/OnlyBubble.png" alt="" />
                <span>UNIQ</span>
              </div>

              <div className="hero-el bubble bubble-uniqbox">
                <img src="/images/home-images/hero/OnlyBubble.png" alt="" />
                <span>UNIQ BOX</span>
              </div>
            </div>

            <button className="hero-el hero-btn" style={slide.buttonPosition} type="button">
              ОЩЕ ИНФОРМАЦИЯ
            </button>
          </>
        )}
      </div>

      <div className="hero-dots">
        {heroSlides.map((_, i) => (
          <button
            key={i}
            className={`hero-dot ${i === index ? "active" : ""}`}
            onClick={() => setIndex(i)}
            type="button"
            aria-label={`Слайд ${i + 1}`}
          />
        ))}
      </div>
    </section>
  );
};

export default Hero;
