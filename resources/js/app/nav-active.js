function setActiveNav() {
  const path = window.location.pathname;
  const links = document.querySelectorAll('[data-nav]');

  links.forEach((a) => {
    const target = a.getAttribute('data-nav');
    const isActive = path.startsWith(target);
    const dot = a.querySelector('.nav-dot');
    const label = a.querySelector('.text-label');

    if (isActive) {
      // Efek saat AKTIF
      a.classList.add('text-white');
      a.classList.remove('text-white/70');
      if (dot) dot.classList.remove('opacity-0'); // Munculkan titik
      if (label) label.classList.add('font-bold'); // Bold teks
    } else {
      // Efek saat TIDAK AKTIF
      a.classList.remove('text-white');
      a.classList.add('text-white/70');
      if (dot) dot.classList.add('opacity-0'); // Sembunyikan titik
      if (label) label.classList.remove('font-bold');
    }
  });
}

// Jalankan saat pertama kali muat
document.addEventListener('DOMContentLoaded', setActiveNav);
// Jalankan setiap kali halaman berpindah (Jika pakai PJAX/Livewire)
document.addEventListener('pjax:end', setActiveNav);