/**
 * snowfall-effect.js
 * Simple canvas particle effect
 *
 * Local integration of https://github.com/waveswan/snowfall-effect
 * Author: Wave Swan
 * License: MIT
 */

const FRAME_TIME = 1000 / 60;

function randomRange(min, max) {
    return Math.random() * (max - min) + min;
}

function lerp(start, end, amount) {
    return (1 - amount) * start + amount * end;
}

const defaultConfig = {
    color: "#ffffff",
    radius: [0.5, 1.6],
    speed: [0.5, 2],
    wind: [-0.5, 2],
    changeFrequency: 250,
    rotationSpeed: [-1, 1],
    opacity: [0.3, 0.8],
    snowflakeCount: 150,
    zIndex: 10,
    direction: "down",
};

class Snowflake {
    constructor(canvas, config) {
        this.config = { ...defaultConfig, ...config };
        this.framesSinceLastUpdate = 0;
        const { radius, speed, wind, rotationSpeed, opacity } = this.config;
        this.params = {
            x: Math.random() * canvas.width,
            y: randomRange(-canvas.height, 0),
            radius: randomRange(radius[0], radius[1]),
            speed: randomRange(speed[0], speed[1]),
            wind: randomRange(wind[0], wind[1]),
            rotation: randomRange(0, Math.PI * 2),
            rotationSpeed: randomRange(rotationSpeed[0], rotationSpeed[1]),
            nextSpeed: randomRange(speed[0], speed[1]),
            nextWind: randomRange(wind[0], wind[1]),
            nextRotationSpeed: randomRange(rotationSpeed[0], rotationSpeed[1]),
            opacity: randomRange(opacity[0], opacity[1]),
        };
    }

    updateTargetParams() {
        this.params.nextSpeed = randomRange(...this.config.speed);
        this.params.nextWind = randomRange(...this.config.wind);
        this.params.nextRotationSpeed = randomRange(...this.config.rotationSpeed);
    }

    update(width, height, delta = 1) {
        const p = this.params;
        p.x += p.wind * delta;
        if (p.x > width + p.radius) p.x = -p.radius;
        if (p.x < -p.radius) p.x = width + p.radius;
        p.y += p.speed * delta;
        if (p.y > height + p.radius) p.y = -p.radius;
        p.rotation += p.rotationSpeed * delta;
        p.speed = lerp(p.speed, p.nextSpeed, 0.01);
        p.wind = lerp(p.wind, p.nextWind, 0.01);
        p.rotationSpeed = lerp(p.rotationSpeed, p.nextRotationSpeed, 0.01);
        this.framesSinceLastUpdate += 1;
        if (this.framesSinceLastUpdate > this.config.changeFrequency) {
            this.updateTargetParams();
            this.framesSinceLastUpdate = 0;
        }
    }

    draw(context) {
        context.save();
        context.globalAlpha = this.params.opacity;
        context.translate(this.params.x, this.params.y);
        context.rotate(this.params.rotation);
        context.beginPath();
        context.arc(0, 0, this.params.radius, 0, Math.PI * 2);
        context.fill();
        context.restore();
    }
}

class SnowfallEffect {
    constructor(canvas, config = {}) {
        this.canvas = canvas;
        this.context = canvas.getContext("2d");
        this.config = { ...defaultConfig, ...config };
        this.snowflakes = [];
        this.animationFrame = null;
        this.lastUpdate = performance.now();
        this.resize();
        this.updateConfig(this.config);
        this.play();
    }

    resize() {
        const ratio = Math.min(window.devicePixelRatio || 1, 2);
        const width = Math.max(1, window.innerWidth);
        const height = Math.max(1, window.innerHeight);
        this.canvas.width = Math.round(width * ratio);
        this.canvas.height = Math.round(height * ratio);
        this.canvas.style.width = `${width}px`;
        this.canvas.style.height = `${height}px`;
        this.context.setTransform(ratio, 0, 0, ratio, 0, 0);
        this.width = width;
        this.height = height;
    }

    updateConfig(newConfig = {}) {
        this.config = { ...this.config, ...newConfig };
        const target = Math.max(0, Math.floor(this.config.snowflakeCount));
        while (this.snowflakes.length < target) {
            this.snowflakes.push(new Snowflake(this.canvas, this.config));
        }
        this.snowflakes.length = target;
    }

    render(delta = 1) {
        this.context.clearRect(0, 0, this.width, this.height);
        this.context.fillStyle = this.config.color;
        this.snowflakes.forEach((snowflake) => {
            snowflake.update(this.width, this.height, delta);
            snowflake.draw(this.context);
        });
    }

    loop() {
        const now = performance.now();
        const elapsed = now - this.lastUpdate;
        this.lastUpdate = now;
        this.render(Math.max(1, elapsed / FRAME_TIME));
        this.animationFrame = window.requestAnimationFrame(() => this.loop());
    }

    play() {
        if (!this.animationFrame) {
            this.lastUpdate = performance.now();
            this.loop();
        }
    }

    pause() {
        if (this.animationFrame) {
            window.cancelAnimationFrame(this.animationFrame);
            this.animationFrame = null;
        }
    }

    destroy() {
        this.pause();
        this.snowflakes = [];
        this.context.clearRect(0, 0, this.width, this.height);
        if (this.wrapper?.parentNode) {
            this.wrapper.parentNode.removeChild(this.wrapper);
        }
    }
}

export function createSnowfall(config = {}) {
    const wrapper = document.createElement("div");
    wrapper.dataset.snowfallWrapper = "true";
    wrapper.style.cssText = `position:fixed;inset:0;width:100%;height:100%;overflow:hidden;pointer-events:none;z-index:${Number(config.zIndex ?? 10)};`;
    const canvas = document.createElement("canvas");
    canvas.setAttribute("aria-hidden", "true");
    canvas.style.cssText = "display:block;width:100%;height:100%;pointer-events:none;background:transparent;";
    wrapper.appendChild(canvas);
    (config.parent || document.body).appendChild(wrapper);

    const snowfall = new SnowfallEffect(canvas, config);
    snowfall.wrapper = wrapper;
    const resizeHandler = () => snowfall.resize();
    window.addEventListener("resize", resizeHandler, { passive: true });
    const originalDestroy = snowfall.destroy.bind(snowfall);
    snowfall.destroy = () => {
        window.removeEventListener("resize", resizeHandler);
        originalDestroy();
    };
    return snowfall;
}