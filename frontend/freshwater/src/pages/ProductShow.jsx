import { useEffect, useMemo, useState } from "react";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
import { getProductById } from "../services/productsApi";
import { useCart } from "../context/CartContext";
import "../styles/product-show.css";

const BGN_RATE = 1.95583;
const MIN_QTY = 1;
const MAX_QTY = 99;

const formatMoney = (value) => {
  const num = Number(value);

  if (!Number.isFinite(num)) {
    return null;
  }

  return num.toFixed(2);
};

const clampQuantity = (value) => {
  const qty = Math.round(Number(value));

  if (!Number.isFinite(qty) || qty < MIN_QTY) return MIN_QTY;
  if (qty > MAX_QTY) return MAX_QTY;

  return qty;
};

const ProductShow = () => {
  const { productId } = useParams();
  const location = useLocation();
  const navigate = useNavigate();
  const { addToCart } = useCart();
  const [product, setProduct] = useState(null);
  const [quantity, setQuantity] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [showAddedMessage, setShowAddedMessage] = useState(false);

  const normalizedProductId = useMemo(() => String(productId || ""), [productId]);
  const returnTo = location.state?.from || "/produkti";

  useEffect(() => {
    let isMounted = true;

    const loadProduct = async () => {
      try {
        setIsLoading(true);
        const matchedProduct = await getProductById(normalizedProductId);
        if (isMounted) setProduct(matchedProduct || null);
      } catch (err) {
        console.log(err);
        if (isMounted) setProduct(null);
      } finally {
        if (isMounted) setIsLoading(false);
      }
    };

    loadProduct();

    return () => {
      isMounted = false;
    };
  }, [normalizedProductId]);

  useEffect(() => {
    setQuantity(1);
    setShowAddedMessage(false);
  }, [normalizedProductId]);

  const image = product?.images?.[0]?.url;
  const basePriceNum = Number(product?.price);
  const salePriceNum = Number(product?.sale_price);
  const hasBasePrice = Number.isFinite(basePriceNum) && basePriceNum > 0;
  const hasSalePrice =
    Number.isFinite(salePriceNum) &&
    salePriceNum > 0 &&
    (!hasBasePrice || salePriceNum < basePriceNum);
  const basePrice = hasBasePrice ? formatMoney(basePriceNum) : null;
  const basePriceBgn = hasBasePrice ? formatMoney(basePriceNum * BGN_RATE) : null;
  const salePrice = hasSalePrice ? formatMoney(salePriceNum) : null;
  const salePriceBgn = hasSalePrice ? formatMoney(salePriceNum * BGN_RATE) : null;
  const descriptionHtml = product?.description || product?.short_description || "";

  const handleQuantityInput = (event) => {
    const raw = event.target.value;

    if (raw === "") {
      setQuantity(MIN_QTY);
      return;
    }

    setQuantity(clampQuantity(raw));
  };

  const handleIncreaseQuantity = () => {
    setQuantity((prev) => clampQuantity(prev + 1));
  };

  const handleDecreaseQuantity = () => {
    setQuantity((prev) => clampQuantity(prev - 1));
  };

  const handleAddToCart = () => {
    if (!product) return;

    addToCart(product, quantity);
    setShowAddedMessage(true);
  };

  const handleBuyNow = () => {
    if (!product) return;

    addToCart(product, quantity);
    navigate("/cart");
  };

  return (
    <>
      <section
        style={{
          background: "linear-gradient(100deg, #28b28f 0%, #1a4f8f 100%)",
          borderBottom: "8px solid #ffffff",
          marginTop: "80px",
          padding: "clamp(20px, 2.6vw, 28px) clamp(16px, 7vw, 80px)",
        }}
      >
        <div style={{ maxWidth: "1400px", margin: "0 auto" }}>
          <h1
            style={{
              margin: 0,
              color: "#f4f7f8",
              fontFamily: "var(--font-jost)",
              fontWeight: 700,
              fontSize: "clamp(28px, 3.2vw, 46px)",
              lineHeight: 1.02,
              letterSpacing: "0.25px",
              textTransform: "uppercase",
            }}
          >
            {product?.name || "Продукт"}
          </h1>

          <nav
            aria-label="Breadcrumb"
            style={{
              marginTop: "clamp(10px, 1.2vw, 14px)",
              display: "flex",
              alignItems: "center",
              flexWrap: "wrap",
              gap: "clamp(6px, 0.8vw, 10px)",
              fontFamily: "var(--font-jost)",
              fontSize: "clamp(12px, 0.95vw, 15px)",
              fontWeight: 400,
              color: "#e7f1ef",
            }}
          >
            <Link to="/" style={{ color: "#e7f1ef", textDecoration: "none" }}>
              Начало
            </Link>
            <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
            <Link to="/produkti" style={{ color: "#e7f1ef", textDecoration: "none" }}>
              Продукти
            </Link>
            <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
            <span>{product?.name || "Продукт"}</span>
          </nav>
        </div>
      </section>

      {showAddedMessage && product && (
        <section className="product-show-added-wrap" aria-live="polite">
          <div className="product-show-added">
            <p className="product-show-added__text">
              „{product.name}“ е добавен във вашата количка.
            </p>
            <Link to="/cart" className="product-show-added__link">
              преглед на количката
            </Link>
          </div>
        </section>
      )}

      <section
        style={{
          padding: "clamp(28px, 4.8vw, 64px) clamp(16px, 7vw, 80px)",
          background: "#ffffff",
        }}
      >
        <div
          style={{
            maxWidth: "1400px",
            margin: "0 auto",
            display: "grid",
            gridTemplateColumns: "repeat(auto-fit, minmax(300px, 1fr))",
            alignItems: "start",
            gap: "clamp(24px, 5vw, 70px)",
          }}
        >
          <div
            style={{
              width: "min(85vw, 500px)",
              height: "min(85vw, 500px)",
              borderRadius: "50%",
              background: "linear-gradient(100deg, #69cbb2 0%, #124480aa 100%)",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              margin: "0 auto",
            }}
          >
            <div
              style={{
                width: "min(76vw, 450px)",
                height: "min(76vw, 450px)",
                borderRadius: "50%",
                background: "linear-gradient(135deg, #3ab89d 0%, #1f5a81 100%)",
                display: "flex",
                alignItems: "center",
                justifyContent: "center",
                overflow: "hidden",
              }}
            >
              {image && (
                <img
                  src={image}
                  alt={product?.name || "Продукт"}
                  loading="eager"
                  decoding="async"
                  style={{
                    width: "88%",
                    objectFit: "contain",
                    transform: "translateY(8px)",
                  }}
                />
              )}
            </div>
          </div>

          <div style={{ maxWidth: "860px" }}>
            {isLoading ? (
              <p
                style={{
                  margin: 0,
                  fontFamily: "var(--font-jost)",
                  fontSize: "18px",
                  color: "#35506a",
                }}
              >
                Зареждане...
              </p>
            ) : !product ? (
              <p
                style={{
                  margin: 0,
                  fontFamily: "var(--font-jost)",
                  fontSize: "18px",
                  color: "#35506a",
                }}
              >
                Продуктът не е намерен.
              </p>
            ) : (
              <>
                <div
                  style={{
                    fontFamily: "var(--font-jost)",
                    fontWeight: 700,
                    fontSize: "clamp(20px, 2.2vw, 34px)",
                    lineHeight: 1.08,
                    letterSpacing: "0.2px",
                    display: "flex",
                    alignItems: "baseline",
                    gap: "clamp(10px, 0.9vw, 16px)",
                    flexWrap: "wrap",
                  }}
                >
                  {hasSalePrice ? (
                    <>
                      {basePrice && (
                        <span
                          style={{
                            textDecoration: "line-through",
                            color: "#4b7fbe",
                            fontSize: "0.88em",
                            textDecorationColor: "#4b7fbe",
                            textDecorationThickness: "1.6px",
                          }}
                        >
                          {basePrice} {"\u20ac"}
                        </span>
                      )}
                      <span style={{ color: "#1f9e7f" }}>
                        {salePrice} {"\u20ac"}
                      </span>
                      {salePriceBgn && <span style={{ color: "#139cc8" }}>/ {salePriceBgn} лв.</span>}
                    </>
                  ) : hasBasePrice ? (
                    <>
                      <span style={{ color: "#1f9e7f" }}>
                        {basePrice} {"\u20ac"}
                      </span>
                      {basePriceBgn && <span style={{ color: "#139cc8" }}>/ {basePriceBgn} лв.</span>}
                    </>
                  ) : (
                    <span style={{ color: "#139cc8", fontWeight: 600 }}>По запитване</span>
                  )}
                </div>

                <div className="product-show-action-row">
                  <div className="product-show-qty">
                    <button
                      type="button"
                      aria-label="Намали количество"
                      onClick={handleDecreaseQuantity}
                    >
                      −
                    </button>
                    <input
                      type="number"
                      inputMode="numeric"
                      min={MIN_QTY}
                      max={MAX_QTY}
                      value={quantity}
                      onChange={handleQuantityInput}
                    />
                    <button
                      type="button"
                      aria-label="Увеличи количество"
                      onClick={handleIncreaseQuantity}
                    >
                      +
                    </button>
                  </div>

                  <button type="button" className="product-show-buy-btn" onClick={handleBuyNow}>
                    <span>Купи сега</span>
                    <span aria-hidden="true">→</span>
                  </button>
                </div>

                <button type="button" className="product-show-add-btn" onClick={handleAddToCart}>
                  Добави в количката
                </button>

                <h2
                  style={{
                    marginTop: "clamp(14px, 1.2vw, 20px)",
                    marginBottom: 0,
                    fontFamily: "var(--font-jost)",
                    fontWeight: 400,
                    fontSize: "clamp(23px, 2vw, 36px)",
                    lineHeight: 1.16,
                    letterSpacing: "0.35px",
                    color: "#0d5fa8",
                    textTransform: "uppercase",
                  }}
                >
                  {product?.name}
                </h2>

                {descriptionHtml && (
                  <div
                    className="za-doma-list-description"
                    style={{
                      marginTop: "clamp(16px, 1.5vw, 24px)",
                      marginBottom: 0,
                      fontFamily: "var(--font-jost)",
                      fontSize: "clamp(14px, 0.95vw, 18px)",
                      lineHeight: 1.5,
                      color: "#2a4560",
                    }}
                    dangerouslySetInnerHTML={{ __html: descriptionHtml }}
                  />
                )}
              </>
            )}

            <div style={{ marginTop: "clamp(20px, 2.2vw, 32px)" }}>
              <Link
                to={returnTo}
                style={{
                  display: "inline-flex",
                  alignItems: "center",
                  height: "38px",
                  padding: "0 16px",
                  borderRadius: "19px",
                  textDecoration: "none",
                  border: "1px solid #b7c9d8",
                  color: "#0d5fa8",
                  fontFamily: "var(--font-jost)",
                  fontSize: "14px",
                  fontWeight: 500,
                  background: "#ffffff",
                }}
              >
                Назад към продуктите
              </Link>
            </div>
          </div>
        </div>
      </section>
    </>
  );
};

export default ProductShow;
