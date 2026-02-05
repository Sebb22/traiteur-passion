import "../scss/main.scss";
import { initReveal } from "./home/reveal";
import { initHeaderScroll } from "./partials/header/headerScroll";
import { initHeaderToggle } from "./partials/header/headerToggle";
import { initVideoHero } from "./home/videoHero";
import { initAboutSlider } from "./about/initAboutSlider";

import { enableWheelRedirect } from "./generic/enableWheelRedirect";
document.addEventListener("DOMContentLoaded", () => {
  initReveal();
  //initHeaderScroll();
  initHeaderToggle();
  initVideoHero();
  initAboutSlider();

  enableWheelRedirect();
  console.log("Traiteur Passion ready 🔥");
});
