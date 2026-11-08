import { createSnowfall } from "../vendor/snowfall-effect";

const MOBILE_QUERY = "(max-width: 720px)";
const REDUCED_MOTION_QUERY = "(prefers-reduced-motion: reduce)";

function numberValue(value, fallback) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : fallback;
}

function configFromRoot(root, mobile = false) {
    const desktopCount = Math.max(5, numberValue(root.dataset.snowflakeCount, 35));
    return {
        snowflakeCount: mobile ? Math.min(15, Math.round(desktopCount * 0.4)) : desktopCount,
        color: root.dataset.color || "#ffffff",
        radius: [0.45, 1.45],
        opacity: [
            numberValue(root.dataset.opacityMin, 0.25),
            numberValue(root.dataset.opacityMax, 0.75),
        ],
        speed: [
            numberValue(root.dataset.speedMin, 0.35),
            numberValue(root.dataset.speedMax, 1.1),
        ],
        wind: [
            numberValue(root.dataset.windMin, -0.2),
            numberValue(root.dataset.windMax, 0.6),
        ],
        changeFrequency: 250,
        zIndex: 10,
    };
}

function isMobile() {
    return window.matchMedia(MOBILE_QUERY).matches;
}

function canRunOnMobile(root) {
    return root.dataset.mobileEnabled === "1";
}

function monitorPerformance(instance, stop) {
    let last = performance.now();
    let slowFrames = 0;
    let samples = 0;
    let frameId = null;

    const sample = (now) => {
        const elapsed = now - last;
        last = now;
        samples += 1;
        slowFrames = elapsed > 42 ? slowFrames + 1 : 0;
        if (slowFrames >= 6 || (samples >= 90 && slowFrames > 12)) {
            stop("performance");
            return;
        }
        frameId = window.requestAnimationFrame(sample);
    };

    frameId = window.requestAnimationFrame(sample);
    return () => {
        if (frameId) window.cancelAnimationFrame(frameId);
    };
}

function start(root, options = {}) {
    if (window.matchMedia(REDUCED_MOTION_QUERY).matches) return null;
    const mobile = isMobile();
    if (mobile && !canRunOnMobile(root) && !options.preview) return null;

    const config = configFromRoot(root, mobile);
    if (options.preview) {
        config.snowflakeCount = Math.min(35, numberValue(root.dataset.snowflakeCount, 35));
    }
    const instance = createSnowfall({
        ...config,
        zIndex: options.preview ? 1300 : 10,
        parent: options.parent || document.body,
    });

    let stopMonitor = () => {};
    const stop = () => {
        stopMonitor();
        instance.destroy();
    };
    if (mobile && !options.preview) stopMonitor = monitorPerformance(instance, stop);
    return { instance, stop };
}

export function initSeasonalSnowfall() {
    const root = document.querySelector("[data-seasonal-ambience]");
    if (!root) return;

    let active = start(root);
    const onVisibilityChange = () => {
        if (!active) return;
        if (document.hidden) active.instance.pause();
        else active.instance.play();
    };
    document.addEventListener("visibilitychange", onVisibilityChange);

    window.addEventListener("pagehide", () => {
        if (active) active.stop();
        document.removeEventListener("visibilitychange", onVisibilityChange);
        active = null;
    }, { once: true });
}

export function initSeasonalSnowfallPreview() {
    const root = document.querySelector("[data-seasonal-ambience-admin]");
    if (!root) return;
    const stage = root.querySelector("[data-seasonal-preview-stage]");
    const open = root.querySelector("[data-seasonal-preview]");
    const close = root.querySelector("[data-seasonal-preview-close]");
    if (!stage || !open || !close) return;

    let active = null;
    const readForm = () => {
        const value = (name, fallback) => root.querySelector(`[name="${name}"]`)?.value || fallback;
        root.dataset.snowflakeCount = value("snowflake_count", "35");
        root.dataset.color = value("color", "#ffffff");
        root.dataset.opacityMin = value("opacity_min", "0.25");
        root.dataset.opacityMax = value("opacity_max", "0.75");
        root.dataset.speedMin = value("speed_min", "0.35");
        root.dataset.speedMax = value("speed_max", "1.1");
        root.dataset.windMin = value("wind_min", "-0.2");
        root.dataset.windMax = value("wind_max", "0.6");
    };
    open.addEventListener("click", () => {
        if (active) active.stop();
        readForm();
        stage.hidden = false;
        close.hidden = false;
        active = start(root, { preview: true, parent: stage });
    });
    close.addEventListener("click", () => {
        if (active) active.stop();
        active = null;
        stage.hidden = true;
        close.hidden = true;
    });
}