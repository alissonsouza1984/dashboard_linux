<?php
session_start();
require_once '../config/db.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Verificar se o ID do usuário está presente na URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard_info.php"); // Redirecionar de volta à página de gerenciamento de usuários
    exit();
}

$user_id = $_SESSION['user_id'];
$id = $_GET['id'];

// Verificar se o usuário é um administrador
$sqlCheckAdmin = "SELECT is_admin FROM users WHERE id = :user_id";
$stmtCheckAdmin = $pdo->prepare($sqlCheckAdmin);
$stmtCheckAdmin->bindParam(":user_id", $user_id);
$stmtCheckAdmin->execute();
$currentUser = $stmtCheckAdmin->fetch(PDO::FETCH_ASSOC);

// Recuperar informações do usuário para pré-preencher o formulário
$sql = "SELECT * FROM users WHERE id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(":id", $id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Usuário não encontrado");
}

$errors = [];
$successMessage = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capturar os campos do formulário de edição
    $newUsername = $_POST["new_username"];
    $newPassword = $_POST["new_password"];
    $newEmail = $_POST["new_email"];
    $isAdmin = isset($_POST["is_admin"]) ? 1 : 0; // Verificar se o administrador foi marcado

    // Validações (você pode adicionar mais validações conforme necessário)
    if (empty($newUsername)) {
        $errors[] = "O nome de usuário não pode estar vazio";
    }

    if (empty($newEmail)) {
        $errors[] = "O email não pode estar vazio";
    } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "O email é inválido";
    }

    if ($user_id === $user['id'] || $currentUser['is_admin'] === '1') {
        // Se o usuário atual estiver editando a si mesmo ou for um administrador,
        // permitir a alteração da permissão de administrador
        if (count($errors) === 0) {
            // Hash a nova senha antes de atualizar
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            // Atualizar as informações do usuário no banco de dados, incluindo a flag de administrador
            $updateSql = "UPDATE users SET username = :username, ";
            if (!empty($newPassword)) {
                $updateSql .= "password = :password, ";
            }
            $updateSql .= "email = :email, is_admin = :is_admin WHERE id = :id";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(":username", $newUsername);
            if (!empty($newPassword)) {
                $updateStmt->bindParam(":password", $hashedPassword);
            }
            $updateStmt->bindParam(":email", $newEmail);
            $updateStmt->bindParam(":is_admin", $isAdmin);
            $updateStmt->bindParam(":id", $id);
            $updateStmt->execute();

            $successMessage = "Alterações feitas com sucesso.";
        }
    } else {
        // Se o usuário atual não tiver permissão para alterar a permissão de administrador,
        // atualize apenas as informações do usuário, excluindo a flag de administrador
        if (count($errors) === 0) {
            // Hash a nova senha antes de atualizar
            if (!empty($newPassword)) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            // Atualizar as informações do usuário no banco de dados, excluindo a flag de administrador
            $updateSql = "UPDATE users SET username = :username, ";
            if (!empty($newPassword)) {
                $updateSql .= "password = :password, ";
            }
            $updateSql .= "email = :email WHERE id = :id";

            $updateStmt = $pdo->prepare($updateSql);
            $updateStmt->bindParam(":username", $newUsername);
            if (!empty($newPassword)) {
                $updateStmt->bindParam(":password", $hashedPassword);
            }
            $updateStmt->bindParam(":email", $newEmail);
            $updateStmt->bindParam(":id", $id);
            $updateStmt->execute();

            $successMessage = "Alterações feitas com sucesso.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Dashboard Bluepex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Editar Usuário</h2>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?php echo $successMessage; ?></div>
            <script>
                setTimeout(function() {
                    window.location.href = "dashboard_info.php";
                }, 2000); // Atraso de 2 segundos
            </script>
        <?php endif; ?>
        <?php if (count($errors) > 0): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form action="" method="post">
            <div class="mb-3">
                <label for="new_username" class="form-label">Novo Nome de Usuário</label>
                <input type="text" class="form-control" id="new_username" name="new_username" value="<?php echo $user['username']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">Nova Senha</label>
                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Deixe em branco para manter a senha atual">
            </div>
            <div class="mb-3">
                <label for="new_email" class="form-label">Novo Email</label>
                <input type="email" class="form-control" id="new_email" name="new_email" value="<?php echo $user['email']; ?>" required>
            </div>
            <!-- Não permitir que o usuário atual selecione a flag "administrador" -->
            <?php if ($user_id !== $user['id'] || $currentUser['is_admin'] === '1'): ?>
                <div class="mb-3">
                    <input type="checkbox" id="is_admin" name="is_admin" <?php if ($user['is_admin'] === '1') { echo 'checked'; } ?>>
                    <label for="is_admin">É Administrador</label>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            <a href="dashboard_info.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</body>
</html>
