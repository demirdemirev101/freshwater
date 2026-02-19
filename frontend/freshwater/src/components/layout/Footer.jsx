import { FiPhone, FiMapPin, FiMail } from "react-icons/fi";
import { FaFacebookF } from "react-icons/fa";
import "../../styles/layout.css";

const Footer = () => {
  return (
    <footer className="footer">

      {/* ===== MAIN FOOTER ===== */}
      <div className="footer-main">
        <div className="footer-container">

          {/* LOGO + SOCIAL */}
          <div className="footer-col">
            <div className="footer-logo">
              <img src="/images/main-logos/white-logo.png" alt="Freshwater" />
            </div>

            <div className="footer-socials">
              <a
                href="https://www.facebook.com/"
                target="_blank"
                rel="noopener noreferrer"
              >
                <FaFacebookF />
              </a>

              <a href="tel:+359884466766">
                <FiPhone />
              </a>
            </div>
          </div>

          {/* CONTACT INFO */}
          <div className="footer-contacts">
            <div className="contact-row">
              <FiPhone />
              <span>0884 466 766</span>
            </div>

            <div className="contact-row">
              <FiMapPin />
              <span>
                гр. Стара Загора<br />
                бул. Ал. Батенберг 28, ет. 4, офис 33
              </span>
            </div>

            <div className="contact-row">
              <FiMail />
              <span>office@freshwater.bg</span>
            </div>
          </div>

          {/* LINKS 1 */}
          <div className="footer-col">
            <a href="#">Водородна вода</a>
            <a href="#">Филтриращи системи</a>
            <a href="#">Диспенсъри за вода</a>
          </div>

          {/* LINKS 2 */}
          <div className="footer-col">
            <a href="#">За нас</a>
            <a href="#">Контакти</a>
          </div>

          {/* LINKS 3 */}
          <div className="footer-col">
            <a href="#">Общи условия</a>
            <a href="#">Политика за поверителност</a>
            <a href="#">Политика за бисквитките</a>
            <a href="#">Условия за доставка</a>
            <a href="#">Връщане на продукти</a>
          </div>

        </div>
      </div>

      {/* ===== CREDITS BAR ===== */}
      <div className="footer-credits">
        <div className="footer-credits-inner">
          <span>© 2026 freshwater.bg</span>

          <span>
            Редакция:{" "}
            <a
              href="https://digitalis.bg"
              target="_blank"
              rel="noopener noreferrer"
            >
              Digitalis
            </a>
          </span>

          <span>
            Дизайн и изработка:{" "}
            <a
              href="https://digitalis.bg"
              target="_blank"
              rel="noopener noreferrer"
            >
              Digitalis
            </a>
          </span>
        </div>
      </div>

    </footer>
  );
};

export default Footer;
