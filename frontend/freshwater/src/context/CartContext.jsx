import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";

const CART_STORAGE_KEY = "freshwater_cart_v1";
const MAX_ITEM_QTY = 99;

const CartContext = createContext(null);

const toNumber = (value) => {
  const num = Number(value);
  return Number.isFinite(num) ? num : 0;
};

const clampQuantity = (value) => {
  const qty = Math.round(toNumber(value));

  if (!Number.isFinite(qty) || qty < 1) return 1;
  if (qty > MAX_ITEM_QTY) return MAX_ITEM_QTY;

  return qty;
};

const sanitizeString = (value, fallback = "") => {
  const next = String(value ?? "").trim();
  return next || fallback;
};

const getProductPrices = (product) => {
  const basePrice = toNumber(product?.price);
  const salePrice = toNumber(product?.sale_price);
  const hasBasePrice = basePrice > 0;
  const hasSalePrice = salePrice > 0 && (!hasBasePrice || salePrice < basePrice);
  const unitPrice = hasSalePrice ? salePrice : hasBasePrice ? basePrice : 0;
  const listPrice = hasBasePrice && basePrice > unitPrice ? basePrice : null;

  return { unitPrice, listPrice };
};

const createCartItemFromProduct = (product, quantity = 1) => {
  const id = sanitizeString(product?.id);

  if (!id) return null;

  const { unitPrice, listPrice } = getProductPrices(product);

  return {
    id,
    name: sanitizeString(product?.name, "Продукт"),
    image: sanitizeString(product?.images?.[0]?.url),
    unitPrice,
    listPrice,
    quantity: clampQuantity(quantity),
  };
};

const sanitizePersistedItem = (item) => {
  const id = sanitizeString(item?.id);
  if (!id) return null;

  return {
    id,
    name: sanitizeString(item?.name, "Продукт"),
    image: sanitizeString(item?.image),
    unitPrice: Math.max(0, toNumber(item?.unitPrice)),
    listPrice: item?.listPrice == null ? null : Math.max(0, toNumber(item?.listPrice)),
    quantity: clampQuantity(item?.quantity),
  };
};

const readInitialCart = () => {
  if (typeof window === "undefined") return [];

  try {
    const raw = window.localStorage.getItem(CART_STORAGE_KEY);
    if (!raw) return [];

    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];

    return parsed.map(sanitizePersistedItem).filter(Boolean);
  } catch {
    return [];
  }
};

export const CartProvider = ({ children }) => {
  const [items, setItems] = useState(() => readInitialCart());

  useEffect(() => {
    if (typeof window === "undefined") return;

    try {
      window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(items));
    } catch {
      // Ignore storage failures (private mode, quota, etc.).
    }
  }, [items]);

  const addToCart = useCallback((product, quantity = 1) => {
    const nextItem = createCartItemFromProduct(product, quantity);
    if (!nextItem) return;

    setItems((currentItems) => {
      const existingIndex = currentItems.findIndex((item) => item.id === nextItem.id);

      if (existingIndex === -1) {
        return [...currentItems, nextItem];
      }

      const mergedItems = [...currentItems];
      const existingItem = mergedItems[existingIndex];

      mergedItems[existingIndex] = {
        ...existingItem,
        ...nextItem,
        quantity: clampQuantity(existingItem.quantity + nextItem.quantity),
      };

      return mergedItems;
    });
  }, []);

  const setItemQuantity = useCallback((itemId, quantity) => {
    const normalizedId = sanitizeString(itemId);
    const qty = Math.round(toNumber(quantity));

    setItems((currentItems) =>
      currentItems.flatMap((item) => {
        if (item.id !== normalizedId) return [item];
        if (!Number.isFinite(qty) || qty < 1) return [];

        return [{ ...item, quantity: clampQuantity(qty) }];
      })
    );
  }, []);

  const incrementItem = useCallback((itemId) => {
    const normalizedId = sanitizeString(itemId);

    setItems((currentItems) =>
      currentItems.map((item) =>
        item.id === normalizedId
          ? { ...item, quantity: clampQuantity(item.quantity + 1) }
          : item
      )
    );
  }, []);

  const decrementItem = useCallback((itemId) => {
    const normalizedId = sanitizeString(itemId);

    setItems((currentItems) =>
      currentItems.flatMap((item) => {
        if (item.id !== normalizedId) return [item];
        if (item.quantity <= 1) return [];

        return [{ ...item, quantity: item.quantity - 1 }];
      })
    );
  }, []);

  const removeFromCart = useCallback((itemId) => {
    const normalizedId = sanitizeString(itemId);
    setItems((currentItems) => currentItems.filter((item) => item.id !== normalizedId));
  }, []);

  const clearCart = useCallback(() => {
    setItems([]);
  }, []);

  const value = useMemo(() => {
    const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
    const subtotal = items.reduce((sum, item) => sum + item.unitPrice * item.quantity, 0);

    return {
      items,
      totalItems,
      subtotal,
      addToCart,
      setItemQuantity,
      incrementItem,
      decrementItem,
      removeFromCart,
      clearCart,
    };
  }, [
    addToCart,
    clearCart,
    decrementItem,
    incrementItem,
    items,
    removeFromCart,
    setItemQuantity,
  ]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
};

export const useCart = () => {
  const context = useContext(CartContext);

  if (!context) {
    throw new Error("useCart must be used within a CartProvider");
  }

  return context;
};
