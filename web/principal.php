<?php
// register_client.php

// ----------------------------
// 🔧 Database Configuration
// ----------------------------
$host = "mysql_server";   // Change to 127.0.0.1 if not using Docker
$user = "radius";
$pass = "dalodbpass";
$db   = "radius";

// ----------------------------
// 🔌 Database Connection
// ----------------------------
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("<div class='error'>❌ Database connection failed: " . htmlspecialchars($conn->connect_error) . "</div>");
}

// ----------------------------
// 🧾 Get MAC from URL
// ----------------------------
$mac = isset($_GET['mac']) ? htmlspecialchars($_GET['mac']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ----------------------------
    // 📥 Get Form Data
    // ----------------------------
    $nombre   = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $cedula   = $_POST['cedula'];
    $telefono = $_POST['telefono'];
    $email    = $_POST['email'];
    $mac      = $_POST['mac']; // Hidden field

    // ----------------------------
    // 🕵️ Check if MAC already registered
    // ----------------------------
    $check = $conn->prepare("SELECT id FROM clients WHERE mac = ?");
    if (!$check) {
        die("<div class='error'>Prepare failed (check): " . htmlspecialchars($conn->error) . "</div>");
    }
    $check->bind_param("s", $mac);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo "<script>alert('⚠️ This device is already registered.');</script>";
    } else {
        // ----------------------------
        // 🧩 Insert into clients table
        // ----------------------------
        $query = "INSERT INTO clients (nombre, apellido, cedula, telefono, email, mac, enabled)
                  VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            die("<div class='error'>Prepare failed (clients): " . htmlspecialchars($conn->error) . "</div>");
        }
        $stmt->bind_param("ssssss", $nombre, $apellido, $cedula, $telefono, $email, $mac);

        if ($stmt->execute()) {
            // ----------------------------
            // ✅ Also insert into radcheck
            // ----------------------------
            $rad = $conn->prepare("INSERT INTO radcheck (username, attribute, op, value) VALUES (?, 'Auth-Type', ':=', 'Accept')");
            if (!$rad) {
                die("<div class='error'>Prepare failed (radcheck): " . htmlspecialchars($conn->error) . "</div>");
            }
            $rad->bind_param("s", $mac);
            if ($rad->execute()) {
                echo "<script>alert('✅ Device registered successfully! You can now connect.'); window.location='success.html';</script>";
            } else {
                echo "<div class='error'>Error inserting into radcheck: " . htmlspecialchars($rad->error) . "</div>";
            }
        } else {
            echo "<div class='error'>Error inserting into clients: " . htmlspecialchars($stmt->error) . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Client Registration</title>

<style>
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; background: #f4f4f4; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 10px; }
.top-image, .bottom-image { width: 100%; max-width: 400px; border-radius: 10px; }
.form-container { background: white; padding: 25px 20px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.15); width: 100%; max-width: 400px; margin: 15px 0; }
h2 { color: #333; text-align: center; margin-bottom: 20px; font-size: 1.4rem; }
input { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #ddd; border-radius: 8px; font-size: 1rem; }
button { width: 100%; padding: 14px; background: #667eea; color: white; border: none; border-radius: 8px; font-size: 1.05rem; cursor: pointer; margin-top: 10px; transition: background 0.3s ease; }
button:hover { background: #5568d3; }
.error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 8px; margin: 10px 0; text-align: center; font-size: 0.9rem; display: block; }
@media (max-width: 480px) { .form-container { padding: 20px 15px; border-radius: 10px; } input, button { font-size: 1rem; } h2 { font-size: 1.3rem; } }
</style>
</head>
<body>

    <!-- Top banner -->
    <img src="top_banner.jpg" alt="Top Banner" class="top-image">

    <div class="form-container">
        <h2>Register to Access Wi-Fi</h2>
        <form method="POST">
            <input type="text" name="nombre" placeholder="First Name" required>
            <input type="text" name="apellido" placeholder="Last Name" required>
            <input type="text" name="cedula" placeholder="ID / Cedula" required>
            <input type="text" name="telefono" placeholder="Phone Number" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="hidden" name="mac" value="<?php echo $mac; ?>">

            <button type="submit">Register</button>
        </form>
    </div>

    <!-- Bottom banner -->
    <img src="bottom_banner.jpg" alt="Bottom Banner" class="bottom-image">

</body>
</html>
