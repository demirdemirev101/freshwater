import { Link } from "react-router-dom";
import { useCart } from "../context/CartContext";
import "../styles/cart.css";

const BGN_RATE = 1.95583;

const formatMoney = (value) => {
  const num = Number(value);
  if (!Number.isFinite(num)) return "0.00";

  return num.toFixed(2);
};

const Cart = () => {
  const {
    items,
    totalItems,
    subtotal,
    setItemQuantity,
    incrementItem,
    decrementItem,
    removeFromCart,
  } = useCart();

  const subtotalText = formatMoney(subtotal);
  const subtotalBgnText = formatMoney(subtotal * BGN_RATE);
  const handleQuantityInput = (itemId, rawValue) => {
    if (rawValue === "") return;

    const nextQty = Number(rawValue);
    if (!Number.isFinite(nextQty)) return;

    setItemQuantity(itemId, nextQty);
  };

  return (
    <>
      <section className="cart-hero">
        <div className="cart-hero__inner">
          <h1>КОЛИЧКА</h1>

          <nav className="cart-breadcrumb" aria-label="Breadcrumb">
            <Link to="/">Начало</Link>
            <span>/</span>
            <Link to="/produkti">Продукти</Link>
            <span>/</span>
            <span>Количка</span>
          </nav>
        </div>
      </section>

      <section className="cart-page">
        <div className="cart-page__container">
          {items.length === 0 ? (
            <div className="cart-empty">
              <h2>Количката е празна</h2>
              <p>Добави продукт от страницата на продуктите.</p>
              <Link to="/produkti" className="cart-empty__btn">
                Към продуктите
              </Link>
            </div>
          ) : (
            <>
              <div className="cart-list">
                {items.map((item) => {
                  const unitPriceEur = formatMoney(item.unitPrice);
                  const unitPriceBgn = formatMoney(item.unitPrice * BGN_RATE);
                  const lineTotal = item.unitPrice * item.quantity;
                  const lineTotalEur = formatMoney(lineTotal);
                  const lineTotalBgn = formatMoney(lineTotal * BGN_RATE);
                  const hasListPrice =
                    Number.isFinite(Number(item.listPrice)) &&
                    Number(item.listPrice) > Number(item.unitPrice);
                  const listPrice = hasListPrice ? formatMoney(item.listPrice) : null;

                  return (
                    <article className="cart-item" key={item.id}>
                      <button
                        type="button"
                        className="cart-remove"
                        aria-label={`Премахни ${item.name}`}
                        onClick={() => removeFromCart(item.id)}
                      >
                        ×
                      </button>

                      <Link to={`/produkti/${item.id}`} className="cart-product">
                        {item.image ? (
                          <img src={item.image} alt={item.name} loading="lazy" decoding="async" />
                        ) : (
                          <span className="cart-product__fallback">FW</span>
                        )}
                        <span className="cart-product__name">{item.name}</span>
                      </Link>

                      <div className="cart-price">
                        {listPrice && <span className="cart-price__old">{listPrice} €</span>}
                        <span className="cart-price__new">
                          {unitPriceEur} € <span>/ {unitPriceBgn} лв.</span>
                        </span>
                      </div>

                      <div className="cart-qty">
                        <button
                          type="button"
                          aria-label="Намали количество"
                          onClick={() => decrementItem(item.id)}
                        >
                          −
                        </button>
                        <input
                          type="number"
                          inputMode="numeric"
                          min="1"
                          max="99"
                          value={item.quantity}
                          onChange={(event) => handleQuantityInput(item.id, event.target.value)}
                        />
                        <button
                          type="button"
                          aria-label="Увеличи количество"
                          onClick={() => incrementItem(item.id)}
                        >
                          +
                        </button>
                      </div>

                      <div className="cart-line-total">
                        <span>{lineTotalEur} €</span>
                        <small>{lineTotalBgn} лв.</small>
                      </div>
                    </article>
                  );
                })}
              </div>

              <div className="cart-controls">
                <div className="cart-coupon">
                  <input type="text" placeholder="код за отстъпка" />
                  <button type="button">прилагане на промо код</button>
                </div>
              </div>

              <section className="cart-summary">
                <h2>Обща сума на количката</h2>

                <div className="cart-summary__row">
                  <span>Общо ({totalItems} бр.)</span>
                  <span>{subtotalText} €</span>
                </div>

                <div className="cart-summary__row">
                  <span>Доставка</span>
                  <span>Разходите за доставка ще бъдат изчислени при финализиране на поръчката.</span>
                </div>

                <div className="cart-summary__row cart-summary__row--total">
                  <span>Общо</span>
                  <span>
                    {subtotalText} €
                    <small>{subtotalBgnText} лв.</small>
                  </span>
                </div>

                <button type="button" className="cart-checkout-btn">
                  Приключване на поръчката
                </button>
              </section>
            </>
          )}
        </div>
      </section>
    </>
  );
};

export default Cart;
