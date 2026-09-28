<?php
require_once __DIR__ . '/../includes/auth.php';
if (is_logged_in()) {
    $role = current_role();
    $dest = is_admin_role() ? 'admin/dashboard.php' : ($role === 'doctor' ? 'doctor/dashboard.php' : 'citizen/dashboard.php');
    header("Location: " . base_url($dest));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['ref_id'] = $user['ref_id'];

            $refTable = ROLE_REF_TABLE[$user['role']] ?? null;
            $nr = null;
            if ($refTable) {
                $pkCol = 'admin_id';
                if ($refTable === 'Citizen') $pkCol = 'citizen_id';
                elseif ($refTable === 'Doctors') $pkCol = 'doctor_id';

                $n = $conn->prepare("SELECT name FROM `$refTable` WHERE `$pkCol` = ?");
                $n->bind_param('i', $user['ref_id']);
                $n->execute();
                $nr = $n->get_result()->fetch_assoc();
            }
            $_SESSION['name'] = $nr['name'] ?? $user['username'];

            if (is_admin_role($user['role'])) {
                $dest = 'admin/dashboard.php';
            } elseif ($user['role'] === 'doctor') {
                $dest = 'doctor/dashboard.php';
            } else {
                $dest = 'citizen/dashboard.php';
            }

            header("Location: " . base_url($dest));
            exit;
        } else {
            $error = "Incorrect username or password.";
        }
    } else {
        $error = "Incorrect username or password.";
    }
}

$page_title = "Sign In - UrbanPulse";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        emeraldGreen: '#10b981',
                        skyBlue: '#38bdf8'
                    }
                }
            }
        }
    </script>
    <style>
        .neon-card {
            background: rgba(10, 15, 30, 0.45);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(16, 185, 129, 0.4);
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.2), inset 0 0 15px rgba(56, 189, 248, 0.1);
        }
        .neon-input:focus {
            border-color: #10b981;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        }
        .neon-btn {
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            transition: all 0.3s ease;
        }
        .neon-btn:hover {
            box-shadow: 0 0 30px rgba(16, 185, 129, 0.7);
        }
    </style>
</head>
<body class="min-h-screen bg-[#070b14] text-slate-200 flex items-center justify-center p-4 relative overflow-hidden" style="background-image: linear-gradient(rgba(7,11,20,0.65), rgba(7,11,20,0.65)), url('../assets/images/traffic.jpg'); background-size: cover; background-position: center;">

    <!-- Neon Container with Background Blur Effect -->
    <div class="w-full max-w-md neon-card rounded-3xl p-8 md:p-10 space-y-6 z-10">
        
        <!-- Logo & Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center gap-2 text-2xl font-black text-white tracking-wide">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-sky-400 flex items-center justify-center text-white shadow-lg shadow-emerald-500/50">
                    <i class="fa-solid fa-city text-sm"></i>
                </div>
                <span>UrbanPulse <span class="w-2 h-2 rounded-full bg-emeraldGreen inline-block shadow-[0_0_10px_#10b981]"></span></span>
            </div>
            <h1 class="text-xl font-bold text-slate-200 tracking-wide">Sign In</h1>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="bg-rose-950/80 border border-rose-800 text-rose-300 text-xs p-3 rounded-xl text-center shadow-[0_0_10px_rgba(244,63,94,0.3)]">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered'])): ?>
            <div class="bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-xs p-3 rounded-xl text-center">
                Your account request has been submitted. You can log in once a Central Manager approves it.
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['reset'])): ?>
            <div class="bg-emerald-950/80 border border-emerald-800 text-emerald-300 text-xs p-3 rounded-xl text-center">
                Password reset successful. Please log in.
            </div>
        <?php endif; ?>

        <!-- Form Fields -->
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Username</label>
                <input type="text" name="username" required autofocus placeholder="Enter your username" 
                    class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white focus:outline-none neon-input transition-all">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Password</label>
                <input type="password" name="password" required placeholder="Enter your password" 
                    class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-3 text-sm text-white focus:outline-none neon-input transition-all">
            </div>

            <button type="submit" 
                class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-sky-400 hover:from-emerald-600 hover:to-sky-500 text-white font-bold rounded-xl text-sm neon-btn mt-2">
                Login
            </button>
        </form>

        <!-- Links and Demo Notes -->
        <div class="space-y-4 pt-2 text-center text-xs">
            <div class="flex justify-center items-center gap-2 text-slate-300">
                <a href="forgot_password.php" class="hover:text-emeraldGreen transition-colors">Forgot password?</a>
                <span>|</span>
                <a href="register.php" class="hover:text-emeraldGreen transition-colors">Create a citizen account</a>
            </div>

            <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-800/80 text-slate-400 text-[11px] leading-relaxed">
                Demo Accounts: Central Manager (<strong class="text-slate-200">alamin</strong> / <strong class="text-slate-200">admin123</strong>), Hospital Manager (<strong class="text-slate-200">drkarim.hospital</strong> / <strong class="text-slate-200">admin123</strong>), Doctor (<strong class="text-slate-200">drrafiq</strong> / <strong class="text-slate-200">doctor123</strong>), Citizen (<strong class="text-slate-200">rafiq</strong> / <strong class="text-slate-200">citizen123</strong>).
            </div>
        </div>

    </div>
</body>
</html>