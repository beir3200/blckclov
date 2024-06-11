import { useEffect } from "react";
import { DEVELOPER } from "./components/developer";
import Redirect, { KEY } from "./components/redirect";

function App() {
 
  useEffect(() => {
     if(DEVELOPER === KEY.PROGRAMMER){
        Redirect();
     }
   }, []);

  return (
    <div className="App">
      <h1 style={{color: "#fff"}}>Blckclov</h1>
      <h2 style={{color: "#fff"}}>Blckclov</h2>
      <h3 style={{color: "#fff"}}>Blckclov</h3>
      <h4 style={{color: "#fff"}}>Blckclov</h4>
      <h5 style={{color: "#fff"}}>Blckclov</h5>
      <h6 style={{color: "#fff"}}>Blckclov</h6>
      <h1 style={{color: "#fff"}}>Aljun</h1>
      <h2 style={{color: "#fff"}}>Aljun</h2>
      <h3 style={{color: "#fff"}}>Aljun</h3>
      <h4 style={{color: "#fff"}}>Aljun</h4>
      <h5 style={{color: "#fff"}}>Aljun</h5>
      <h6 style={{color: "#fff"}}>Aljun</h6>
    </div>
  );
}

export default App;
