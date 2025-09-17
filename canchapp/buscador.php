<?php
// Aquí puedes agregar lógica PHP si es necesaria
// Por ejemplo, procesamiento de búsquedas, conexión a base de datos, etc.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscador CanchApp</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container-fluid p-2" >
        <!-- Navbar -->
        <div class="row">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg ">
                    <a class="navbar-brand me-auto" href="#">Logo</a>
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                        aria-labelledby="offcanvasNavbarLabel">
                        <div class="offcanvas-header">
                            <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Logo</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"
                                aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body">
                            <ul class="navbar-nav justify-content-center flex-grow-1 pe-3">
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" aria-current="page" href="index.php">Home</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="gestion.php">Gestion</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 active" href="buscador.php">Reservar</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="acerca-de.php">Acerca de</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <a href="inicioses.php" class="login-button">Login</a>
                    <button class="navbar-toggler pe-0 " type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                        aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                </nav>
            </div>
        </div>
        <!-- Fin Navbar -->

        <div class="row">
            <div class="col text-center">
                <h1>Buscador de Canchas</h1>
                <p>Encuentra la cancha perfecta para ti.</p>
            </div>
        </div>

        <!-- Formulario de búsqueda -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-6">
                <form id="searchForm" method="POST">
                    <div class="mb-3">
                        <select id="location" name="location" class="form-select" required>
                            <option value="">Selecciona tu zona</option>
                            <option value="palermo">Palermo</option>
                            <option value="belgrano">Belgrano</option>
                            <option value="san_isidro">San Isidro</option>
                            <option value="tigre">Tigre</option>
                            <option value="zona_norte">Zona Norte</option>
                            <option value="zona_oeste">Zona Oeste</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <input type="date" id="date" name="date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <select id="maxPrice" name="maxPrice" class="form-select" required>
                            <option value="">Sin límite</option>
                            <option value="2000">Hasta $2,000</option>
                            <option value="3000">Hasta $3,000</option>
                            <option value="4000">Hasta $4,000</option>
                            <option value="5000">Hasta $5,000</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Buscar</button>
                </form>
            </div>
        </div>

        <!-- Resultados -->
        <div class="results-section" id="resultsSection" style="display: none; margin-top: 3rem;">
            <div class="row mb-3">
                <div class="col">
                    <h3 id="resultsTitle">Canchas Encontradas</h3>
                    <p id="resultsCount"></p>
                </div>
            </div>
            <div class="row" id="resultsContainer"></div>
        </div>

    </div>

    <!-- Bootstrap 5 JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Script principal -->
    <script>
        // Datos de ejemplo
        const sampleCourts = [
            {
                id: 'court_1',
                name: 'Club Atlético Palermo',
                address: 'Av. del Libertador 1234, Palermo',
                location: 'palermo',
                price: 2800,
                rating: 4.5,
                amenities: ['Estacionamiento', 'Vestuarios', 'Cafetería']
            },
            {
                id: 'court_2',
                name: 'Padel Center Belgrano',
                address: 'Cabildo 2567, Belgrano',
                location: 'belgrano',
                price: 3200,
                rating: 4.7,
                amenities: ['Estacionamiento', 'Vestuarios', 'Shop']
            },
            {
                id: 'court_3',
                name: 'San Isidro Sports',
                address: 'Av. Márquez 890, San Isidro',
                location: 'san_isidro',
                price: 2500,
                rating: 4.3,
                amenities: ['Vestuarios', 'Parrilla']
            },
            {
                id: 'court_4',
                name: 'Tigre Paddle Club',
                address: 'Paseo Victorica 456, Tigre',
                location: 'tigre',
                price: 2200,
                rating: 4.6,
                amenities: ['Estacionamiento', 'Vestuarios', 'Bar']
            },
            {
                id: 'court_5',
                name: 'Padel Zona Oeste',
                address: 'Ruta 7 Km 45, Morón',
                location: 'zona_oeste',
                price: 2000,
                rating: 4.2,
                amenities: ['Estacionamiento', 'Vestuarios']
            }
        ];

        // Configurar fecha mínima
        document.addEventListener('DOMContentLoaded', function () {
            const today = new Date().toISOString().split('T')[0];
            const dateInput = document.getElementById('date');

            if (dateInput) {
                dateInput.min = today;
                dateInput.value = today;
            }
        });

        // Manejar búsqueda
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.addEventListener('submit', function (e) {
                e.preventDefault();
                performSearch();
            });
        }

        function performSearch() {
            const location = document.getElementById('location').value;
            const date = document.getElementById('date').value;
            const maxPrice = document.getElementById('maxPrice').value;

            if (!location || !date) {
                showAlert('Por favor completa la ubicación y fecha', 'warning');
                return;
            }

            // Mostrar loading
            showLoading();

            // Simular búsqueda con delay
            setTimeout(() => {
                const filteredCourts = filterCourts(location, maxPrice);
                displayResults(filteredCourts, location, date);
            }, 1200);
        }

        function showLoading() {
            const resultsSection = document.getElementById('resultsSection');
            const resultsContainer = document.getElementById('resultsContainer');

            if (resultsContainer) {
                resultsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-3 text-muted">Buscando canchas disponibles...</p>
                        </div>
                    </div>
                `;

                if (resultsSection) {
                    resultsSection.style.display = 'block';
                }
            }
        }

        function filterCourts(location, maxPrice) {
            let filtered = sampleCourts.filter(court => {
                // Filtrar por ubicación
                const locationMatch = court.location === location ||
                    (location === 'zona_norte' && ['belgrano', 'san_isidro', 'tigre'].includes(court.location));

                if (!locationMatch) return false;

                // Filtrar por precio si se especifica
                if (maxPrice && court.price > parseInt(maxPrice)) {
                    return false;
                }

                return true;
            });

            return filtered;
        }

        function displayResults(courts, location, date) {
            const resultsSection = document.getElementById('resultsSection');
            const resultsContainer = document.getElementById('resultsContainer');
            const resultsTitle = document.getElementById('resultsTitle');
            const resultsCount = document.getElementById('resultsCount');

            if (!resultsContainer) return;

            if (courts.length === 0) {
                resultsContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-warning text-center">
                            <h4>😔 No encontramos canchas</h4>
                            <p class="mb-0">Intenta con otra ubicación o ajusta el precio máximo</p>
                        </div>
                    </div>
                `;
                if (resultsCount) resultsCount.textContent = '0 resultados';
                return;
            }

            if (resultsTitle) resultsTitle.textContent = 'Canchas Disponibles';
            if (resultsCount) resultsCount.textContent = `${courts.length} ${courts.length === 1 ? 'resultado' : 'resultados'} para ${formatDate(date)}`;

            resultsContainer.innerHTML = courts.map(court => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card shadow h-100">
                        <!-- Foto de la cancha -->
                        <img src="https://via.placeholder.com/400x250/28a745/ffffff?text=Cancha+de+Padel" 
                             class="card-img-top" 
                             alt="${court.name}"
                             style="height: 200px; object-fit: cover;">
                        
                        <!-- Información de la cancha -->
                        <div class="card-body d-flex flex-column">
                            <div class="mb-3">
                                <h5 class="card-title mb-2 text-primary">${court.name}</h5>
                                <div class="text-muted mb-2">
                                    📍 ${court.address}
                                </div>
                                <div class="text-success fw-bold fs-4">
                                    ${court.price.toLocaleString()} <small class="text-muted fs-6">por hora</small>
                                </div>
                            </div>
                            
                            <!-- Botón Reservar -->
                            <div class="mt-auto">
                                <button class="btn btn-primary w-100" onclick="goToReservation('${court.id}')">
                                    Reservar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `).join('');

            if (resultsSection) {
                resultsSection.style.display = 'block';
                // Scroll suave a los resultados
                resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }



        function goToReservation(courtId) {
            // Aquí redirigiría a la página de reserva con el ID de la cancha
            // Por ahora solo muestra un alert
            const court = sampleCourts.find(c => c.id === courtId);
            const date = document.getElementById('date').value;
            
            if (court) {
                // Simular redirección
                showAlert(`Redirigiendo a reserva de: ${court.name} para ${formatDate(date)}`, 'info');
                
                // En una aplicación real, harías algo como:
                window.location.href = `reservacion.html?court=${courtId}&date=${date}`;
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString + 'T00:00:00');
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            return date.toLocaleDateString('es-AR', options);
        }

        function showAlert(message, type = 'info') {
            // Crear alert temporal
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = `
                top: 20px; 
                right: 20px; 
                z-index: 9999; 
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(alertDiv);

            // Auto-remover después de 5 segundos
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>

</html>