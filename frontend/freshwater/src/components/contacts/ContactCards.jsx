const ContactCards = () => {
  return (
    <section className="contact-cards">

      <a href="tel:0884466766" className="contact-card">
        <div className="icon-circle">📞</div>
        <p>0884 466 766</p>
      </a>

      <a
        href="https://maps.google.com/?q=Aleksandar+Batenberg+28+Stara+Zagora"
        target="_blank"
        rel="noreferrer"
        className="contact-card"
      >
        <div className="icon-circle">📍</div>
        <p>
          гр. Стара Загора<br />
          бул. Ал. Батенберг 28, ет. 4, офис 33
        </p>
      </a>

      <a href="mailto:office@freshwater.bg" className="contact-card">
        <div className="icon-circle">✉️</div>
        <p>office@freshwater.bg</p>
      </a>

    </section>
  );
};

export default ContactCards;
