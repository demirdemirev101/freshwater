const ContactHero = () => {
  const scrollToContact = () => {
  const section = document.getElementById("contact-section");
  const header = document.querySelector(".site-header");

  if (!section) return;

  const headerHeight = header?.offsetHeight || 0;
  const sectionTop =
    section.getBoundingClientRect().top + window.scrollY;

  window.scrollTo({
    top: sectionTop - headerHeight - 20,
    behavior: "smooth",
  });
};

  return (
    <section className="contact-hero">
      <h1>КОНТАКТИ</h1>

      <button className="hero-btn" onClick={scrollToContact}>
        Свържете се с нас
      </button>
    </section>
  );
};

export default ContactHero;


