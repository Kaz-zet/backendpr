<?php
// Aquí puedes agregar cualquier lógica PHP que necesites
// Por ejemplo: conexión a base de datos, sesiones, etc.
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <div class="container-fluid p-2">
    <!-- Navbar -->
    <div class="row" id="navbar">
      <div class="col-12">
        <nav class="navbar navbar-expand-lg ">
          <a class="navbar-brand me-auto" href="#">
            <img src="image/icon.png" alt="Logo" width="85" height="60" class="d-inline-block align-text-top">
          </a>
          <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
            aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
              <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Logo</h5>
              <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
              <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
                <li class="nav-item">
                  <a class="nav-link mx-lg-2 active" aria-current="page" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mx-lg-2" href="gestion.php">Gestion</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mx-lg-2" href="buscador.php">Reservar</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link mx-lg-2" href="acerca-de.html">Acerca de</a>
                </li>
              </ul>
            </div>
          </div>
          <a href="inicioses.php" class="login-button">Login</a>
          <button class="navbar-toggler pe-0 " type="button" data-bs-toggle="offcanvas"
            data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
        </nav>
      </div>
    </div>
    <!-- Fin Navbar -->


    <!-- inicio pagina de inicio -->
    <div class="row py-5 mb-5 mt-3 " id="inicio">
      <div class="col-12 d-flex flex-column justify-content-center align-items-center text-center">
        <h1 class="text-center-left text-white">Bienvenido a CanchApp</h1>
        <p class="text-center-left text-white">Tu sitio de confianza para reservar o gestionar canchas.</p>
        <button type="button" class="btn btn-primary mt-2">Saber Más</button>

      </div>
    </div>
    <!-- fin pagina de inicio -->


    <!-- Carrusel -->
    <div class="row py-5 mt-5 bg-white rounded shadow-lg">
      <div class="col-12 d-flex justify-content-center align-items-center mx-auto" style="height:400px;">
        <div id="carouselExampleCaptions" class="carousel slide w-100 h-100">
          <div class="carousel-indicators">
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="0" class="active"
              aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="1"
              aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#carouselExampleCaptions" data-bs-slide-to="2"
              aria-label="Slide 3"></button>
          </div>
          <div class="carousel-inner h-100" style="height:100%;">
            <div class="carousel-item active h-100" style="height:100%;">
              <img src="image/raqueta.avif" class="d-block w-100 h-100"
                alt="Padel racket resting on a court, surrounded by green turf and white lines, evoking a sense of anticipation and excitement for a game"
                style="object-fit:cover; height:100%;">
              <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                <h5 class="fw-bold text-success">Reserva Fácil</h5>
                <p class="text-white">Elige tu cancha y horario en segundos. ¡Jugar nunca fue tan simple!</p>
              </div>
            </div>
            <div class="carousel-item h-100" style="height:100%;">
              <img src="image/raqueta.avif" class="d-block w-100 h-100" alt="Gestiona tus canchas"
                style="object-fit:cover; height:100%;">
              <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                <h5 class="fw-bold text-primary">Gestión Rápida</h5>
                <p class="text-white">Administra disponibilidad y horarios con un solo click.</p>
              </div>
            </div>
            <div class="carousel-item h-100" style="height:100%;">
              <img src="image/raqueta.avif" class="d-block w-100 h-100" alt="Disfruta el pádel"
                style="object-fit:cover; height:100%;">
              <div class="carousel-caption d-none d-md-block bg-dark bg-opacity-50 rounded p-3">
                <h5 class="fw-bold text-warning">Disfruta el Pádel</h5>
                <p class="text-white">Vive la mejor experiencia en nuestras canchas modernas y cómodas.</p>
              </div>
            </div>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleCaptions"
            data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Anterior</span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleCaptions"
            data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Siguiente</span>
          </button>
        </div>
      </div>
    </div>
    <!-- fin carrusel -->


    <!-- cancha destacada -->
    <div class="row py-5 px-5 mt-5 bg-light rounded shadow" id="reserva-gestion">
      <div class="col-12 text-center mb-4">
        <h1 class="text-primary fw-bold mb-3">cancha destacada:</h1>
        <p class="text-secondary fs-5">Descubre las mejores canchas para de disfrutar del pádel. Reserva tu cancha
          fácilmente y
          comienza a jugar hoy mismo.</p>
      </div>
      <div class="col-12 py-2 d-flex justify-content-center gap-4 flex-wrap bg-dark p-4 rounded">
        <div class="card bg-white border-0 shadow-sm" style="width: 20rem;">
          <img src="image/raqueta.avif" class="card-img-top rounded-top" alt="Reserva tu turno">
          <div class="card-body">
            <h5 class="card-title text-primary fw-semibold">cancha re fachera</h5>
            <p class="card-text text-secondary">Descripcion de la cancha re fachera</p>
            <a href="buscador.php" class="btn btn-success w-100">Reservar</a>
          </div>
        </div>
      </div>
    </div>
    <!-- fin reserva -->


    <!-- Footer -->
    <footer>
      <div class="row p-5 bg-secondary text-white">
        <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
          <h3 class="mb-2">CanchApp</h3>
          <p>Tu sitio de confianza para reservar y gestionar canchas de pádel.</p>
        </div>
        <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
          <h5 class="mb-2">Enlaces</h5>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Inicio</a>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Sobre Nosotros</a>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Servicios</a>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Contacto</a>
        </div>
        <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
          <h5 class="mb-2">Contacto</h5>
          <p class="mb-1">Email: info@canchapp.com</p>
          <p class="mb-1">Tel: +54 11 1234-5678</p>
          <p class="mb-1">Dirección: Av. Pádel 123, Buenos Aires</p>
        </div>
        <div class="col-xs-12 col-md-6 col-lg-3 mb-3">
          <h5 class="mb-2">Síguenos</h5>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Instagram</a>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Facebook</a>
          <a href="#" class="d-block text-white text-decoration-none mb-1">Twitter</a>
        </div>
      </div>
      <div class="row bg-dark text-white text-center py-2">
        <div class="col-12">
          <small>&copy; 2024 CanchApp. Todos los derechos reservados.</small>
        </div>
      </div>
    </footer>


    <!-- Fin Footer -->

  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  </div>
</body>

</html>