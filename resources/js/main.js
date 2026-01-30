import "../scss/main.scss";
import { initReveal } from "./reveal";
import { initHeaderScroll } from "./partials/header/headerScroll";
import { initHeaderToggle } from "./partials/header/headerToggle";
import { initVideoHero } from "./videoHero";
import { initAboutSlider } from "./about/initAboutSlider";

document.addEventListener("DOMContentLoaded", () => {
  initReveal();
  //initHeaderScroll();
  initHeaderToggle();
  initVideoHero();
  initAboutSlider();
  console.log("Traiteur Passion ready 🔥");
});
