import "./trustedBy.css";

const trustedLogos = [
  { src: "/images/trusted/sweflex.png", alt: "Client 1" },
  { src: "/images/trusted/stoichevi.png", alt: "Client 2" },
  { src: "/images/trusted/nepas.png", alt: "Client 3" },
  { src: "/images/trusted/leso.png", alt: "Client 4" },
  { src: "/images/trusted/d_tokuda.jpg", alt: "Client 5" },
  { src: "/images/trusted/bobal.jpg", alt: "Client 6" },
  { src: "/images/trusted/ajax.jpg", alt: "Client 7" },
  { src: "/images/trusted/optikom.jpg", alt: "Client 8" },
  { src: "/images/trusted/medina-med.jpg", alt: "Client 9" },
  { src: "/images/trusted/trakiya.jpg", alt: "Client 10" },
  { src: "/images/trusted/si-logo-dark.jpg", alt: "Client 11" },
  { src: "/images/trusted/promosale.png", alt: "Client 12" },
  { src: "/images/trusted/vereya.jpg", alt: "Client 13" },
  { src: "/images/trusted/Spodelena.jpg", alt: "Client 14" },
  { src: "/images/trusted/zeleniya hulm.png", alt: "Client 15" },
  { src: "/images/trusted/park and hotel Stara Zagora.png", alt: "Client 16" },
  { src: "/images/trusted/SOL-Marina-palace.png", alt: "Client 17" },
  { src: "/images/trusted/casadifiore.jpg", alt: "Client 18" },
  { src: "/images/trusted/ACCREDIA.png", alt: "Client 19" },
  { src: "/images/trusted/beroe.jpg", alt: "Client 20" },
  { src: "/images/trusted/crista-nutrition.png", alt: "Client 21" },
  { src: "/images/trusted/Devalex-1.png", alt: "Client 22" },
  { src: "/images/trusted/logo-soriko-orange.png", alt: "Client 23" },
  { src: "/images/trusted/italianoto.jpg", alt: "Client 24" },
];

export default function TrustedBy() {
  return (
    <section className="trusted-by">
      <div className="trusted-by-container">
        <h2>
          Довериха ни се
          <span className="trusted-by-dots">•••</span>
        </h2>

        <div className="trusted-by-grid">
          {trustedLogos.map((logo, index) => (
            <div key={index} className="trusted-by-item">
              <img
                src={logo.src}
                alt={logo.alt}
                loading="lazy"
              />
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}