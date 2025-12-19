import ContactHero from "../components/contacts/ContactHero";
import ContactCards from "../components/contacts/ContactCards";
import ContactForm from "../components/contacts/ContactForm";
import GoogleMap from "../components/contacts/GoogleMap";

import "../styles/contacts.css";

const Contacts = () => {
  return (
    <>
    <>
  <ContactHero />

  <section id="contact-section">
    <ContactCards />
    <ContactForm />
  </section>
</>
      <GoogleMap />
    </>
  );
};

export default Contacts;
