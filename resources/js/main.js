import "../scss/main.scss";
import { initReveal } from "./home/reveal";
import { initHeaderScroll } from "./partials/header/headerScroll";
import { initHeaderToggle } from "./partials/header/headerToggle";
import { initVideoHero } from "./home/videoHero";
import { initAboutSlider } from "./about/initAboutSlider";
import { initContactForm } from "./contact/contactForm";
import { initMenuTabs } from "./menu/menuTabs";
import { initMenuOrder } from "./menu/menuOrder";
import { initAdminCatalog } from "./admin/catalog";
import { initAdminBlog } from "./admin/blog";

import { enableWheelRedirect } from "./generic/enableWheelRedirect";
document.addEventListener("DOMContentLoaded", () => {
    initReveal();
    //initHeaderScroll();
    initHeaderToggle();
    initVideoHero();
    initAboutSlider();
    initContactForm();
    initMenuTabs();
    initMenuOrder();
    initAdminCatalog();
    initAdminBlog();

    enableWheelRedirect();
    console.log("Traiteur Passion ready 🔥");
});
