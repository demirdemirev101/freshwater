import { useState } from "react";
import "./aboutForm.css";

export default function AboutForm() {
  const [form, setForm] = useState({
    name: "",
    phone: "",
    email: "",
  });

  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    const newErrors = {};
    if (!form.name) newErrors.name = "Полето е задължително";
    if (!form.phone) newErrors.phone = "Полето е задължително";
    if (!form.email) newErrors.email = "Полето е задължително";

    setErrors(newErrors);

    if (Object.keys(newErrors).length === 0) {
      // submit logic (API)
      console.log("OK", form);
    }
  };

  const handleScrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <section className="about-form" id="about-form">
      <div className="about-form-overlay">
        <div className="about-form-content">
          <button
            type="button"
            className="about-form-top"
            onClick={handleScrollToTop}
            aria-label="Back to top"
          >
            ↑
          </button>

          <h2>Свържи се с нас</h2>

          <p className="about-form-subtitle">
            Чудите се кой е правилния вариант за вас? Ние ще ви помогнем в избора.
            Попълнете формата за контакти и ние ще се свържем с вас!
          </p>

          <form className="about-form-fields" onSubmit={handleSubmit}>
            <div className="field">
              <label>Име <span>*</span></label>
              <input
                name="name"
                value={form.name}
                onChange={handleChange}
                className={errors.name ? "error" : ""}
              />
              {errors.name && <small>{errors.name}</small>}
            </div>

            <div className="field">
              <label>Телефон <span>*</span></label>
              <input
                name="phone"
                value={form.phone}
                onChange={handleChange}
                className={errors.phone ? "error" : ""}
              />
              {errors.phone && <small>{errors.phone}</small>}
            </div>

            <div className="field">
              <label>Email <span>*</span></label>
              <input
                name="email"
                value={form.email}
                onChange={handleChange}
                className={errors.email ? "error" : ""}
              />
              {errors.email && <small>{errors.email}</small>}
            </div>

            <div className="form-submit">
              <button type="submit">Изпрати</button>
            </div>
          </form>

        </div>
      </div>
    </section>
  );
}
