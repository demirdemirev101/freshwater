import { NavLink } from "react-router-dom";
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
    <header className={`header ${scrolled ? "header--scrolled" : ""}`}>
      <div className="header-container">
        <div className="logo">
          <NavLink to="/">
            <img
              src="/images/main-logos/logo.png"
              alt="Freshwater"
              className={`logo-img ${scrolled ? "logo-img--small" : ""}`}
            />
          </NavLink>
        </div>

        <nav className="nav">
          <NavLink to="/za-doma" className="nav-link">
            {"\u0419\u041E\u041D\u0418\u0417\u0410\u0422\u041E\u0420 \u0417\u0410 \u0412\u041E\u0414\u0410"}
          </NavLink>

          <NavLink to="/za-doma" className="nav-link">
            {"\u0417\u0410 \u0414\u041E\u041C\u0410"}
          </NavLink>

          <NavLink to="/za-biznesa" className="nav-link">
            {"\u0417\u0410 \u0411\u0418\u0417\u041D\u0415\u0421\u0410"}
          </NavLink>

          <NavLink
            to="/about"
            className={({ isActive }) =>
              "nav-link" + (isActive ? " active" : "")
            }
          >
            {"\u0417\u0410 \u041D\u0410\u0421"}
          </NavLink>

          <NavLink
            to="/contacts"
            className={({ isActive }) =>
              "nav-link" + (isActive ? " active" : "")
            }
          >
            {"\u041A\u041E\u041D\u0422\u0410\u041A\u0422\u0418"}
          </NavLink>
        </nav>

        <div className="nav-right">
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
              placeholder={"\u0442\u044A\u0440\u0441\u0435\u043D\u0435..."}
              className="search-input"
            />
          </div>

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
