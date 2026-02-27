import { Link } from "react-router-dom";

const breadcrumbLinkStyle = {
  color: "#e7f1ef",
  textDecoration: "none",
  transition: "color 0.2s ease, opacity 0.2s ease",
};

const handleBreadcrumbLinkMouseEnter = (event) => {
  event.currentTarget.style.color = "#0e8455";
  event.currentTarget.style.opacity = "1";
};

const handleBreadcrumbLinkMouseLeave = (event) => {
  event.currentTarget.style.color = "#e7f1ef";
  event.currentTarget.style.opacity = "1";
};

const IonizatorZaVoda = () => {
  return (
    <>
      <section
        style={{
          background: "linear-gradient(100deg, #28b28f 0%, #1a4f8f 100%)",
          borderBottom: "8px solid #ffffff",
          marginTop: "112px",
          padding: "clamp(20px, 2.6vw, 28px) clamp(16px, 7vw, 80px)",
        }}
      >
        <div style={{ maxWidth: "1400px", margin: "0 auto" }}>
          <h1
            style={{
              margin: 0,
              color: "#f4f7f8",
              fontFamily: "var(--font-jost)",
              fontWeight: 700,
              fontSize: "clamp(28px, 3.6vw, 50px)",
              lineHeight: 1.02,
              letterSpacing: "0.25px",
              textTransform: "uppercase",
            }}
          >
            Йонизатор за вода Respo
          </h1>

          <nav
            aria-label="Breadcrumb"
            style={{
              marginTop: "clamp(10px, 1.2vw, 14px)",
              display: "flex",
              alignItems: "center",
              flexWrap: "wrap",
              gap: "clamp(6px, 0.8vw, 10px)",
              fontFamily: "var(--font-jost)",
              fontSize: "clamp(12px, 0.95vw, 15px)",
              fontWeight: 400,
              color: "#e7f1ef",
            }}
          >
            <Link
              to="/"
              style={breadcrumbLinkStyle}
              onMouseEnter={handleBreadcrumbLinkMouseEnter}
              onMouseLeave={handleBreadcrumbLinkMouseLeave}
            >
              Начало
            </Link>
            <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
            <span>Йонизатор за вода Respo</span>
          </nav>
        </div>
      </section>
    </>
  );
};

export default IonizatorZaVoda;
