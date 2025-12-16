import ContactHero from "../components/contacts/ContactHero";
import ContactCards from "../components/contacts/ContactCards";
import ContactForm from "../components/contacts/ContactForm";
import GoogleMap from "../components/contacts/GoogleMap";

import "../styles/contacts.css";

const Contacts = () => {
  return (
    <>
      <ContactHero />
      <ContactCards />
      <ContactForm />
      <GoogleMap />
    </>
  );
};

export default Contacts;
