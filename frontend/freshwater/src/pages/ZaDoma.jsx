import { useEffect, useMemo, useState } from "react";
import { Link, useLocation } from "react-router-dom";

const API_BASE_URL =
  process.env.REACT_APP_API_URL || "http://192.168.1.208";

const BGN_RATE = 1.95583;
const PRODUCTS_PER_PAGE = 12;

const formatMoney = (value) => {
  const num = Number(value);

  if (!Number.isFinite(num)) {
    return null;
  }

  return num.toFixed(2);
};

const breadcrumbLinkStyle = {
  color: "#e7f1ef",
  textDecoration: "none",
  transition: "color 0.2s ease, opacity 0.2s ease",
};

const handleBreadcrumbLinkMouseEnter = (event) => {
  event.currentTarget.style.color = "#0e8455";
  event.currentTarget.style.opacity = "1";
};

const handleBreadcrumbLinkMouseLeave = (event) => {
  event.currentTarget.style.color = "#e7f1ef";
  event.currentTarget.style.opacity = "1";
};

const normalizeCategoryText = (value) =>
  String(value || "")
    .toLowerCase()
    .trim();

const getProductCategoryPriority = (product) => {
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

  if (normalized.some((name) => name.includes("за дома") || name.includes("za doma"))) {
    return 0;
  }

  if (
    normalized.some((name) => name.includes("за бизнеса") || name.includes("za biznes"))
  ) {
    return 1;
  }

  return 2;
};

const isHomeCategoryProduct = (product) => getProductCategoryPriority(product) === 0;

const getHomeCategoryValues = (product) => {
  const values = [];

  if (product?.category?.name) values.push(product.category.name);
  if (product?.category_name) values.push(product.category_name);
  if (typeof product?.category === "string") values.push(product.category);

  if (Array.isArray(product?.categories)) {
    product.categories.forEach((categoryItem) => {
      if (typeof categoryItem === "string") {
        values.push(categoryItem);
      } else if (categoryItem?.name) {
        values.push(categoryItem.name);
      }
    });
  }

  return values.map(normalizeCategoryText);
};

const getHomeProductSearchValues = (product) => {
  const values = [...getHomeCategoryValues(product)];

  if (product?.name) values.push(product.name);

  return values.map(normalizeCategoryText);
};

const HOME_FILTER_KEYWORDS = {
  "water-systems": ["системи за вода", "sistemi za voda", "water system", "water systems"],
  accessories: ["аксесоар", "aksesoar", "accessor"],
  "filters-consumables": [
    "филтри и консумативи",
    "филтри",
    "консуматив",
    "filtri",
    "konsumativi",
    "consumable",
    "filter",
  ],
  bottles: ["бутилки", "бутилка", "butilki", "bottle"],
  mixers: ["смесители", "смесител", "smesitel", "smesiteli", "mixer", "faucet"],
  "hydrogen-water": ["водородна вода", "vodorodna voda", "hydrogen water", "hydrogen"],
};

const HOME_FILTER_LABELS = {
  "water-systems": "Системи за вода",
  accessories: "Аксесоари за дома",
  "filters-consumables": "Филтри и консумативи",
  bottles: "Бутилки",
  mixers: "Смесители",
  "hydrogen-water": "Водородна вода",
};

const HOME_FILTER_PARENT = {
  "filters-consumables": "accessories",
  bottles: "accessories",
  mixers: "accessories",
};

const matchesHomeFilter = (product, filterKey) => {
  const keywords = HOME_FILTER_KEYWORDS[filterKey];

  if (!keywords?.length) {
    return true;
  }

  const categoryValues = getHomeCategoryValues(product);
  const values = categoryValues.length ? categoryValues : getHomeProductSearchValues(product);
  return values.some((value) => keywords.some((keyword) => value.includes(keyword)));
};

const ZaDoma = ({ pageMode = "home" }) => {
  const [products, setProducts] = useState([]);
  const [viewMode, setViewMode] = useState("grid");
  const [currentPage, setCurrentPage] = useState(1);
  const location = useLocation();
  const isAllProductsPage = pageMode === "all";
  const isIonizerPage = pageMode === "ionizer";
  const homeFilter = useMemo(
    () =>
      isAllProductsPage || isIonizerPage
        ? null
        : new URLSearchParams(location.search).get("homeFilter"),
    [isAllProductsPage, isIonizerPage, location.search]
  );
  const activeHomeFilter = isIonizerPage ? "water-systems" : homeFilter;
  const selectedFilterLabel = homeFilter ? HOME_FILTER_LABELS[homeFilter] || null : null;
  const parentFilterKey = homeFilter ? HOME_FILTER_PARENT[homeFilter] || null : null;
  const parentFilterLabel = parentFilterKey ? HOME_FILTER_LABELS[parentFilterKey] || null : null;

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

  const sortedProducts = useMemo(
    () =>
      [...products]
        .map((product, index) => ({ product, index }))
        .sort((a, b) => {
          const priorityDiff =
            getProductCategoryPriority(a.product) - getProductCategoryPriority(b.product);

          if (priorityDiff !== 0) {
            return priorityDiff;
          }

          return a.index - b.index;
        })
        .map(({ product }) => product),
    [products]
  );

  const filteredProducts = useMemo(() => {
    if (isAllProductsPage) {
      return sortedProducts;
    }

    const homeProducts = sortedProducts.filter((product) => isHomeCategoryProduct(product));

    if (activeHomeFilter) {
      return homeProducts.filter((product) => matchesHomeFilter(product, activeHomeFilter));
    }

    return homeProducts;
  }, [activeHomeFilter, isAllProductsPage, sortedProducts]);

  const totalPages = useMemo(
    () => Math.max(1, Math.ceil(filteredProducts.length / PRODUCTS_PER_PAGE)),
    [filteredProducts.length]
  );

  const paginatedProducts = useMemo(() => {
    const startIndex = (currentPage - 1) * PRODUCTS_PER_PAGE;
    return filteredProducts.slice(startIndex, startIndex + PRODUCTS_PER_PAGE);
  }, [currentPage, filteredProducts]);

  useEffect(() => {
    setCurrentPage(1);
  }, [activeHomeFilter, isAllProductsPage, isIonizerPage, viewMode]);

  useEffect(() => {
    if (currentPage > totalPages) {
      setCurrentPage(totalPages);
    }
  }, [currentPage, totalPages]);

  return (
    <>
      <section
        style={{
          background: "linear-gradient(100deg, #28b28f 0%, #1a4f8f 100%)",
          borderBottom: "8px solid #ffffff",
          marginTop: "112px",
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
              fontSize: "clamp(28px, 3.6vw, 50px)",
              lineHeight: 1.02,
              letterSpacing: "0.25px",
              textTransform: "uppercase",
            }}
          >
            {isAllProductsPage
              ? "Продукти"
              : isIonizerPage
                ? "Йонизатор за вода"
                : selectedFilterLabel || "За дома"}
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
            <Link
              to="/"
              style={breadcrumbLinkStyle}
              onMouseEnter={handleBreadcrumbLinkMouseEnter}
              onMouseLeave={handleBreadcrumbLinkMouseLeave}
            >
              Начало
            </Link>
            <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
            {isAllProductsPage ? (
              <span>Продукти</span>
            ) : isIonizerPage ? (
              <>
                <Link
                  to="/produkti"
                  style={breadcrumbLinkStyle}
                  onMouseEnter={handleBreadcrumbLinkMouseEnter}
                  onMouseLeave={handleBreadcrumbLinkMouseLeave}
                >
                  Продукти
                </Link>
                <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
                <span>Йонизатор за вода</span>
              </>
            ) : (
              <>
                <Link
                  to="/produkti"
                  style={breadcrumbLinkStyle}
                  onMouseEnter={handleBreadcrumbLinkMouseEnter}
                  onMouseLeave={handleBreadcrumbLinkMouseLeave}
                >
                  Продукти
                </Link>
                <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
                {selectedFilterLabel ? (
                  <>
                    <Link
                      to="/za-doma"
                      style={breadcrumbLinkStyle}
                      onMouseEnter={handleBreadcrumbLinkMouseEnter}
                      onMouseLeave={handleBreadcrumbLinkMouseLeave}
                    >
                      За дома
                    </Link>
                    <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
                    {parentFilterLabel && homeFilter !== parentFilterKey && (
                      <>
                        <Link
                          to={`/za-doma?homeFilter=${parentFilterKey}`}
                          style={breadcrumbLinkStyle}
                          onMouseEnter={handleBreadcrumbLinkMouseEnter}
                          onMouseLeave={handleBreadcrumbLinkMouseLeave}
                        >
                          {parentFilterLabel}
                        </Link>
                        <span style={{ color: "rgba(255, 255, 255, 0.35)" }}>/</span>
                      </>
                    )}
                    <span>{selectedFilterLabel}</span>
                  </>
                ) : (
                  <span>За дома</span>
                )}
              </>
            )}
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
            gap: "14px",
          }}
        >
          <button
            type="button"
            aria-label="Покажи като мрежа"
            onClick={() => setViewMode("grid")}
            style={{
              width: "28px",
              height: "28px",
              padding: 0,
              border: "none",
              background: "transparent",
              cursor: "pointer",
              display: "grid",
              placeItems: "center",
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
                    background: viewMode === "grid" ? "#1f2f43" : "#7d8188",
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
              padding: 0,
              border: "none",
              background: "transparent",
              cursor: "pointer",
              display: "grid",
              placeItems: "center",
            }}
          >
            <span style={{ display: "grid", gap: "2px" }}>
              {Array.from({ length: 3 }).map((_, index) => (
                <span
                  key={index}
                  style={{
                    display: "flex",
                    alignItems: "center",
                    gap: "2px",
                  }}
                >
                  <span
                    style={{
                      width: "5px",
                      height: "5px",
                      background: viewMode === "list" ? "#1f2f43" : "#7d8188",
                      borderRadius: "1px",
                    }}
                  />
                  <span
                    style={{
                      width: "11px",
                      height: "2px",
                      background: viewMode === "list" ? "#1f2f43" : "#7d8188",
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
          {paginatedProducts.map((p, index) => {
            const image = p.images?.[0]?.url;
            const basePriceNum = Number(p.price);
            const salePriceNum = Number(p.sale_price);
            const hasBasePrice = Number.isFinite(basePriceNum) && basePriceNum > 0;
            const hasSalePrice =
              Number.isFinite(salePriceNum) &&
              salePriceNum > 0 &&
              (!hasBasePrice || salePriceNum < basePriceNum);
            const basePrice = hasBasePrice ? formatMoney(basePriceNum) : null;
            const basePriceBgn = hasBasePrice
              ? formatMoney(basePriceNum * BGN_RATE)
              : null;
            const salePrice = hasSalePrice ? formatMoney(salePriceNum) : null;
            const salePriceBgn = hasSalePrice
              ? formatMoney(salePriceNum * BGN_RATE)
              : null;
            const descriptionHtml = p.short_description || p.description || "";
            const productLink = `/produkti/${p.id}`;
            const productLinkState = { from: `${location.pathname}${location.search}` };

            return (
              <article
                key={p.id}
                style={{
                  textAlign: viewMode === "grid" ? "center" : "left",
                  width: "100%",
                  minWidth: 0,
                  maxWidth: viewMode === "grid" ? "640px" : "none",
                  display: "grid",
                  gridTemplateColumns:
                    viewMode === "grid"
                      ? "1fr"
                      : "minmax(260px, clamp(260px, 34vw, 420px)) minmax(0, 1fr)",
                  alignItems: viewMode === "list" ? "start" : "center",
                  gap: viewMode === "grid" ? 0 : "clamp(22px, 4vw, 56px)",
                  borderTop:
                    viewMode === "list" && index > 0 ? "1px solid #d7dbe0" : "none",
                  paddingTop: viewMode === "list" && index > 0 ? "clamp(18px, 3vw, 30px)" : 0,
                }}
              >
                <Link
                  to={productLink}
                  state={productLinkState}
                  style={{
                    display: "block",
                    textDecoration: "none",
                    color: "inherit",
                  }}
                >
                  <div
                    style={{
                      width: "min(60vw, 416px)",
                      height: "min(60vw, 415px)",
                      borderRadius: "50%",
                      background: "linear-gradient(100deg, #69cbb2 0%, #124480aa 100%)",
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
                </Link>

                <div
                  style={{
                    display: "block",
                    textAlign: "left",
                    paddingTop: viewMode === "list" ? "clamp(2px, 0.5vw, 8px)" : 0,
                    maxWidth: viewMode === "grid" ? "min(100%, 430px)" : "none",
                    margin: viewMode === "grid" ? "0 auto" : 0,
                  }}
                >
                  <div
                    style={{
                      marginTop: viewMode === "grid" ? "clamp(8px, 1vw, 14px)" : 0,
                      fontFamily: "var(--font-jost)",
                      fontWeight: 700,
                      fontSize:
                        viewMode === "grid"
                          ? "clamp(14px, 1.15vw, 22px)"
                          : "clamp(17px, 1.45vw, 24px)",
                      lineHeight: viewMode === "grid" ? 1.08 : 1.1,
                      letterSpacing: viewMode === "grid" ? "0.2px" : "0.1px",
                      display: "flex",
                      alignItems: "baseline",
                      gap:
                        viewMode === "grid"
                          ? "clamp(10px, 0.9vw, 16px)"
                          : "clamp(8px, 0.9vw, 14px)",
                      flexWrap: "wrap",
                    }}
                  >
                    {salePrice ? (
                      <>
                        {basePrice && (
                          <span
                            style={{
                              textDecoration: "line-through",
                              color: "#4b7fbe",
                              fontSize: viewMode === "grid" ? "0.9em" : "0.82em",
                              textDecorationColor: "#4b7fbe",
                              textDecorationThickness: viewMode === "grid" ? "1.6px" : "1.5px",
                            }}
                          >
                            {basePrice} {"\u20ac"}
                          </span>
                        )}
                        <span
                          style={{
                            color: "#1f9e7f",
                          }}
                        >
                          {salePrice} {"\u20ac"}
                        </span>
                        {salePriceBgn && (
                          <span style={{ color: "#139cc8" }}>
                            / {salePriceBgn} лв.
                          </span>
                        )}
                      </>
                    ) : hasBasePrice ? (
                      <>
                        <span style={{ color: "#1f9e7f" }}>
                          {basePrice} {"\u20ac"}
                        </span>
                        {basePriceBgn && (
                          <span style={{ color: "#139cc8" }}>
                            / {basePriceBgn} лв.
                          </span>
                        )}
                      </>
                    ) : (
                      <span style={{ color: "#139cc8", fontWeight: 600 }}>По запитване</span>
                    )}
                  </div>

                  <h3
                    style={{
                      marginTop: viewMode === "grid" ? "clamp(10px, 1.1vw, 16px)" : "10px",
                      marginBottom: 0,
                      fontFamily: "var(--font-jost)",
                      fontWeight: 400,
                      fontSize:
                        viewMode === "grid"
                          ? "clamp(15px, 1.15vw, 24px)"
                          : "clamp(16px, 1.1vw, 22px)",
                      lineHeight: viewMode === "grid" ? 1.16 : 1.2,
                      letterSpacing: viewMode === "grid" ? "0.35px" : "0.35px",
                      overflowWrap: "anywhere",
                      wordBreak: "break-word",
                      color: "#0d5fa8",
                      textTransform: "uppercase",
                    }}
                  >
                    <Link
                      to={productLink}
                      state={productLinkState}
                      style={{ color: "inherit", textDecoration: "none" }}
                    >
                      {p.name}
                    </Link>
                  </h3>

                  {viewMode === "list" && descriptionHtml && (
                    <div
                      className="za-doma-list-description"
                      style={{
                        marginTop: "clamp(12px, 1.2vw, 18px)",
                        marginBottom: 0,
                        fontFamily: "var(--font-jost)",
                        fontSize: "clamp(13px, 0.9vw, 15px)",
                        lineHeight: 1.45,
                        color: "#2a4560",
                      }}
                      dangerouslySetInnerHTML={{ __html: descriptionHtml }}
                    />
                  )}
                </div>
              </article>
            );
          })}
        </div>

        {totalPages > 1 && (
          <div
            style={{
              maxWidth: "1400px",
              margin: "clamp(28px, 4vw, 42px) auto 0",
              display: "flex",
              alignItems: "center",
              justifyContent: "center",
              gap: "8px",
              flexWrap: "wrap",
            }}
          >
            <button
              type="button"
              onClick={() => setCurrentPage((prev) => Math.max(1, prev - 1))}
              disabled={currentPage === 1}
              style={{
                minWidth: "88px",
                height: "34px",
                border: "1px solid #b7c9d8",
                background: currentPage === 1 ? "#f1f5f8" : "#ffffff",
                color: currentPage === 1 ? "#8fa4b7" : "#0d5fa8",
                borderRadius: "18px",
                fontFamily: "var(--font-jost)",
                fontSize: "14px",
                cursor: currentPage === 1 ? "default" : "pointer",
              }}
            >
              Назад
            </button>

            {Array.from({ length: totalPages }).map((_, idx) => {
              const page = idx + 1;
              const isActive = page === currentPage;

              return (
                <button
                  key={page}
                  type="button"
                  onClick={() => setCurrentPage(page)}
                  style={{
                    width: "34px",
                    height: "34px",
                    border: isActive ? "1px solid #0d5fa8" : "1px solid #b7c9d8",
                    background: isActive ? "#0d5fa8" : "#ffffff",
                    color: isActive ? "#ffffff" : "#0d5fa8",
                    borderRadius: "50%",
                    fontFamily: "var(--font-jost)",
                    fontSize: "14px",
                    cursor: "pointer",
                  }}
                >
                  {page}
                </button>
              );
            })}

            <button
              type="button"
              onClick={() => setCurrentPage((prev) => Math.min(totalPages, prev + 1))}
              disabled={currentPage === totalPages}
              style={{
                minWidth: "88px",
                height: "34px",
                border: "1px solid #b7c9d8",
                background: currentPage === totalPages ? "#f1f5f8" : "#ffffff",
                color: currentPage === totalPages ? "#8fa4b7" : "#0d5fa8",
                borderRadius: "18px",
                fontFamily: "var(--font-jost)",
                fontSize: "14px",
                cursor: currentPage === totalPages ? "default" : "pointer",
              }}
            >
              Напред
            </button>
          </div>
        )}
      </section>
    </>
  );
};

export default ZaDoma;
