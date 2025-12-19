import { FiPhone, FiMapPin, FiMail } from "react-icons/fi";

const ContactCards = () => {
  return (
    <div className="contact-cards">
      <div className="contact-card">
        <div className="icon-circle">
          <FiPhone />
        </div>
        <p>0884 466 766</p>
      </div>

      <div className="contact-card">
        <div className="icon-circle">
          <FiMapPin />
        </div>
        <p>
          гр. Стара Загора<br />
          бул. Ал. Батенберг 28, ет. 4, офис 33
        </p>
      </div>

      <div className="contact-card">
        <div className="icon-circle">
          <FiMail />
        </div>
        <p>office@freshwater.bg</p>
      </div>
    </div>
  );
};

export default ContactCards;
