import { useEffect, useState } from "react";
import "../../styles/layout.css";

const Header = () => {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => {
      setScrolled(window.scrollY > 50);
    };

    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <header className="header">
      <div className="header-container">
        
        {/* LOGO */}
        <div className="logo">
          <img
            src="/images/logo.png"
            alt="Freshwater"
            className={`logo-img ${scrolled ? "logo-img--small" : ""}`}
          />
        </div>

        {/* NAV */}
        <nav className="nav">
          <a href="/" className="nav-link">ЙОНИЗАТОР ЗА ВОДА</a>
          <a href="/" className="nav-link">ЗА ДОМА</a>
          <a href="/" className="nav-link">ЗА БИЗНЕСА</a>
          <a href="/" className="nav-link">ЗА НАС</a>
          <a href="/contacts" className="nav-link active">КОНТАКТИ</a>
        </nav>

        {/* RIGHT */}
       <div className="nav-right">

  {/* SEARCH */}
  <div className="search-box">
    <svg
      className="search-icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <circle cx="11" cy="11" r="7" />
      <line x1="16.5" y1="16.5" x2="22" y2="22" />
    </svg>

    <input
      type="text"
      placeholder="търсене..."
      className="search-input"
    />
  </div>

  {/* CART */}
  <div className="cart-box">
    <svg
      className="cart-icon"
      viewBox="0 0 24 24"
      aria-hidden="true"
    >
      <path d="M6 6h15l-1.5 9h-12z" />
      <circle cx="9" cy="21" r="1" />
      <circle cx="18" cy="21" r="1" />
    </svg>

    <span className="cart-count">0</span>
  </div>

</div>

      </div>
    </header>
  );
};

export default Header;
