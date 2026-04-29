import "../../styles/home-styles/cta.css";

const CallToAction = () => {
  return (
    <section className="home-cta">
      <div className="home-cta__inner">
        <h2 className="home-cta__title">Ready for fresher water?</h2>
        <p className="home-cta__copy">Talk to our team to find the right fit.</p>
        <button type="button" className="home-cta__button">
          Get a Quote
        </button>
      </div>
    </section>
  );
};

export default CallToAction;
