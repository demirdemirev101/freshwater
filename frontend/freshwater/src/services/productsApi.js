const API_BASE_URL = process.env.REACT_APP_API_URL || "http://192.168.1.208";
const PRODUCTS_ENDPOINT = `${API_BASE_URL}/api/products`;
const CACHE_TTL_MS = 5 * 60 * 1000;
const SESSION_CACHE_KEY = "freshwater_products_cache_v1";

let inMemoryCache = {
  data: null,
  timestamp: 0,
};
let inflightRequest = null;

const parseProductsPayload = (payload) => (Array.isArray(payload?.data) ? payload.data : []);

const readSessionCache = () => {
  if (typeof window === "undefined") return null;

  try {
    const raw = window.sessionStorage.getItem(SESSION_CACHE_KEY);
    if (!raw) return null;

    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed?.data) || typeof parsed?.timestamp !== "number") {
      return null;
    }

    if (Date.now() - parsed.timestamp > CACHE_TTL_MS) {
      window.sessionStorage.removeItem(SESSION_CACHE_KEY);
      return null;
    }

    return parsed;
  } catch {
    return null;
  }
};

const writeSessionCache = (cache) => {
  if (typeof window === "undefined") return;

  try {
    window.sessionStorage.setItem(SESSION_CACHE_KEY, JSON.stringify(cache));
  } catch {
    // Ignore storage failures (private mode, quota, etc.).
  }
};

const setCache = (data) => {
  const nextCache = {
    data,
    timestamp: Date.now(),
  };

  inMemoryCache = nextCache;
  writeSessionCache(nextCache);

  return data;
};

export const getAllProducts = async ({ forceRefresh = false } = {}) => {
  const now = Date.now();

  if (
    !forceRefresh &&
    Array.isArray(inMemoryCache.data) &&
    now - inMemoryCache.timestamp < CACHE_TTL_MS
  ) {
    return inMemoryCache.data;
  }

  if (!forceRefresh) {
    const sessionCache = readSessionCache();
    if (sessionCache) {
      inMemoryCache = sessionCache;
      return sessionCache.data;
    }
  }

  if (!forceRefresh && inflightRequest) {
    return inflightRequest;
  }

  inflightRequest = fetch(PRODUCTS_ENDPOINT, {
    headers: { Accept: "application/json" },
  })
    .then(async (response) => {
      if (!response.ok) {
        throw new Error(`Products request failed with status ${response.status}`);
      }

      const payload = await response.json();
      return setCache(parseProductsPayload(payload));
    })
    .finally(() => {
      inflightRequest = null;
    });

  return inflightRequest;
};

export const getProductById = async (productId) => {
  const normalizedId = String(productId ?? "");
  const products = await getAllProducts();

  return (
    products.find((item) => String(item?.id ?? "") === normalizedId) || null
  );
};
