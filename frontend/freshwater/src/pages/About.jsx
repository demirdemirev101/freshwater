import "../styles/about.css";
import TrustedBy from "../components/sections/TrustedBy/TrustedBy";
import AboutAwards from "../components/sections/AboutAwards/AboutAwards";
import AboutProcess from "../components/sections/AboutProcess/AboutProcess";
import AboutForm from "../components/sections/AboutForm/AboutForm";
import { FiCheck } from "react-icons/fi";

export default function About() {
  const handleScrollToForm = () => {
    const formSection = document.getElementById("about-form");
    if (formSection) {
      formSection.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  };

  return (
    <>
      {/* HERO */}
      <section className="about-hero">
        <div className="about-hero-overlay">
          <div className="about-hero-content">
            <h1>ЗА НАС</h1>
            <button className="about-hero-btn" onClick={handleScrollToForm}>
              Свържете се с нас
            </button>
          </div>
        </div>
      </section>

      {/* WHO WE ARE */}
      <section className="about-who">
        <div className="about-who-container">
          <h2>
            Кой сме <span className="about-accent">ние?</span>
          </h2>

          <div className="about-who-content">
            <div className="about-who-logo">
              <img src="/images/main-logos/logo-one-color.png" alt="Freshwater logo" />
            </div>

            <div className="about-who-text">
              <p className="highlight">
                Повече от 15 години нашата компания е водещ доставчик на
                висококачествени филтриращи системи за вода за дома и бизнеса.
              </p>

              <p>
                Сътрудничим си с утвърдени италиански производители,
                известни със своите иновации и надеждност в областта на
                филтрирането, охлаждането и газирането на вода.
              </p>

              <p>
                Нашата цел е да осигурим на клиентите си чиста и безопасна питейна вода, чрез решения с активна филтрация,
                които покриват и на най-високите европейски стандарти за качество.
                Продуктовата ни гама включва разнообразие от филтри и системи, подходящи както за бита, така и за бизнеса.
                Разбираме, че всеки има специфични изисквания и нужди.
                Екипът ни от опитни специалисти ще ви съпътства във всеки един етап - от избора на система за филтриране на вода,
                през нейният монтаж и последваща поддръжка и обслужване, което ще ви гарантира дългосрочна и ефективна работа на избраната система.
                С Freshwater пренасянето на бутилки и постоянното пълнене на кани остава в миналото.
              </p>

              <p>
               Вярваме, че достъпът до чиста и качествена вода е ключов фактор за здравословен начин на живот,
               който не трябва да води до трудности в ежедневието ни.
               Чрез нашите продукти и услуги се стремим да допринесем както за подобряване на качеството на живот на нашите клиенти,
               така и за опазването на околната среда,
               намалявайки значително всекидневната употреба на пластмасови бутилки.
              </p>

            </div>
          </div>
        </div>
      </section>

      {/* STATS – ОТДЕЛНА СЕКЦИЯ */}
      <section className="about-stats">
        <div className="about-stats-container">

          <div className="about-stat">
            <FiCheck className="stat-icon" />
            <h3>15 години опит</h3>
            <p>
              В достаква и монтаж на системи за активни филтриране на вода за дома, офиса, ХоРеКа
            </p>
          </div>

          <div className="about-stat">
            <FiCheck className="stat-icon" />
            <h3>над 600 локации</h3>
            <p>
             На, които се използват ежедневно наши системи за филтриране на вода
            </p>
          </div>

          <div className="about-stat">
            <FiCheck className="stat-icon" />
            <h3>Над 48 000</h3>
            <p>
           души всеки месец пият вода ежедневно вода от наши системи, които сме инсталирали.
            </p>
          </div>

          <div className="about-stat">
            <FiCheck className="stat-icon" />
            <h3>над 130 000</h3>
            <p>
              литра вода всеки ден бива филтрирана през наши системи за вода
            </p>
          </div>

        </div>
      </section>

      <TrustedBy />
      <AboutAwards />
      <AboutProcess />
      <AboutForm />
    </>
  );
}
