import { Suspense, lazy, useEffect } from "react";
import Header from "./components/layout/Header";
import Footer from "./components/layout/Footer";
import { Routes, Route, useLocation } from "react-router-dom";

const Home = lazy(() => import("./pages/Home"));
const About = lazy(() => import("./pages/About"));
const Contacts = lazy(() => import("./pages/Contacts"));
const ZaDoma = lazy(() => import("./pages/ZaDoma"));
const ZaBiznesa = lazy(() => import("./pages/ZaBiznesa"));
const IonizatorZaVoda = lazy(() => import("./pages/IonizatorZaVoda"));
const ProductShow = lazy(() => import("./pages/ProductShow"));
const Cart = lazy(() => import("./pages/Cart"));

function ScrollToTop() {
  const { pathname } = useLocation();

  useEffect(() => {
    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
  }, [pathname]);

  return null;
}

function App() {
  return (
    <>
      <Header />

      <main className="app-content">
        <ScrollToTop />

        <Suspense fallback={<div className="route-loading">Loading...</div>}>
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/ionizator-za-voda" element={<IonizatorZaVoda />} />
            <Route path="/produkti" element={<ZaDoma pageMode="all" />} />
            <Route path="/produkti/:productId" element={<ProductShow />} />
            <Route path="/cart" element={<Cart />} />
            <Route path="/za-doma" element={<ZaDoma />} />
            <Route path="/za-biznesa" element={<ZaBiznesa />} />
            <Route path="/about" element={<About />} />
            <Route path="/contacts" element={<Contacts />} />
          </Routes>
        </Suspense>
      </main>

      <Footer />
    </>
  );
}

export default App;

