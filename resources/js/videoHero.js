export async function initVideoHero() {
    const video = document.querySelector('.home-hero__video');
    if (!video) return;
  
    try {
      // tente de lancer (souvent déjà auto)
      await video.play();
      document.documentElement.classList.remove('no-hero-video');
    } catch {
      // autoplay bloqué -> on garde poster / style fallback
      document.documentElement.classList.add('no-hero-video');
    }
  }
  