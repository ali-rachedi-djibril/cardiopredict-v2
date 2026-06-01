<header>
  <div class="container header-row">
    <a href="index.php" class="logo">
      <span class="logo-badge">❤</span>
      <span>CardioPredict</span>
    </a>
    <button class="nav-toggle" id="navToggle" onclick="toggleNav()" aria-label="Menu navigation">
      <span></span><span></span><span></span>
    </button>
    <nav id="mainNav">
      <a href="index.php"          class="<?= $page==='accueil'        ? 'active':'' ?>">Accueil</a>
      <a href="prediction.php"     class="<?= $page==='prediction'     ? 'active':'' ?>">Prédiction</a>
      <a href="visualisations.php" class="<?= $page==='visualisations' ? 'active':'' ?>">Visualisations</a>
      <a href="methode.php"        class="<?= $page==='methode'        ? 'active':'' ?>">Méthode</a>
      <a href="apropos.php"        class="<?= $page==='apropos'        ? 'active':'' ?>">À propos</a>
    </nav>
  </div>
</header>
<script>
function toggleNav() {
  const nav    = document.getElementById('mainNav');
  const toggle = document.getElementById('navToggle');
  nav.classList.toggle('nav-open');
  toggle.classList.toggle('open');
}
// Fermer le menu si clic en dehors
document.addEventListener('click', function(e) {
  const nav    = document.getElementById('mainNav');
  const toggle = document.getElementById('navToggle');
  if (!nav.contains(e.target) && !toggle.contains(e.target)) {
    nav.classList.remove('nav-open');
    toggle.classList.remove('open');
  }
});
</script>
