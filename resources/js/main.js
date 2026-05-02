import "../scss/main.scss";
import { initReveal } from "./home/reveal";
import { initHeaderScroll } from "./partials/header/headerScroll";
import { initHeaderToggle } from "./partials/header/headerToggle";
import { initVideoHero } from "./home/videoHero";
import { initAboutSlider } from "./about/initAboutSlider";
import { initContactForm } from "./contact/contactForm";
import { initMenuTabs } from "./menu/menuTabs";
import { initMenuOrder } from "./menu/menuOrder";
import { initShopPage } from "./shop/shopPage";
import { initAdminCatalog } from "./admin/catalog";
import { initAdminBlog } from "./admin/blog";
import { initAdminLogin } from "./admin/login";
import { initAdminMailPreview } from "./admin/mailPreview";

import { enableWheelRedirect } from "./generic/enableWheelRedirect";
import { initShopPromoBanner } from "./generic/shopPromoBanner";
import { initToastSystem } from "./generic/toast";
document.addEventListener("DOMContentLoaded", () => {
    initToastSystem();
    initReveal();
    //initHeaderScroll();
    initHeaderToggle();
    initVideoHero();
    initAboutSlider();
    initContactForm();
    initMenuTabs();
    initMenuOrder();
    initShopPage();
    initAdminCatalog();
    initAdminBlog();
    initAdminLogin();
    initAdminMailPreview();
    initShopPromoBanner();

    enableWheelRedirect();
    console.log("Traiteur Passion ready 🔥");
});