import "../../styles/home-styles/promo-banner.css";

const PromoBanner = () => {
  return (
    <section className="home-promo-banner">
      <div className="home-promo-banner__inner">
        <h3 className="home-promo-banner__title">Limited Offer</h3>
        <p className="home-promo-banner__copy">
          Save on premium filtration systems this week.
        </p>
        <button type="button" className="home-promo-banner__cta">
          View Offers
        </button>
      </div>
    </section>
  );
};

export default PromoBanner;
