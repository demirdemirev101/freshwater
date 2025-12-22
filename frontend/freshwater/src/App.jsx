import Header from "./components/layout/Header";
import Contacts from "./pages/Contacts";
import Footer from "./components/layout/Footer";
import About from "./pages/About";

function App() {
  return (
    <>
      <Header />

      <main className="app-content">
        <About />
   
      </main>

      <Footer />
    </>
  );
}

export default App;
