<?php
// Aquí puedes agregar lógica PHP si es necesaria
// Por ejemplo, obtener datos del usuario desde base de datos, procesamiento de formularios, etc.
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - PadelReservas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --padel-primary: #2c5530;
            --padel-secondary: #4a7c59;
            --padel-accent: #8bc34a;
            --padel-light: #f8f9fa;
        }

        body {
            background-color: var(--padel-light);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .color-header-bg{
            background: linear-gradient(135deg, var(--padel-primary) 0%, var(--padel-secondary) 100%);
        }

        .profile-header {
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 15px 15px;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            object-fit: cover;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background-color: var(--padel-primary);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
            border: none;
        }

        .btn-primary {
            background-color: var(--padel-primary);
            border-color: var(--padel-primary);
        }

        .btn-primary:hover {
            background-color: var(--padel-secondary);
            border-color: var(--padel-secondary);
        }

        .btn-outline-success {
            color: var(--padel-primary);
            border-color: var(--padel-primary);
        }

        .btn-outline-success:hover {
            background-color: var(--padel-primary);
            border-color: var(--padel-primary);
        }

        .stats-card {
            background: linear-gradient(135deg, var(--padel-accent) 0%, #7cb342 100%);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-radius: 15px;
        }

        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            display: block;

        }

        .reservation-item {
            border-left: 4px solid var(--padel-accent);
            background-color: white;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 10px 10px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .profile-tabs .nav-link {
            color: var(--padel-primary);
            border: none;
            font-weight: 500;
        }

        .profile-tabs .nav-link.active {
            background-color: var(--padel-primary);
            color: white;
            border-radius: 10px;
        }

        .form-control:focus {
            border-color: var(--padel-accent);
            box-shadow: 0 0 0 0.2rem rgba(139, 195, 74, 0.25);
        }

        
    </style>
</head>

<body>
    <div class="container-fluid p-2 d-flex-justify-content-center bg-dark" style="background-image: url('image/padel-fondo.jpg'); background-size: cover; background-repeat: no-repeat;">
        <div class="color-header-bg">
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
                                        <a class="nav-link mx-lg-2" aria-current="page" href="index.php">Home</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mx-lg-2" href="gestion.php">Gestion</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mx-lg-2" href="buscador.php">Reservar</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link mx-lg-2" href="acerca-de.php">Acerca de</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <a href="#" class="perfil-button">perfil</a>
                        <button class="navbar-toggler pe-0 " type="button" data-bs-toggle="offcanvas"
                            data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                            aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </nav>
                </div>
            </div>
            <!-- Fin Navbar -->

            <!-- Header del Perfil -->
            <div class="profile-header">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-9">
                            <h2 class="mb-2">Juan Pérez</h2>
                            <p class="mb-1"><i class="fas fa-envelope me-2"></i>juan.perez@email.com</p>
                            <p class="mb-1"><i class="fas fa-phone me-2"></i>+54 11 1234-5678</p>
                            <p class="mb-0"><i class="fas fa-calendar me-2"></i>Miembro desde el 20 de Octubre 2023</p>
                        </div>
                        <div class="col-md-3 text-center">
                            <img src="img/papus.jpg" alt="Avatar" class="profile-avatar">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container" >
            <!-- Contenido Principal con Tabs -->
            <div class="row ">
                <div class="col-12">
                    <ul class="nav nav-tabs profile-tabs mb-4 bg-light rounded-4" id="profileTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="reservas-tab" data-bs-toggle="tab"
                                data-bs-target="#reservas" type="button" role="tab">
                                <i class="fas fa-calendar-check me-2"></i>Mis Reservas
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="datos-tab" data-bs-toggle="tab" data-bs-target="#datos"
                                type="button" role="tab">
                                <i class="fas fa-user-edit me-2"></i>Datos Personales
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial"
                                type="button" role="tab">
                                <i class="fas fa-history me-2"></i>Historial
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="profileTabContent">
                        <!-- Tab Reservas -->
                        <div class="tab-pane fade show active" id="reservas" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Próximas Reservas
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="reservation-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4">
                                                        <h6 class="mb-1">Cancha Central</h6>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-calendar me-1"></i>Viernes, 20 Sept 2024
                                                            <i class="fas fa-clock ms-3 me-1"></i>18:00 - 19:30
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-users me-1"></i>Juan P., María G., Carlos
                                                            R., Ana M.
                                                        </small>
                                                    </div>
                                                    <div class="col-md-4 text-center">
                                                        <h6 class="mb-1">Código de Reserva</h6>
                                                        <p class="text-muted">XYZ789</p>
                                                    </div>

                                                    <div class="col-md-4 text-end">
                                                        <span
                                                            class="status-badge status-confirmed mb-2 d-block">Confirmada</span>
                                                        <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="reservation-item">
                                                <div class="row align-items-center">
                                                    <div class="col-md-4">
                                                        <h6 class="mb-1">Cancha 2</h6>
                                                        <p class="text-muted mb-1">
                                                            <i class="fas fa-calendar me-1"></i>Domingo, 22 Sept 2024
                                                            <i class="fas fa-clock ms-3 me-1"></i>10:00 - 11:30
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-users me-1"></i>Juan P., Luis M.
                                                            <span class="text-warning ms-2">Faltan 2 jugadores</span>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-4 text-center">
                                                        <h6 class="mb-1">Código de Reserva</h6>
                                                        <p class="text-muted">ABC123</p>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <span
                                                            class="status-badge status-pending mb-2 d-block">Pendiente</span>
                                                        <button
                                                            class="btn btn-sm btn-outline-success me-1">Invitar</button>
                                                        <button class="btn btn-sm btn-outline-danger">Cancelar</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Datos Personales -->
                        <div class="tab-pane fade" id="datos" role="tabpanel">
                            <div class="row">
                                <div class="col-lg-8">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Información Personal
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST">
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Nombre</label>
                                                        <input type="text" name="nombre" class="form-control" value="Juan" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Apellido</label>
                                                        <input type="text" name="apellido" class="form-control" value="Pérez" required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" name="email" class="form-control"
                                                            value="juan.perez@email.com" required>
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Teléfono</label>
                                                        <input type="tel" name="telefono" class="form-control" value="+54 11 1234-5678"
                                                            required>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Fecha de Nacimiento</label>
                                                        <input type="date" name="fecha_nacimiento" class="form-control" value="1985-06-15">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="form-label">Nivel de Juego</label>
                                                        <select name="nivel_juego" class="form-control">
                                                            <option>Principiante</option>
                                                            <option selected>Intermedio</option>
                                                            <option>Avanzado</option>
                                                            <option>Profesional</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Posición Preferida</label>
                                                    <select name="posicion_preferida" class="form-control">
                                                        <option>Sin preferencia</option>
                                                        <option selected>Derecha</option>
                                                        <option>Izquierda</option>
                                                    </select>
                                                </div>
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><i class="fas fa-key me-2"></i>Cambiar Contraseña</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST">
                                                <div class="mb-3">
                                                    <label class="form-label">Contraseña Actual</label>
                                                    <input type="password" name="password_actual" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Nueva Contraseña</label>
                                                    <input type="password" name="nueva_password" class="form-control" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Confirmar Contraseña</label>
                                                    <input type="password" name="confirmar_password" class="form-control" required>
                                                </div>
                                                <button type="submit" class="btn btn-primary w-100">
                                                    Cambiar Contraseña
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab Historial -->
                        <div class="tab-pane fade" id="historial" role="tabpanel">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Partidos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="reservation-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1">Cancha Central</h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-calendar me-1"></i>Miércoles, 18 Sept 2024
                                                    <i class="fas fa-clock ms-3 me-1"></i>19:00 - 20:30
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i>Juan P., María G., Carlos R., Ana
                                                    M.
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <span class="badge bg-success mb-1">Completado</span>
                                                <div class="text-muted small">$2,500</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="reservation-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1">Cancha 3</h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-calendar me-1"></i>Lunes, 16 Sept 2024
                                                    <i class="fas fa-clock ms-3 me-1"></i>18:00 - 19:30
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i>Juan P., Luis M., Pedro S., Ana L.
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <span class="badge bg-success mb-1">Completado</span>
                                                <div class="text-muted small">$2,500</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="reservation-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1">Cancha 1</h6>
                                                <p class="text-muted mb-1">
                                                    <i class="fas fa-calendar me-1"></i>Sábado, 14 Sept 2024
                                                    <i class="fas fa-clock ms-3 me-1"></i>10:00 - 11:30
                                                </p>
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i>Juan P., Carlos R.
                                                </small>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <span class="badge bg-danger mb-1">Cancelado</span>
                                                <div class="text-muted small">Reembolsado</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="text-center mt-4">
                                        <button class="btn btn-outline-success">Ver Más Partidos</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>

</html>