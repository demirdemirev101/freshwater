import { NavLink } from "react-router-dom";
import { useEffect, useState } from "react";
import "../../styles/layout.css";

const Header = () => {
  const [scrolled, setScrolled] = useState(false);
  const [openMenu, setOpenMenu] = useState(null);
  const [openSubmenu, setOpenSubmenu] = useState(null);

  useEffect(() => {
    const onScroll = () => {
      setScrolled(window.scrollY > 50);
    };

    window.addEventListener("scroll", onScroll);
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  const zaDomaMenu = [
    {
      label: "\u0421\u0438\u0441\u0442\u0435\u043c\u0438 \u0437\u0430 \u0432\u043e\u0434\u0430",
      to: "/za-doma?homeFilter=water-systems",
    },
    {
      label: "\u0410\u043a\u0441\u0435\u0441\u043e\u0430\u0440\u0438",
      to: "/za-doma?homeFilter=accessories",
      key: "za-doma-aksesoari",
      children: [
        {
          label: "\u0424\u0438\u043b\u0442\u0440\u0438 \u0438 \u043a\u043e\u043d\u0441\u0443\u043c\u0430\u0442\u0438\u0432\u0438",
          to: "/za-doma?homeFilter=filters-consumables",
        },
        { label: "\u0411\u0443\u0442\u0438\u043b\u043a\u0438", to: "/za-doma?homeFilter=bottles" },
        { label: "\u0421\u043c\u0435\u0441\u0438\u0442\u0435\u043b\u0438", to: "/za-doma?homeFilter=mixers" },
      ],
    },
    { label: "\u0412\u043e\u0434\u043e\u0440\u043e\u0434\u043d\u0430 \u0432\u043e\u0434\u0430", to: "/za-doma?homeFilter=hydrogen-water" },
  ];

  const zaBiznesaMenu = [
    {
      label: "\u0421\u0438\u0441\u0442\u0435\u043c\u0438 \u0437\u0430 \u0432\u043e\u0434\u0430",
      to: "/za-biznesa?businessFilter=water-systems",
    },
    { label: "\u0425\u043e\u0420\u0435\u041a\u0430", to: "/za-biznesa?businessFilter=horeca" },
    {
      label: "\u0410\u043a\u0441\u0435\u0441\u043e\u0430\u0440\u0438",
      to: "/za-biznesa?businessFilter=accessories",
      key: "za-biznesa-aksesoari",
      children: [
        {
          label: "\u0424\u0438\u043b\u0442\u0440\u0438 \u0438 \u043a\u043e\u043d\u0441\u0443\u043c\u0430\u0442\u0438\u0432\u0438",
          to: "/za-biznesa?businessFilter=filters-consumables",
        },
        { label: "\u0411\u0443\u0442\u0438\u043b\u043a\u0438", to: "/za-biznesa?businessFilter=bottles" },
        {
          label: "\u0412\u043e\u0434\u043d\u0438 \u043a\u043e\u043b\u043e\u043d\u0438",
          to: "/za-biznesa?businessFilter=water-coolers",
        },
        {
          label: "\u0414\u043e\u043f\u044a\u043b\u043d\u0438\u0442\u0435\u043b\u043d\u043e \u043e\u0431\u043e\u0440\u0443\u0434\u0432\u0430\u043d\u0435",
          to: "/za-biznesa?businessFilter=additional-equipment",
        },
        { label: "\u0428\u043a\u0430\u0444\u043e\u0432\u0435", to: "/za-biznesa?businessFilter=cabinets" },
      ],
    },
  ];

  const renderDropdown = (menu, variant = "default") => {
    const activeParent = menu.find((item) => item.key === openSubmenu && item.children);
    const isHomeVariant = variant === "home";

    return (
      <div className={`nav-dropdown-wrap${isHomeVariant ? " nav-dropdown-wrap--home" : ""}`}>
        <div className={`nav-dropdown-panel${isHomeVariant ? " nav-dropdown-panel--home" : ""}`}>
          {menu.map((item) => (
            (() => {
              const isActiveSubmenuParent = Boolean(item.children && item.key === openSubmenu);
              const linkClassName = `nav-dropdown-link${
                isHomeVariant ? " nav-dropdown-link--home" : ""
              }${isActiveSubmenuParent ? " is-open" : ""}`;

              return (
            <NavLink
              key={item.label}
              to={item.to}
              className={linkClassName}
              onMouseEnter={() => {
                if (item.children) {
                  setOpenSubmenu(item.key);
                } else if (openSubmenu) {
                  setOpenSubmenu(null);
                }
              }}
            >
              <span>{item.label}</span>
              {item.children && <span className="nav-dropdown-arrow">{"\u2192"}</span>}
            </NavLink>
              );
            })()
          ))}
        </div>

        {activeParent && activeParent.children && (
  <div
            className={`nav-dropdown-panel nav-dropdown-panel--sub is-visible${
              isHomeVariant ? " nav-dropdown-panel--home nav-dropdown-panel--sub-home" : ""
            }`}
          >
            {activeParent.children.map((childItem) => (
              <NavLink
                key={childItem.label}
                to={childItem.to}
                className={`nav-dropdown-link${isHomeVariant ? " nav-dropdown-link--home" : ""}`}
              >
                <span>{childItem.label}</span>
              </NavLink>
            ))}
          </div>
        )}
      </div>
    );
  };

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
          <NavLink to="/ionizator-za-voda" className="nav-link">
            {"\u0419\u041E\u041D\u0418\u0417\u0410\u0422\u041E\u0420 \u0417\u0410 \u0412\u041E\u0414\u0410"}
          </NavLink>

          <div
            className="nav-item nav-item--has-dropdown"
            onMouseEnter={() => {
              setOpenMenu("za-doma");
              setOpenSubmenu(null);
            }}
            onMouseLeave={() => {
              setOpenMenu(null);
              setOpenSubmenu(null);
            }}
          >
            <NavLink to="/za-doma" className="nav-link nav-link--menu">
              {"\u0417\u0410 \u0414\u041E\u041C\u0410"}
              <span className="nav-caret" aria-hidden="true" />
            </NavLink>

            {openMenu === "za-doma" && renderDropdown(zaDomaMenu, "home")}
          </div>

          <div
            className="nav-item nav-item--has-dropdown"
            onMouseEnter={() => {
              setOpenMenu("za-biznesa");
              setOpenSubmenu(null);
            }}
            onMouseLeave={() => {
              setOpenMenu(null);
              setOpenSubmenu(null);
            }}
          >
            <NavLink to="/za-biznesa" className="nav-link nav-link--menu">
              {"\u0417\u0410 \u0411\u0418\u0417\u041D\u0415\u0421\u0410"}
              <span className="nav-caret" aria-hidden="true" />
            </NavLink>

            {openMenu === "za-biznesa" && renderDropdown(zaBiznesaMenu, "home")}
          </div>

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
