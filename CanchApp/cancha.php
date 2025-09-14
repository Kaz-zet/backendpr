<?php
session_start();
require_once 'conexiones/conDB.php';

$rol = $_SESSION['rol'] ?? null;
$idduenio = $_SESSION['id'] ?? null; //Creo variable para sacar la ID

$id_usuario = $_SESSION['id'] ?? null;

//PARA EDITAR CANCHA!!

$msgError = [];
$msgOk = [];

//AGREGAR A FAVORITOSS LA CANCHA--------------------------------------------------------------------

$misFavoritos = [];
$favoritosIds = []; //Le ponemos ID asi se storea mas fácil.

if ($id_usuario) {
    $stmt = $pdo->prepare("
        SELECT c.*, f.id_favorito
        FROM cancha c
        INNER JOIN favoritos f ON c.id_cancha = f.id_cancha 
        WHERE f.id_usuario = ?
    ");
    $stmt->execute([$id_usuario]);
    $misFavoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    //Crea un array de las canchas favoritas para que sea mas lindo a la vista
    $favoritosIds = array_column($misFavoritos, 'id_cancha');
}

//Saca o pone en favoritos.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'toggle_favorito') {
    if (!$id_usuario) {
        header("Location: login.php");
        exit;
    }
    
    $id_cancha = $_POST['id_cancha'];
    
    try {
        //SI YA ESTÁ EN FAVORITOS
        $stmt = $pdo->prepare("SELECT id_favorito FROM favoritos WHERE id_usuario = ? AND id_cancha = ?");
        $stmt->execute([$id_usuario, $id_cancha]);
        $existe = $stmt->fetch(); //creamos la variable "existe" en la busqueda de canchas, por ende revisa si esa cancha existe en favoritos, si si, te deja eliminarla, si no, se añadae.
        
        if ($existe) {
            //SACAR DE FAVORITOS
            $stmt = $pdo->prepare("DELETE FROM favoritos WHERE id_usuario = ? AND id_cancha = ?");
            $stmt->execute([$id_usuario, $id_cancha]);
            $msg = "Cancha removida de favoritos";
        } else {
            //AÑADIR A FAVORITOS
            $stmt = $pdo->prepare("INSERT INTO favoritos (id_usuario, id_cancha) VALUES (?, ?)");
            $stmt->execute([$id_usuario, $id_cancha]);
            $msg = "Cancha agregada a favoritos";
        }
        
        //se restartea la pagina asi se ven los nuevos cambios.
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

//-----------------------------------------------------------------------

//EDITAR CANCHAS!!

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'editar') { //Utilizo el mismo filtro que al crear cancha pero en este caso saco su Id, y remplazo los datos utilizando esa ID.
    $id = $_POST['id_cancha'];
    $nombre = trim($_POST['nombre']);
    $lugar  = trim($_POST['lugar']);
    $bio  = trim($_POST['bio']);
    $foto  = "";

    if ($nombre === '' || $lugar === '' || $bio === '') {
        $msgError[$id] = "Completa todos los campos.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM cancha WHERE nombre = ? AND id_cancha <> ?"); //<> permite que ponele, si qerés editar la cancha y dejás el mismo nombre, que no te mande q ya existe, sino q entienda q no la cambiaste.
            $stmt->execute([$nombre, $id]);

            if ($stmt->fetch()) {
                $msgError[$id] = "Ya existe otra cancha con ese nombre.";
            } else {

                 if (!empty($_FILES['foto']['name'])) {
                    // Buscar la foto vieja
                    $stmt = $pdo->prepare("SELECT foto FROM cancha WHERE id_cancha = ?");
                    $stmt->execute([$id]);
                    $cancha = $stmt->fetch();

                    if ($cancha && !empty($cancha['foto'])) {
                        $rutaVieja = __DIR__ . "/uploads/" . $cancha['foto'];
                        if (file_exists($rutaVieja)) {
                            unlink($rutaVieja);
                        }
                    }

                    // Guardar nueva foto
                    $nombreArchivo = time() . "_" . basename($_FILES['foto']['name']);
                    $rutaDestino = __DIR__ . "/uploads/" . $nombreArchivo;
                    move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino);

                    $foto = $nombreArchivo;
                }
                 if ($foto) {
                    $stmt = $pdo->prepare("UPDATE cancha SET nombre = ?, lugar = ?, bio = ?, foto = ? WHERE id_cancha = ?");
                    $stmt->execute([$nombre, $lugar, $bio, $foto, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE cancha SET nombre = ?, lugar = ?, bio = ? WHERE id_cancha = ?");
                    $stmt->execute([$nombre, $lugar, $bio, $id]);
                }

                $_SESSION['msgOk'] = "Cancha editada correctamente.";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            }
        } catch (Throwable $e) {
            $msgError[$id] = "Error: " . $e->getMessage();
        }
    }
}
//-------------------------------------------------------------------------------------------


    try {
        $stmt = $pdo->query("SELECT * FROM cancha");
        $canchas = $stmt->fetchAll(); // lo mismo q lo demás pero creamos canchas, que va a ser un array de la busqueda de todas las canchas.
    if ($rol === 'duenio' && $idduenio) {
        $stmt2 = $pdo->prepare("SELECT * FROM cancha WHERE id_duenio = ?");
        $stmt2->execute([$idduenio]);
        $misCanchas = $stmt2->fetchAll();
    }
    } catch (PDOException $e) {
        echo "Error al encontrar las canchas: " . $e->getMessage();
        $canchas = [];
        $misCanchas=[];
    }
    
    
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de canchas</title>
</head>
<body>    
    <?php if ($misFavoritos): ?>
    <h1>Mis Favoritos</h1>
    <ul>
        <?php foreach ($misFavoritos as $cancha): ?>
            <li><?= htmlspecialchars($cancha['nombre']) ?> - <?= htmlspecialchars($cancha['lugar']) ?> - <?= htmlspecialchars($cancha['bio']) ?><?php if ($cancha['foto']): ?>
                            <br><img src="uploads/<?= htmlspecialchars($cancha['foto']) ?>" width="100" height="60">
                        <?php endif; ?> <a href="calendario.php?id=<?= $cancha['id_cancha'] ?>"
             
                    style="background: #000000ff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                    Ver Detalles
                    </a>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="id_cancha" value="<?= $cancha['id_cancha'] ?>">
                        <button type="submit" name="accion" value="toggle_favorito">
                            <?= in_array($cancha['id_cancha'], $favoritosIds) ? '⭐' : '☆' ?>
                        </button>
                    </form></li>
    

        <?php endforeach; ?>
    </ul>
<?php endif; ?> 
    <h1>Canchas registradas</h1>
    <?php if ($canchas && count($canchas) > 0): ?> <!--permite comprobar que existan canchas y que tengan datos adentro-->
        <ul>
            <?php foreach ($canchas as $cancha): ?>
                <li>
                    <strong><?php echo htmlspecialchars($cancha['nombre']); ?></strong>  <!--Muestra las variables q queremos-->
                    - Ubicación: <?php echo htmlspecialchars($cancha['lugar']); ?>
                     - Descipcion: <?php echo htmlspecialchars($cancha['bio']); ?>
                     <?php if ($cancha['foto']): ?>
                            <br><img src="uploads/<?= htmlspecialchars($cancha['foto']) ?>" width="100" height="60">
                        <?php endif; ?>
                    <a href="calendario.php?id=<?= $cancha['id_cancha'] ?>" 
                    style="background: #000000ff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                        Ver Detalles
                    </a>



            <!--PARA AGREGAR FAV CANCHAS (EL CODE ESTÁ EN ESTE PHP, MIS FAVORTIOS.PHP NO ANDA)-->
            

                    <form method="post" style="display:inline;">
                        <input type="hidden" name="id_cancha" value="<?= $cancha['id_cancha'] ?>">
                        <button type="submit" name="accion" value="toggle_favorito">
                            <?= in_array($cancha['id_cancha'], $favoritosIds) ? '⭐' : '☆' ?>
                        </button>
                    </form>


                    
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No hay canchas registradas.</p>
    <?php endif; ?>

    <?php if ($rol === 'duenio'): ?>
        <h2>Mis canchas</h2>

                        <?php if (!empty($_SESSION['msgOk'])): ?>
                        <p style="color:green;"><?= $_SESSION['msgOk'] ?></p>
                        <?php unset($_SESSION['msgOk']); ?>
                        <?php endif; ?>
                        
        <?php if ($misCanchas && count($misCanchas) > 0): ?>



            <ul>
                <?php foreach ($misCanchas as $cancha): ?>
                    <li>
                        <?php if (isset($msgError[$cancha['id_cancha']])): ?> <!--ESTE ERROR VA EN EL LOOP YA QUE AFUERA DA ERROR!-->
                        <p style="color:red;"><?= $msgError[$cancha['id_cancha']] ?></p>
                    <?php endif; ?>

                        <strong><?= htmlspecialchars($cancha['nombre']) ?></strong>
                        - Ubicación: <?= htmlspecialchars($cancha['lugar']) ?>
                        - Descripción: <?= htmlspecialchars($cancha['bio']) ?>
                        <?php if ($cancha['foto']): ?>
                            <br><img src="uploads/<?= htmlspecialchars($cancha['foto']) ?>" width="100" height="60">
                        <?php endif; ?>

                        

                        <!--BOTON PARA EDITAR-->
                        <button onclick="abrirModal(
                        '<?= $cancha['id_cancha'] ?>',
                        '<?= htmlspecialchars($cancha['nombre']) ?>',
                        '<?= htmlspecialchars($cancha['lugar']) ?>',
                        '<?= htmlspecialchars($cancha['bio']) ?>',
                        '<?= htmlspecialchars($cancha['foto']) ?>'
                        )">Editar</button>

                        <!--POPUP PARA EDITAR CANCHA-->
                        <div id="modalEditar" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
                        <div style="background:#fff; padding:20px; margin:10% auto; width:300px; border-radius:10px;">
                            <h2>Editar Cancha</h2>
                            <form method="post" enctype="multipart/form-data">
                            <input type="hidden" name="id_cancha" id="edit_id">
                            <input type="text" name="nombre" id="edit_nombre" required><br><br>
                            <input type="text" name="lugar" id="edit_lugar" required><br><br>
                            <input type="text" name="bio" id="edit_bio" required><br><br>
                            <input type="file" name="foto" id="edit_foto"><br><br>
                            <button type="submit" name="accion" value="editar">Guardar</button>
                            <button type="button" onclick="cerrarModal()">Cancelar</button>
                            </form>
                        </div>
                        </div>


                        <!--SCRIPT PARA EL POPUP-->
                        <script>
                        function abrirModal(id, nombre, lugar, bio, foto) {
                        document.getElementById('modalEditar').style.display = 'block';
                        document.getElementById('edit_id').value = id;
                        document.getElementById('edit_nombre').value = nombre;
                        document.getElementById('edit_lugar').value = lugar;
                        document.getElementById('edit_bio').value = bio;
                        document.getElementById('edit_foto').value = foto;
                        }
                        function cerrarModal() {
                        document.getElementById('modalEditar').style.display = 'none';
                        }
                        </script>

                    <!--BOTON PARA BORRAR CANCHA, ESTÁ COMBINADO CON EL PHP ELIMINAR_CANCHA.PHP-->
                        <form method="post" action="eliminar_cancha.php" style="display:inline;" onsubmit="return confirm('¿Seguro que querés eliminar esta cancha?');">
                            <input type="hidden" name="borrarcancha" value="<?= (int)$cancha['id_cancha'] ?>">
                            <button type="submit">Borrar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No creaste ninguna cancha todavía.</p>
        <?php endif; ?>
    <?php endif; ?>

    <a href="index.php">Volver</a>
</body>
</html>
