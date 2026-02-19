import { useEffect, useRef, useState } from "react";
import gsap from "gsap";
import heroSlides from "./heroSlides";
import "../../styles/home-styles/hero.css";

const IDLE_DURATION = 6;

const Hero = () => {
  const [index, setIndex] = useState(0);
  const rootRef = useRef(null);
  const tlRef = useRef(null);

  useEffect(() => {
    const root = rootRef.current;
    if (!root) return;

    const q = gsap.utils.selector(root);
    const bg = q(".hero-bg");

    if (tlRef.current) tlRef.current.kill();

    const slide = heroSlides[index];

    const tl = gsap.timeline({
      defaults: { ease: "power2.out" },
      onComplete: () => {
        setIndex((i) => (i + 1) % heroSlides.length);
      },
    });

    tlRef.current = tl;

    // RESET
    tl.set(q(".hero-el"), { autoAlpha: 0, y: 25, scale: 1 });
    tl.set(bg, { opacity: 1 });

    // Soft background fade-in
    tl.fromTo(
      bg,
      { opacity: 0.92 },
      { opacity: 1, duration: 1.1, ease: "sine.out" }
    );

    // ======================
    // SLIDE 1
    // ======================
    if (slide.type === "compact") {
      tl.to(q(".el-compact"), { autoAlpha: 1, y: 0, duration: 0.8 })
        .to(q(".el-compact-text"), { autoAlpha: 1, y: 0 }, "-=0.6")
        .to(q(".bubble-1"), { autoAlpha: 1, y: 0 }, "-=0.5")
        .to(q(".bubble-2"), { autoAlpha: 1, y: 0 }, "-=0.6")
        .to(q(".hero-btn"), { autoAlpha: 1, y: 0 }, "-=0.6");
    }

    // ======================
    // SLIDE 2
    // ======================
    if (slide.type === "text") {
      tl.to(q(".el-title-text"), { autoAlpha: 1, y: 0, duration: 0.8 })
        .to(q(".el-title-text-big"), { autoAlpha: 1, y: 0 }, "-=0.6")
        .to(q(".el-subtitle"), { autoAlpha: 1, y: 0 }, "-=0.5")
        .to(q(".hero-btn"), { autoAlpha: 1, y: 0 }, "-=0.5");
    }

    // ======================
    // SLIDE 3
    // ======================
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

      tl.set(withSink, { autoAlpha: 0 });

      tl.to(logo, { autoAlpha: 1, y: 0 })
        .to(title, { autoAlpha: 1, y: 0 }, "-=0.6")

        .to(uniq, { autoAlpha: 1, y: 0 }, "-=0.5")
        .to(box, { autoAlpha: 1, y: 0 }, "-=0.6")
        .to(sink, { autoAlpha: 1, y: 0 }, "-=0.6")

        // merge effect
        .to(withSink, { autoAlpha: 1, duration: 0.25 }, "-=0.1")
        .to([box, sink], { autoAlpha: 0, duration: 0.25 }, "<")

        // smooth bubble drop
        .fromTo(
          bubbleUniq,
          { autoAlpha: 0, scale: 0.8, y: -15 },
          { autoAlpha: 1, scale: 1, y: 0, duration: 0.8, ease: "power3.out" },
          "-=0.2"
        )
        .fromTo(
          bubbleBox,
          { autoAlpha: 0, scale: 0.8, y: -15 },
          { autoAlpha: 1, scale: 1, y: 0, duration: 0.8, ease: "power3.out" },
          "-=0.6"
        )

        .to(btn, { autoAlpha: 1, y: 0 }, "-=0.6");
    }

    // IDLE
    tl.to({}, { duration: IDLE_DURATION });

    return () => tl.kill();
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
          />
        ))}
      </div>
    </section>
  );
};

export default Hero;
