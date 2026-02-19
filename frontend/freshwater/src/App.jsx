import { useEffect } from "react";
import Header from "./components/layout/Header";
import Footer from "./components/layout/Footer";
import { Routes, Route, useLocation } from "react-router-dom";

import Home from "./pages/Home";
import About from "./pages/About";
import Contacts from "./pages/Contacts";
import ZaDoma from "./pages/ZaDoma";
import ZaBiznesa from "./pages/ZaBiznesa";

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

        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/za-doma" element={<ZaDoma />} />
          <Route path="/za-biznesa" element={<ZaBiznesa />} />
          <Route path="/about" element={<About />} />
          <Route path="/contacts" element={<Contacts />} />
        </Routes>
      </main>

      <Footer />
    </>
  );
}

export default App;
