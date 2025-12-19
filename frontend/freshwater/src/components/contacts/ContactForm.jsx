import { useState } from "react";

const ContactForm = () => {
  const [values, setValues] = useState({
    name: "",
    phone: "",
    email: "",
    message: "",
  });

  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    setValues({
      ...values,
      [e.target.name]: e.target.value,
    });
  };

  const validate = () => {
    const newErrors = {};

    if (!values.name.trim()) {
      newErrors.name = "Моля, въведете име.";
    }

    if (!values.phone.trim()) {
      newErrors.phone = "Моля, въведете телефон.";
    } else if (values.phone.length < 6) {
      newErrors.phone = "Телефонът е невалиден.";
    }

    if (!values.email.trim()) {
      newErrors.email = "Моля, въведете имейл.";
    } else if (!/^\S+@\S+\.\S+$/.test(values.email)) {
      newErrors.email = "Невалиден имейл адрес.";
    }

    if (!values.message.trim()) {
      newErrors.message = "Това поле е задължително!.";
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    if (!validate()) return;

    // ТУК по-късно ще пращаме към backend
    console.log("FORM DATA:", values);

    alert("Съобщението е изпратено успешно!");
    setValues({ name: "", phone: "", email: "", message: "" });
  };

  return (
    <section id="contact-form" className="contact-form-section">
  <form className="contact-form" onSubmit={handleSubmit} noValidate>

    <div className={`field ${errors.name ? "error" : ""}`}>
      <input
        type="text"
        name="name"
        placeholder="Име *"
        value={values.name}
        onChange={handleChange}
      />
      {errors.name && <span className="error-text">{errors.name}</span>}
    </div>

    <div className={`field ${errors.phone ? "error" : ""}`}>
      <input
        type="tel"
        name="phone"
        placeholder="Телефон *"
        value={values.phone}
        onChange={handleChange}
      />
      {errors.phone && <span className="error-text">{errors.phone}</span>}
    </div>

    <div className={`field ${errors.email ? "error" : ""}`}>
      <input
        type="email"
        name="email"
        placeholder="Имейл *"
        value={values.email}
        onChange={handleChange}
      />
      {errors.email && <span className="error-text">{errors.email}</span>}
    </div>

    <div className={`field ${errors.message ? "error" : ""}`}>
      <textarea
        name="message"
        placeholder="Съобщение *"
        value={values.message}
        onChange={handleChange}
      />
      {errors.message && (
        <span className="error-text">{errors.message}</span>
      )}
    </div>

    <button type="submit">Изпрати</button>
  </form>
</section>
  );
};

export default ContactForm;
