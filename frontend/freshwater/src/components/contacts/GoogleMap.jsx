const GoogleMap = () => {
  return (
    <section className="map-section">
      <div className="map-wrapper">
        <iframe
          title="Freshwater Map"
          src="https://www.google.com/maps?q=bul.%20Aleksandar%20Batenberg%2028,%20Stara%20Zagora&output=embed"
          loading="lazy"
        />
      </div>
    </section>
  );
};

export default GoogleMap;