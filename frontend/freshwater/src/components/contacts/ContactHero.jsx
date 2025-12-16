const ContactHero = () => {
  const scrollToForm = () => {
    const formSection = document.getElementById("contact-form");

    if (formSection) {
      formSection.scrollIntoView({
        behavior: "smooth",
        block: "start",
      });
    }
  };

  return (
    <section className="contact-hero">
      <h1>КОНТАКТИ</h1>
      <button className="hero-btn" onClick={scrollToForm}>
        Свържете се с нас
      </button>
    </section>
  );
};

export default ContactHero;
