<?php
session_start();
include 'db.php';

ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);

// Función para verificar si un archivo ya existe para el usuario actual
function checkExistingFile($file_name, $file_size, $file_type) {
    global $db_connection;

    $query = "SELECT unique_name FROM Files WHERE name = ? AND size = ? AND file_type = ? AND user_id = ?";
    $stmt = $db_connection->prepare($query);
    $user_id = $_SESSION['user_id']; // Obtener el ID de usuario de la sesión
    $stmt->bind_param("sisi", $file_name, $file_size, $file_type, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($unique_name);
        $stmt->fetch();
        $part_directory = "files/".$_SESSION['user_id']."/";
        return true;//$part_directory . "/" . $unique_name . "_part_";
    } else {
        return false;
    }

    $stmt->close();
}


// Función para obtener el nombre único del archivo
function getUniqueFileName($file_name, $file_size, $file_type) {
    global $db_connection;
    $user_id = $_SESSION['user_id'];
    $query = "SELECT unique_name FROM Files WHERE name = ? AND size = ? AND file_type = ? AND user_id = ?";
    $stmt = $db_connection->prepare($query);
    $stmt->bind_param("sisi", $file_name, $file_size, $file_type, $user_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($unique_name);
        $stmt->fetch();
        $stmt->close();
        return $unique_name;
    } else {
        $stmt->close();
        return false;
    }
}

// Función para insertar un nuevo registro de archivo en la base de datos
function insertFileRecord($file_name, $file_size, $file_type, $encryption_key) {
    global $db_connection;
    $user_id = $_SESSION['user_id'];
    $query = "SELECT COUNT(*) AS count FROM Files WHERE name = ? AND user_id = ?";
    $stmt = $db_connection->prepare($query);
    $stmt->bind_param("si", $fileName, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['count'];

        if ($count == 0) {
        $file_path = "files/".$_SESSION['user_id']."/";
        $insert_query = "INSERT INTO Files (name, unique_name, size, file_type, status, status_upload, user_id, file_path, active, encryption_key) VALUES (?, ?, ?, ?, ?,'Binary', ?,?,1,?)";
        $stmt = $db_connection->prepare($insert_query);
        $unique_name = uniqid();
        $status = "Procesando"; 
        $stmt->bind_param("ssississ", $file_name, $unique_name, $file_size, $file_type, $status, $user_id, $file_path ,$encryption_key);
        $stmt->execute();
        $stmt->close();
    } 
}

function getEncryptionKey($file_name, $file_size, $file_type) {
    global $db_connection;
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT encryption_key FROM Files WHERE name = ? AND size = ? AND file_type = ? AND user_id = ?";
    $stmt = $db_connection->prepare($sql);
    $stmt->bind_param("sisi", $file_name, $file_size, $file_type, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $encryption_key = $row['encryption_key'];
    } else {
        $encryption_key = null; 
    }
    $stmt->close();
    return $encryption_key;
}

// Función para dividir el archivo en partes y guardarlas en el servidor
function divideFileIntoParts($file_tmp, $file_name, $file_size, $file_type, $encryption_key) {
    $chunk_size = 512 * 1024;
    $part_number = 1;
    $unique_name = getUniqueFileName($file_name, $file_size, $file_type);
    $part_directory = "files/" . $_SESSION['user_id'] . "/"; 
    $file_handle = fopen($file_tmp, "rb");

    if ($file_handle) {
        if (!file_exists($part_directory)) {
            mkdir($part_directory, 0777, true);
        }

        while (!feof($file_handle)) {
            $chunk = fread($file_handle, $chunk_size);
            if ($chunk !== false) {
                $compressedChunk = compressAndEncryptData($chunk, $encryption_key);
                $part_filename = $part_directory . $unique_name . "_part_" . $part_number . ".bin";
                file_put_contents($part_filename, $compressedChunk);
                $part_number++;
            }
        }

        if (fclose($file_handle)) {
            updateFileStatusToCompleted($file_name, $file_size, $file_type);
        } else {
            updateFileStatusToError($file_name, $file_size, $file_type);
        }

    } else {
        throw new Exception("Error: No se pudo abrir el archivo.");
    }
}

// Función para comprimir datos utilizando el algoritmo zip
function compressAndEncryptData($data, $encryption_key = null) {
    if (!empty($encryption_key)) {
        $data = encryptData($data, $encryption_key);
    }

    // Comprimir los datos utilizando el algoritmo DEFLATE
    $compressedData = gzcompress($data, 9); // Nivel de compresión máximo
    
    if ($compressedData === false) {
        throw new Exception("Error: No se pudo comprimir los datos.");
    }
    
    return $compressedData;
}

// Función para cifrar datos utilizando la clave de cifrado
function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    $result = $iv . $encrypted;
    return $result;
}

// Función para generate Encryption Key
function generateEncryptionKey($length = 32) {
    $chars = '0123456789bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ!#$%&()*+,-./:;<=>?@[\]^_{|}~';
    $chars_length = strlen($chars);
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $chars[random_int(0, $chars_length - 1)];
    }
    return $key;
}


// Función para actualizar el estado de un archivo a "completado"
function updateFileStatusToCompleted($file_name, $file_size, $file_type) {
    global $db_connection;
    $sql = "UPDATE Files SET status = 'Completo' WHERE name = ? AND size = ? AND file_type = ? AND user_id = ?";
    $stmt = $db_connection->prepare($sql);
    $stmt->bind_param("sisi", $file_name, $file_size, $file_type, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $db_connection->close();
    
}

// Función para actualizar el estado de un archivo a "completado"
function updateFileStatusToError($file_name, $file_size, $file_type) {
    global $db_connection;
    $sql = "UPDATE Files SET status = 'Error' WHERE name = ? AND size = ? AND file_type = ? AND user_id = ?";
    $stmt = $db_connection->prepare($sql);
    $stmt->bind_param("sisi", $file_name, $file_size, $file_type, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    $db_connection->close();
    
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["file"])) {
   
    $file = $_FILES["file"];
    $file_name = $file["name"];
    $file_tmp = $file["tmp_name"];
    $file_size = $file["size"];
    $file_type = $file["type"];

    if (!checkExistingFile($file_name, $file_size, $file_type)) {
        $encryption_key = generateEncryptionKey(32);
        insertFileRecord($file_name, $file_size, $file_type, $encryption_key);
        divideFileIntoParts($file_tmp, $file_name, $file_size, $file_type, $encryption_key);
    } else {
        $encryption_key = getEncryptionKey($file_name, $file_size, $file_type);
        divideFileIntoParts($file_tmp, $file_name, $file_size, $file_type, $encryption_key);
    }
   header("Location: fdropzone.php");
   exit;
}

?>

