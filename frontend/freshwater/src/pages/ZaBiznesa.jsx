import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";

const API_BASE_URL =
  process.env.REACT_APP_API_URL || "http://192.168.1.208:8000";

const BGN_RATE = 1.95583;

const formatMoney = (value) => {
  const num = Number(value);

  if (!Number.isFinite(num)) {
    return null;
  }

  return num.toFixed(2);
};

const normalizeCategoryText = (value) =>
  String(value || "")
    .toLowerCase()
    .trim();

const isBusinessCategoryProduct = (product) => {
  const categoryValues = [];

  if (product?.category?.name) categoryValues.push(product.category.name);
  if (product?.category_name) categoryValues.push(product.category_name);
  if (typeof product?.category === "string") categoryValues.push(product.category);

  if (Array.isArray(product?.categories)) {
    product.categories.forEach((categoryItem) => {
      if (typeof categoryItem === "string") {
        categoryValues.push(categoryItem);
      } else if (categoryItem?.name) {
        categoryValues.push(categoryItem.name);
      }
    });
  }

  const normalized = categoryValues.map(normalizeCategoryText);

  return normalized.some(
    (name) => name.includes("за бизнеса") || name.includes("za biznes")
  );
};

const ZaBiznesa = () => {
  const [products, setProducts] = useState([]);
  const [viewMode, setViewMode] = useState("grid");

  const endpoint = useMemo(() => `${API_BASE_URL}/api/products`, []);

  useEffect(() => {
    const controller = new AbortController();

    const loadProducts = async () => {
      try {
        const response = await fetch(endpoint, {
          headers: { Accept: "application/json" },
          signal: controller.signal,
        });

        const payload = await response.json();
        setProducts(payload.data || []);
      } catch (err) {
        console.log(err);
      }
    };

    loadProducts();

    return () => controller.abort();
  }, [endpoint]);

  const filteredProducts = useMemo(
    () => products.filter((product) => isBusinessCategoryProduct(product)),
    [products]
  );

  return (
    <>
      <section
        style={{
          background: "linear-gradient(100deg, #28b28f 0%, #1a4f8f 100%)",
          borderBottom: "8px solid #ffffff",
          marginTop: "112px",
          padding: "clamp(26px, 5vw, 25px) clamp(16px, 7vw, 80px)",
        }}
      >
        <div style={{ maxWidth: "1400px", margin: "0 auto" }}>
          <h1
            style={{
              margin: 0,
              color: "#f4f7f8",
              fontFamily: "Jost", 
              fontFamily: "sans-serif",
              fontWeight: 700,
              fontSize: "clamp(44px, 7vw, 14px)",
              letterSpacing: "1px",
              textTransform: "uppercase",
            }}
          >
            За бизнеса
          </h1>

          <nav
            aria-label="Breadcrumb"
            style={{
              marginTop: "clamp(18px, 2.5vw, 28px)",
              display: "flex",
              alignItems: "center",
              gap: "clamp(12px, 1.5vw, 28px)",
              fontFamily: "Jost",
              fontSize: "clamp(15px, 2vw, 18px)",
              fontWeight: 400,
              color: "#e7f1ef",
            }}
          >
            <Link
              to="/"
              style={{
                color: "#e7f1ef",
                textDecoration: "none",
              }}
            >
              Начало
            </Link>
            <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
            <span>Продукти</span>
            <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
            <span>За бизнеса</span>
          </nav>
        </div>
      </section>

      <section
        style={{
          padding: "clamp(40px, 7vw, 80px) clamp(16px, 7vw, 80px)",
          background: "#ffffff",
        }}
      >
        <div
          style={{
            maxWidth: "1400px",
            margin: "0 auto clamp(26px, 4vw, 40px)",
            display: "flex",
            alignItems: "center",
            gap: "10px",
          }}
        >
          <button
            type="button"
            aria-label="Покажи като мрежа"
            onClick={() => setViewMode("grid")}
            style={{
              width: "28px",
              height: "28px",
              border: "none",
              background: "transparent",
              cursor: "pointer",
              display: "grid",
              placeItems: "center",
              opacity: viewMode === "grid" ? 1 : 0.5,
            }}
          >
            <span
              style={{
                display: "grid",
                gridTemplateColumns: "repeat(3, 5px)",
                gap: "2px",
              }}
            >
              {Array.from({ length: 9 }).map((_, index) => (
                <span
                  key={index}
                  style={{
                    width: "5px",
                    height: "5px",
                    background: "#22344b",
                    borderRadius: "1px",
                  }}
                />
              ))}
            </span>
          </button>

          <button
            type="button"
            aria-label="Покажи като списък"
            onClick={() => setViewMode("list")}
            style={{
              width: "28px",
              height: "28px",
              border: "none",
              background: "transparent",
              cursor: "pointer",
              display: "grid",
              placeItems: "center",
              opacity: viewMode === "list" ? 1 : 0.5,
            }}
          >
            <span style={{ display: "grid", gap: "3px" }}>
              {Array.from({ length: 3 }).map((_, index) => (
                <span
                  key={index}
                  style={{
                    display: "flex",
                    alignItems: "center",
                    gap: "3px",
                  }}
                >
                  <span
                    style={{
                      width: "5px",
                      height: "5px",
                      background: "#22344b",
                      borderRadius: "1px",
                    }}
                  />
                  <span
                    style={{
                      width: "11px",
                      height: "2px",
                      background: "#22344b",
                      borderRadius: "1px",
                    }}
                  />
                </span>
              ))}
            </span>
          </button>
        </div>

        <div
          style={{
            display: "grid",
            gridTemplateColumns:
              viewMode === "grid" ? "repeat(auto-fit, minmax(300px, 1fr))" : "1fr",
            gap: viewMode === "grid" ? "clamp(36px, 6vw, 90px)" : "clamp(28px, 4vw, 48px)",
            justifyItems: viewMode === "grid" ? "center" : "stretch",
            maxWidth: "1400px",
            margin: "0 auto",
          }}
        >
          {filteredProducts.map((p, index) => {
            const image = p.images?.[0]?.url;
            const basePriceNum = Number(p.price);
            const salePriceNum = Number(p.sale_price);
            const hasSalePrice =
              Number.isFinite(salePriceNum) &&
              salePriceNum > 0 &&
              (!Number.isFinite(basePriceNum) || salePriceNum < basePriceNum);
            const basePrice = formatMoney(basePriceNum);
            const salePrice = hasSalePrice ? formatMoney(salePriceNum) : null;
            const salePriceBgn = hasSalePrice
              ? formatMoney(salePriceNum * BGN_RATE)
              : null;
            const descriptionHtml = p.short_description || p.description || "";

            return (
              <article
                key={p.id}
                style={{
                  textAlign: viewMode === "grid" ? "center" : "left",
                  width: "100%",
                  maxWidth: viewMode === "grid" ? "640px" : "none",
                  display: "grid",
                  gridTemplateColumns:
                    viewMode === "grid"
                      ? "1fr"
                      : "minmax(260px, clamp(260px, 34vw, 420px)) minmax(0, 1fr)",
                  alignItems: "center",
                  gap: viewMode === "grid" ? 0 : "clamp(22px, 4vw, 56px)",
                  borderTop:
                    viewMode === "list" && index > 0 ? "1px solid #d7dbe0" : "none",
                  paddingTop: viewMode === "list" && index > 0 ? "clamp(18px, 3vw, 30px)" : 0,
                }}
              >
                <div
                  style={{
                    width: "min(60vw, 410px)",
                    height: "min(60vw, 410px)",
                    borderRadius: "50%",
                    background: "#9fd8ce",
                    display: "flex",
                    alignItems: "center",
                    justifyContent: "center",
                    margin: viewMode === "grid" ? "0 auto" : 0,
                  }}
                >
                  <div
                    style={{
                      width: "min(54vw, 380px)",
                      height: "min(54vw, 380px)",
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
                        alt={p.name}
                        style={{
                          width: "88%",
                          objectFit: "contain",
                          transform: "translateY(8px)",
                        }}
                      />
                    )}
                  </div>
                </div>

                <div
                  style={{
                    display: "block",
                    textAlign: viewMode === "grid" ? "center" : "left",
                  }}
                >
                  <div
                    style={{
                      marginTop: viewMode === "grid" ? "clamp(18px, 3vw, 10px)" : 0,
                      fontFamily: "Jost",
                      fontFamily: "sans-serif",
                      fontWeight: 700,
                      fontSize:
                        viewMode === "grid"
                          ? "clamp(18px, 5.5vw, 15px)"
                          : "clamp(24px, 3vw, 20px)",
                      lineHeight: 1,
                      letterSpacing: "0.4px",
                      display: viewMode === "list" ? "flex" : "block",
                      alignItems: viewMode === "list" ? "center" : "initial",
                      gap: viewMode === "list" ? "clamp(16px, 2vw, 34px)" : 0,
                      flexWrap: viewMode === "list" ? "wrap" : "nowrap",
                    }}
                  >
                    {salePrice ? (
                      <>
                        <span
                          style={{
                            textDecoration: "line-through",
                            color: "#7b7f86",
                            fontSize: viewMode === "list" ? "0.8em" : "0.96em",
                          }}
                        >
                          {basePrice} €
                        </span>
                        <span
                          style={{
                            color: "#149a84",
                          }}
                        >
                          {salePrice} €
                        </span>
                        {salePriceBgn && (
                          <span style={{ color: "#0c97c6" }}>/ {salePriceBgn} лв.</span>
                        )}
                      </>
                    ) : (
                      <span style={{ color: "#149a84" }}>{basePrice} €</span>
                    )}
                  </div>

                  <h3
                    style={{
                      marginTop: viewMode === "grid" ? "clamp(14px, 2.5vw, 10px)" : "14px",
                      marginBottom: 0,
                      fontFamily: "Jost",
                      fontFamily: "sans-serif",
                      fontWeight: 500,
                      fontSize:
                        viewMode === "grid"
                          ? "clamp(15px, 5.6vw, 12px)"
                          : "clamp(18px, 2.9vw, 16px)",
                      lineHeight: 1.12,
                      letterSpacing: "0.3px",
                      color: "#0d5fa8",
                      textTransform: "uppercase",
                    }}
                  >
                    {p.name}
                  </h3>

                  {viewMode === "list" && descriptionHtml && (
                    <div
                      className="za-doma-list-description"
                      style={{
                        marginTop: "14px",
                        marginBottom: 0,
                        fontFamily: "Jost",
                        fontFamily: "sans-serif",
                        fontSize: "clamp(12px, 1.35vw, 12.5px)",
                        lineHeight: 1.45,
                        color: "#45596f",
                      }}
                      dangerouslySetInnerHTML={{ __html: descriptionHtml }}
                    />
                  )}
                </div>
              </article>
            );
          })}
        </div>
      </section>
    </>
  );
};

export default ZaBiznesa;
