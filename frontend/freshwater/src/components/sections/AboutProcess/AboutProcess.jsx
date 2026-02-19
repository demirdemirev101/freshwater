import { useEffect, useRef } from "react";
import "./aboutProcess.css";

function LogoSlider({ title, items }) {
  const trackRef = useRef(null);
  const viewportRef = useRef(null);

  const indexRef = useRef(0);
  const intervalRef = useRef(null);
  const pausedRef = useRef(false);

  /* AUTO MOVE */
  useEffect(() => {
    const track = trackRef.current;
    if (!track) return;

    const cardWidth = 280 + 20;
    const visibleCount = 4;
    const maxIndex = items.length - visibleCount;

    intervalRef.current = setInterval(() => {
      if (pausedRef.current) return;

      indexRef.current++;

      if (indexRef.current > maxIndex) {
        indexRef.current = 0;
      }

      track.style.transform =
        `translateX(-${indexRef.current * cardWidth}px)`;
    }, 1200);

    return () => clearInterval(intervalRef.current);
  }, [items.length]);

  /* CLICK = STOP AUTO FOREVER */
  const handleClick = () => {
    pausedRef.current = true;
    clearInterval(intervalRef.current);
  };

  /* DRAG */
  useEffect(() => {
    const viewport = viewportRef.current;
    if (!viewport) return;

    let isDown = false;
    let startX = 0;
    let scrollLeft = 0;

    const down = (e) => {
      isDown = true;
      startX = e.pageX;
      scrollLeft = viewport.scrollLeft;
      e.preventDefault();
    };

    const move = (e) => {
      if (!isDown) return;
      const walk = e.pageX - startX;
      viewport.scrollLeft = scrollLeft - walk;
    };

    const up = () => {
      isDown = false;
    };

    viewport.addEventListener("mousedown", down);
    window.addEventListener("mousemove", move);
    window.addEventListener("mouseup", up);

    return () => {
      viewport.removeEventListener("mousedown", down);
      window.removeEventListener("mousemove", move);
      window.removeEventListener("mouseup", up);
    };
  }, []);

  return (
    <div className="process-block">
      <h3 className="process-title">{title}</h3>
      <div className="process-dots">•••</div>

      <div
        className="process-viewport"
        ref={viewportRef}
        onMouseDown={handleClick}
      >
        <div className="process-track" ref={trackRef}>
          {items.map((logo, i) => (
            <div className="process-card" key={i}>
              <img
                src={logo.src}
                alt={logo.alt}
                draggable={false}
              />
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

export default function AboutProcess() {
  const certificates = [
    { src: "/images/process/ACS.jpg" },
    { src: "/images/process/immetro.png" },
    { src: "/images/process/etl-1.png" },
    { src: "/images/process/CE-1.png" },
    { src: "/images/process/tuvr.png" },
    { src: "/images/process/made-in-italy.png" },
    { src: "/images/process/immagine7.jpg" },
    { src: "/images/process/nsf.png" },
    { src: "/images/process/rohs-5.png" },
    { src: "/images/process/alim.png" },
    { src: "/images/process/ACCREDIA.png" },
  ];

  const associations = [
    { src: "/images/process/we.jpg" },
    { src: "/images/process/gwca.jpg" },
    { src: "/images/process/WHA.png" },
    { src: "/images/process/water-quality.jpg" },
    { src: "/images/process/FACHVERBAND.jpg" },
    { src: "/images/process/confida.jpg" },
    { src: "/images/process/bdv.jpg" },
    { src: "/images/process/nra.png" },
    { src: "/images/process/aqua-italiajpg.jpg" },
    { src: "/images/process/WI.jpg" },
  ];

  return (
    <section className="about-process">
      <div className="about-process-wrap">
        <LogoSlider title="Сертификати" items={certificates} />
        <LogoSlider title="Асоциации" items={associations} />
      </div>
    </section>
  );
}
