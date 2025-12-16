import "../../styles/layout.css";

const Footer = () => {
  return (
    <footer className="footer">
      <div className="footer-container">

        {/* LOGO + SOCIAL */}
        <div className="footer-col">
          <div className="footer-logo">
            <img src="/images/white-logo.png" alt="Freshwater"/>
          </div>

          <div className="footer-socials">
            <a href="#" aria-label="Facebook">f</a>
            <a href="#" aria-label="Instagram">⌁</a>
          </div>
        </div>

        {/* CONTACT INFO */}
        <div className="footer-col">
          <p>📞 0884 46 67 66</p>
          <p>
            📍 гр. Стара Загора<br />
            бул. Ал. Батенберг 28, ет. 4, оф. 33
          </p>
          <p>✉️ office@freshwater.bg</p>
        </div>

        {/* LINKS 1 */}
        <div className="footer-col">
          <a href="#">Водородна вода</a>
          <a href="#">Филтрационни системи</a>
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
    </footer>
  );
};

export default Footer;
