import { useEffect, useState } from "react";
import "../../styles/layout.css";

const Header = () => {
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 80);
    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  return (
    <header className={`header ${scrolled ? "scrolled" : ""}`}>
      <div className="header-container">

        {/* LOGO */}
        <div className="logo">
          <img src="/images/logo.png" alt="Freshwater" />
        </div>

        {/* NAV */}
        <nav className="nav">
          <a href="#">ЙОНИЗАТОР ЗА ВОДА</a>
          <a href="#">ЗА ДОМА</a>
          <a href="#">ЗА БИЗНЕСА</a>
          <a href="#">ЗА НАС</a>
          <a href="#">КОНТАКТИ</a>
        </nav>

        {/* SEARCH + CART */}
        <div className="header-actions">

          <div className="search-box">
            <input type="text" placeholder="Търсене..." />
            <span className="search-icon">🔍</span>
          </div>

          <div className="cart-btn">
            🛒
            <span className="cart-badge">0</span>
          </div>

        </div>

      </div>
    </header>
  );
};

export default Header;

