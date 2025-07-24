<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Lista de Tarefas</title>
    <style>
        /* Estilos básicos para a página */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #0056b3;
        }
        form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        form input[type="text"] {
            flex-grow: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        form button {
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        form button:hover {
            background-color: #0056b3;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            background-color: #e9e9e9;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        li.completed {
            text-decoration: line-through;
            color: #888;
        }
        .actions button {
            margin-left: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .actions .complete-btn {
            background-color: #28a745;
            color: white;
        }
        .actions .complete-btn:hover {
            background-color: #218838;
        }
        .actions .edit-btn {
            background-color: #ffc107;
            color: white;
        }
        .actions .edit-btn:hover {
            background-color: #e0a800;
        }
        .actions .delete-btn {
            background-color: #dc3545;
            color: white;
        }
        .actions .delete-btn:hover {
            background-color: #c82333;
        }
        .message {
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Minha Lista de Tarefas</h1>

        <?php
        // --- Configuração do Banco de Dados para MySQL/MariaDB (XAMPP) ---
        // Altere essas variáveis de acordo com as suas credenciais do MySQL/MariaDB
        $host = "localhost";
        $dbname = "todo_list_db"; // Nome do seu banco de dados
        $user = "root";          // Usuário padrão do XAMPP MySQL (geralmente 'root')
        $password = "";          // Senha padrão do XAMPP MySQL (geralmente vazia)

        // Variável para armazenar a conexão com o banco de dados
        $pdo = null;
        $message = '';
        $error = '';

        try {
            // Conexão com o MySQL usando PDO
            // Adicionado 'charset=utf8mb4' para melhor suporte a caracteres
            $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Habilita o lançamento de exceções em caso de erro
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Define o modo de busca padrão como array associativo
                PDO::ATTR_EMULATE_PREPARES => false,                // Recomendado para segurança e tratamento correto de tipos
            ]);
            // echo "<div class='message'>Conexão com o banco de dados estabelecida com sucesso!</div>"; // Mensagem de sucesso na conexão (opcional)
        } catch (PDOException $e) {
            $error = "Erro ao conectar ao banco de dados: " . $e->getMessage();
            // Para depuração, você pode exibir o erro completo:
            // die("Erro ao conectar ao banco de dados: " . $e->getMessage());
        }

        // --- Criação da Tabela (se não existir) para MySQL ---
        if ($pdo) {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
                    id INT AUTO_INCREMENT PRIMARY KEY,   -- Alterado de SERIAL PRIMARY KEY (PostgreSQL) para INT AUTO_INCREMENT PRIMARY KEY (MySQL)
                    description VARCHAR(255) NOT NULL,
                    completed BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            } catch (PDOException $e) {
                $error = "Erro ao criar tabela: " . $e->getMessage();
            }
        }

        // --- Processamento de Ações do Formulário ---
        if ($pdo) {
            // Adicionar Tarefa
            if (isset($_POST['add_task']) && !empty($_POST['description'])) {
                $description = trim($_POST['description']);
                try {
                    $stmt = $pdo->prepare("INSERT INTO tasks (description) VALUES (?)");
                    $stmt->execute([$description]);
                    $message = "Tarefa adicionada com sucesso!";
                } catch (PDOException $e) {
                    $error = "Erro ao adicionar tarefa: " . $e->getMessage();
                }
            }

            // Atualizar Tarefa (Completar/Descompletar)
            if (isset($_POST['toggle_complete'])) {
                $id = (int)$_POST['id'];
                // Para MySQL, 'TRUE'/'FALSE' são mais robustos que 0/1 para BOOLEAN em alguns contextos, ou simplesmente `NOT completed`
                // A sua lógica original com `TRUE`/`FALSE` já é compatível com MySQL BOOLEAN (que internamente é TinyINT(1)).
                $completed = filter_var($_POST['completed_status'], FILTER_VALIDATE_BOOLEAN); // Converte para booleano
                $newStatus = $completed ? 'FALSE' : 'TRUE'; // Inverte o status
                try {
                    $stmt = $pdo->prepare("UPDATE tasks SET completed = $newStatus WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Status da tarefa atualizado!";
                } catch (PDOException $e) {
                    $error = "Erro ao atualizar status da tarefa: " . $e->getMessage();
                }
            }

            // Editar Tarefa
            if (isset($_POST['edit_task']) && !empty($_POST['edit_description']) && isset($_POST['edit_id'])) {
                $id = (int)$_POST['edit_id'];
                $description = trim($_POST['edit_description']);
                try {
                    $stmt = $pdo->prepare("UPDATE tasks SET description = ? WHERE id = ?");
                    $stmt->execute([$description, $id]);
                    $message = "Tarefa atualizada com sucesso!";
                } catch (PDOException $e) {
                    $error = "Erro ao editar tarefa: " . $e->getMessage();
                }
            }

            // Excluir Tarefa
            if (isset($_POST['delete_task'])) {
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = "Tarefa excluída com sucesso!";
                } catch (PDOException $e) {
                    $error = "Erro ao excluir tarefa: " . $e->getMessage();
                }
            }
        }

        // --- Exibição de Mensagens ---
        if ($message) {
            echo "<div class='message'>$message</div>";
        }
        if ($error) {
            echo "<div class='message error'>$error</div>";
        }
        ?>

        <h2>Adicionar Nova Tarefa</h2>
        <form action="" method="POST">
            <input type="text" name="description" placeholder="Digite uma nova tarefa..." required>
            <button type="submit" name="add_task">Adicionar</button>
        </form>

        <h2>Tarefas Existentes</h2>
        <?php
        if ($pdo) {
            try {
                // Selecionar todas as tarefas do banco de dados, ordenadas por data de criação
                $stmt = $pdo->query("SELECT id, description, completed FROM tasks ORDER BY created_at DESC");
                $tasks = $stmt->fetchAll();

                if (count($tasks) > 0) {
                    echo "<ul>";
                    foreach ($tasks as $task) {
                        $completedClass = $task['completed'] ? 'completed' : '';
                        echo "<li class='$completedClass'>";
                        echo "<span>" . htmlspecialchars($task['description']) . "</span>";
                        echo "<div class='actions'>";
                        // Formulário para marcar/desmarcar como completa
                        echo "<form action='' method='POST' style='display:inline;'>";
                        echo "<input type='hidden' name='id' value='" . $task['id'] . "'>";
                        echo "<input type='hidden' name='completed_status' value='" . ($task['completed'] ? 'true' : 'false') . "'>"; // Passa o status atual
                        echo "<button type='submit' name='toggle_complete' class='complete-btn'>" . ($task['completed'] ? 'Desfazer' : 'Concluir') . "</button>";
                        echo "</form>";
                        // Botão para editar (que pode abrir um modal ou formulário JS)
                        echo "<button class='edit-btn' onclick='editTask(" . $task['id'] . ", \"" . htmlspecialchars($task['description'], ENT_QUOTES) . "\")'>Editar</button>";
                        // Formulário para excluir
                        echo "<form action='' method='POST' style='display:inline;'>";
                        echo "<input type='hidden' name='id' value='" . $task['id'] . "'>";
                        echo "<button type='submit' name='delete_task' class='delete-btn' onclick='return confirm(\"Tem certeza que deseja excluir esta tarefa?\")'>Excluir</button>";
                        echo "</form>";
                        echo "</div>";
                        echo "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>Nenhuma tarefa adicionada ainda.</p>";
                }
            } catch (PDOException $e) {
                echo "<div class='message error'>Erro ao carregar tarefas: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='message error'>Não foi possível carregar as tarefas devido a um erro de conexão com o banco de dados.</div>";
        }
        ?>

        <div id="editFormContainer" style="display: none; margin-top: 20px; padding: 15px; background-color: #f9f9f9; border: 1px solid #eee; border-radius: 5px;">
            <h2>Editar Tarefa</h2>
            <form action="" method="POST">
                <input type="hidden" id="edit_id" name="edit_id">
                <input type="text" id="edit_description" name="edit_description" placeholder="Editar tarefa..." required>
                <button type="submit" name="edit_task">Salvar Edição</button>
                <button type="button" onclick="cancelEdit()">Cancelar</button>
            </form>
        </div>

    </div>

    <script>
        // Função JavaScript para exibir o formulário de edição
        function editTask(id, description) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_description').value = description;
            document.getElementById('editFormContainer').style.display = 'block';
        }

        // Função JavaScript para cancelar a edição
        function cancelEdit() {
            document.getElementById('editFormContainer').style.display = 'none';
            document.getElementById('edit_id').value = '';
            document.getElementById('edit_description').value = '';
        }
    </script>
</body>
</html>
