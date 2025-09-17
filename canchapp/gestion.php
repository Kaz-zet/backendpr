<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Canchas de Pádel</title>

    <!-- links provisorios hasta resolver error modales -->
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- arreglar modales error blur modales -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css"> -->
    <style>
        :root {
            --primary-green: #28a745;
            --dark-green: #1e7e34;
            --light-green: #d4edda;
            --primary-blue: #007bff;
            --dark-blue: #0056b3;
        }

        body {
            background-color: #f8f9fa;
        }

        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .card-header {
            background-color: var(--primary-green);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }

        .btn-primary {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .btn-primary:hover {
            background-color: var(--dark-green);
            border-color: var(--dark-green);
        }

        .btn-info {
            background-color: var(--primary-blue);
            border-color: var(--primary-blue);
        }

        .btn-info:hover {
            background-color: var(--dark-blue);
            border-color: var(--dark-blue);
        }

        .court-card {
            transition: transform 0.2s;
        }

        .court-card:hover {
            transform: translateY(-2px);
        }


        .status-badge {
            font-size: 0.8em;
        }

        .table th {
            background-color: var(--light-green);
            color: var(--dark-green);
            border: none;
        }

        .nav-tabs .nav-link.active {
            background-color: var(--primary-green);
            color: white;
            border-color: var(--primary-green);
        }

        .nav-tabs .nav-link {
            color: var(--dark-green);
        }

        .modal-header {
            background-color: var(--primary-green);
            color: white;
        }

        .time-slot {
            cursor: pointer;
            transition: all 0.2s;
        }

        .time-slot:hover {
            background-color: var(--light-green);
        }

        .time-slot.occupied {
            background-color: #f8d7da;
            color: #721c24;
        }

        .time-slot.selected {
            background-color: var(--primary-green);
            color: white;
        }
    </style>
</head>

<body>
    <div class="container-fluid main-container bg-dark">
        <!-- Navbar -->
        <div class="row" id="navbar">
            <div class="col-12">
                <nav class="navbar navbar-expand-lg ">
                    <a class="navbar-brand me-auto" href="#">
                        <img src="image/icon.png" alt="Logo" width="85" height="60"
                            class="d-inline-block align-text-top">
                    </a>
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
                                    <a class="nav-link mx-lg-2" aria-current="page" href="index.html">Home</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2 active" href="gestion.html">Gestion</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="buscador.html">Reservar</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link mx-lg-2" href="acerca-de.html">Acerca de</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
        <!-- Fin Navbar -->
        <!-- Tabs de navegación -->
        <div class="row">
            <ul class="nav nav-tabs mb-4" id="managementTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="courts-tab" data-bs-toggle="tab" data-bs-target="#courts"
                        type="button" role="tab">
                        <i class="fas fa-tennis-ball me-2"></i>Canchas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings"
                        type="button" role="tab">
                        <i class="fas fa-calendar-alt me-2"></i>Turnos
                    </button>
                </li>
            </ul>
        </div>

        <!-- Contenido de las tabs -->
        <div class="row">
            <div class="tab-content" id="managementTabsContent">
                <!-- Tab de Canchas -->
                <div class="tab-pane fade show active" id="courts" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Gestión de Canchas</h5>
                                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#courtModal"
                                        onclick="openCourtModal()">
                                        <i class="fas fa-plus me-2"></i>Nueva Cancha
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" id="courtsContainer">
                        <!-- Las canchas se cargarán aquí dinámicamente -->
                    </div>
                </div>

                <!-- Tab de Turnos -->
                <div class="tab-pane fade" id="bookings" role="tabpanel">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Gestión de Turnos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="courtSelect" class="form-label">Seleccionar Cancha:</label>
                                            <select class="form-select" id="courtSelect" onchange="loadBookings()">
                                                <option value="">Todas las canchas</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="dateSelect" class="form-label">Fecha:</label>
                                            <input type="date" class="form-control" id="dateSelect"
                                                onchange="loadBookings()">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <div class="d-grid">
                                                <button class="btn btn-primary" onclick="loadBookings()">
                                                    <i class="fas fa-search me-2"></i>Buscar
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Cancha</th>
                                                    <th>Fecha</th>
                                                    <th>Horario</th>
                                                    <th>Cliente</th>
                                                    <th>Estado</th>
                                                    <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="bookingsTable">
                                                <!-- Los turnos se cargarán aquí -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

    <!-- Modal para Crear/Editar Cancha -->
    <div class="modal fade" id="courtModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="courtModalTitle">Nueva Cancha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="courtForm">
                        <input type="hidden" id="courtId">
                        <div class="mb-3">
                            <label for="courtName" class="form-label">Nombre de la Cancha</label>
                            <input type="text" class="form-control" id="courtName" required>
                        </div>
                        <div class="mb-3">
                            <label for="courtLocation" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="courtLocation" required>
                        </div>
                        <div class="mb-3">
                            <label for="courtDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="courtDescription" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="courtPrice" class="form-label">Precio por Hora ($)</label>
                                <input type="number" class="form-control" id="courtPrice" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="courtStatus" class="form-label">Estado</label>
                                <select class="form-select" id="courtStatus">
                                    <option value="active">Activa</option>
                                    <option value="maintenance">Mantenimiento</option>
                                    <option value="inactive">Inactiva</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveCourt()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Gestionar Turno -->
    <div class="modal fade" id="bookingModal" tabindex="-1" >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestionar Turno</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <strong>Cancha:</strong> <span id="bookingCourt"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Fecha:</strong> <span id="bookingDate"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Horario:</strong> <span id="bookingTime"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Cliente:</strong> <span id="bookingClient"></span>
                    </div>
                    <div class="mb-3">
                        <label for="bookingStatus" class="form-label">Estado:</label>
                        <select class="form-select" id="bookingStatus">
                            <option value="confirmed">Confirmado</option>
                            <option value="cancelled">Cancelado</option>
                            <option value="completed">Completado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="updateBooking()">Actualizar</button>
                    <button type="button" class="btn btn-danger" onclick="deleteBooking()">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Arreglar los modales -->
    <!--
    <script>
        const courtModal = new bootstrap.Modal(document.getElementById('courtModal'), {
            backdrop: false
        }); 
    </script>
    <script>
        const bookingModal = new bootstrap.Modal(document.getElementById('bookingModal'), {
            backdrop: false
        });
    </script> -->
 
    <script>
        // Datos de prueba
        let courts = [
            {
                id: 1,
                name: "Cancha Central",
                location: "Av. Pádel 123, Buenos Aires",
                description: "Cancha principal con césped sintético de última generación",
                price: 2500,
                status: "active"
            },
            {
                id: 2,
                name: "Cancha Norte",
                location: "Av. Lomas 43, Buenos Aires",
                description: "Cancha techada ideal para días de lluvia",
                price: 2800,
                status: "active"
            },
            {
                id: 3,
                name: "Cancha Sur",
                location: "Calle Falsa 123, Buenos Aires",
                description: "Cancha con iluminación LED profesional",
                price: 2300,
                status: "maintenance"
            }
        ];

        let bookings = [
            {
                id: 1,
                courtId: 1,
                courtName: "Cancha Central",
                date: "2025-09-17",
                time: "09:00-10:00",
                client: "Juan Pérez",
                clientPhone: "+54 11 1234-5678",
                status: "confirmed"
            },
            {
                id: 2,
                courtId: 1,
                courtName: "Cancha Central",
                date: "2025-09-17",
                time: "10:00-11:00",
                client: "María González",
                clientPhone: "+54 11 8765-4321",
                status: "confirmed"
            },
            {
                id: 3,
                courtId: 2,
                courtName: "Cancha Norte",
                date: "2025-09-17",
                time: "14:00-15:00",
                client: "Carlos Rodríguez",
                clientPhone: "+54 11 5555-5555",
                status: "cancelled"
            }
        ];

        let currentBookingId = null;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function () {
            loadCourts();
            loadBookings();
            loadCourtSelect();

            // Establecer fecha actual
            document.getElementById('dateSelect').value = new Date().toISOString().split('T')[0];
        });

        // Funciones para Canchas
        function loadCourts() {
            const container = document.getElementById('courtsContainer');
            container.innerHTML = '';

            courts.forEach(court => {
                const statusClass = court.status === 'active' ? 'success' :
                    court.status === 'maintenance' ? 'warning' : 'danger';
                const statusText = court.status === 'active' ? 'Activa' :
                    court.status === 'maintenance' ? 'Mantenimiento' : 'Inactiva';

                const courtCard = `
                    <div class="col-md-4 mb-3">
                        <div class="card court-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title">${court.name}</h5>
                                    <span class="badge bg-${statusClass} status-badge">${statusText}</span>
                                </div>
                                <p class="card-text">${court.location}</p>
                                <p class="card-text text-muted">${court.description}</p>
                                <p class="card-text">
                                    <strong>$${court.price}</strong> por hora
                                </p>

                                <div class="btn-group w-100" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="editCourt(${court.id})">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteCourt(${court.id})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += courtCard;
            });
        }

        function openCourtModal(courtId = null) {
            const modal = document.getElementById('courtModal');
            const title = document.getElementById('courtModalTitle');
            const form = document.getElementById('courtForm');

            form.reset();

            if (courtId) {
                title.textContent = 'Editar Cancha';
                const court = courts.find(c => c.id === courtId);
                if (court) {
                    document.getElementById('courtId').value = court.id;
                    document.getElementById('courtName').value = court.name;
                    document.getElementById('courtLocation').value = court.location;
                    document.getElementById('courtDescription').value = court.description;
                    document.getElementById('courtPrice').value = court.price;
                    document.getElementById('courtStatus').value = court.status;
                }
            } else {
                title.textContent = 'Nueva Cancha';
                document.getElementById('courtId').value = '';
            }
        }

        function editCourt(courtId) {
            openCourtModal(courtId);
            new bootstrap.Modal(document.getElementById('courtModal')).show();
        }

        function deleteCourt(courtId) {
            if (confirm('¿Estás seguro de que deseas eliminar esta cancha?')) {
                courts = courts.filter(c => c.id !== courtId);
                loadCourts();
                loadCourtSelect();
            }
        }

        function saveCourt() {
            const form = document.getElementById('courtForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const courtId = document.getElementById('courtId').value;
            const courtData = {
                name: document.getElementById('courtName').value,
                location: document.getElementById('courtLocation').value,
                description: document.getElementById('courtDescription').value,
                price: parseFloat(document.getElementById('courtPrice').value),
                status: document.getElementById('courtStatus').value
            };

            if (courtId) {
                // Editar cancha existente
                const courtIndex = courts.findIndex(c => c.id == courtId);
                if (courtIndex !== -1) {
                    courts[courtIndex] = { ...courts[courtIndex], ...courtData };
                }
            } else {
                // Crear nueva cancha
                const newCourt = {
                    id: Math.max(...courts.map(c => c.id)) + 1,
                    ...courtData
                };
                courts.push(newCourt);
            }

            bootstrap.Modal.getInstance(document.getElementById('courtModal')).hide();
            loadCourts();
            loadCourtSelect();
        }

        // Funciones para Turnos
        function loadCourtSelect() {
            const select = document.getElementById('courtSelect');
            select.innerHTML = '<option value="">Todas las canchas</option>';

            courts.forEach(court => {
                select.innerHTML += `<option value="${court.id}">${court.name}</option>`;
            });
        }

        function loadBookings() {
            const courtId = document.getElementById('courtSelect').value;
            const date = document.getElementById('dateSelect').value;

            let filteredBookings = bookings;

            if (courtId) {
                filteredBookings = filteredBookings.filter(b => b.courtId == courtId);
            }

            if (date) {
                filteredBookings = filteredBookings.filter(b => b.date === date);
            }

            const tbody = document.getElementById('bookingsTable');
            tbody.innerHTML = '';

            filteredBookings.forEach(booking => {
                const statusClass = booking.status === 'confirmed' ? 'success' :
                    booking.status === 'cancelled' ? 'danger' : 'info';
                const statusText = booking.status === 'confirmed' ? 'Confirmado' :
                    booking.status === 'cancelled' ? 'Cancelado' : 'Completado';

                const row = `
                    <tr>
                        <td>${booking.courtName}</td>
                        <td>${new Date(booking.date + 'T00:00:00').toLocaleDateString('es-AR')}</td>
                        <td>${booking.time}</td>
                        <td>${booking.client}</td>
                        <td><span class="badge bg-${statusClass}">${statusText}</span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" onclick="openBookingModal(${booking.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        function openBookingModal(bookingId) {
            const booking = bookings.find(b => b.id === bookingId);
            if (!booking) return;

            currentBookingId = bookingId;
            document.getElementById('bookingCourt').textContent = booking.courtName;
            document.getElementById('bookingDate').textContent = new Date(booking.date + 'T00:00:00').toLocaleDateString('es-AR');
            document.getElementById('bookingTime').textContent = booking.time;
            document.getElementById('bookingClient').textContent = booking.client;
            document.getElementById('bookingStatus').value = booking.status;

            new bootstrap.Modal(document.getElementById('bookingModal')).show();
        }

        function updateBooking() {
            if (!currentBookingId) return;

            const bookingIndex = bookings.findIndex(b => b.id === currentBookingId);
            if (bookingIndex !== -1) {
                bookings[bookingIndex].status = document.getElementById('bookingStatus').value;
            }

            bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
            loadBookings();
        }

        function deleteBooking() {
            if (!currentBookingId) return;

            if (confirm('¿Estás seguro de que deseas eliminar este turno?')) {
                bookings = bookings.filter(b => b.id !== currentBookingId);
                bootstrap.Modal.getInstance(document.getElementById('bookingModal')).hide();
                loadBookings();
            }
        }
    </script>
</body>

</html>