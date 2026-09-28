<?php
require_once __DIR__ . '/../includes/auth.php';
if (is_logged_in()) {
    header("Location: " . base_url(is_admin_role() ? 'admin/dashboard.php' : 'citizen/dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $contact_no = trim($_POST['contact_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $security_question = trim($_POST['security_question'] ?? '');
    $security_answer = strtolower(trim($_POST['security_answer'] ?? ''));

    if ($name === '' || $username === '' || $password === '' || $security_question === '' || $security_answer === '') {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        // Username must be free in both the live Users table and the pending-requests queue
        $check = $conn->prepare("SELECT user_id FROM Users WHERE username = ?");
        $check->bind_param('s', $username);
        $check->execute();
        $taken = $check->get_result()->num_rows > 0;

        if (!$taken) {
            $check2 = $conn->prepare("SELECT request_id FROM Citizen_Registration_Requests WHERE username = ? AND status = 'Pending'");
            $check2->bind_param('s', $username);
            $check2->execute();
            $taken = $check2->get_result()->num_rows > 0;
        }

        if ($taken) {
            $error = "That username is already taken or already has a pending request.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $answerHash = password_hash($security_answer, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO Citizen_Registration_Requests
                (name, age, gender, address, contact_no, email, username, password, security_question, security_answer)
                VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sissssssss', $name, $age, $gender, $address, $contact_no, $email, $username, $hash, $security_question, $answerHash);
            $stmt->execute();

            header("Location: login.php?registered=1");
            exit;
        }
    }
}

$page_title = "Create Account - UrbanPulse";
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
    <style>
        .neon-card {
            background: rgba(10, 15, 30, 0.55);
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
<body class="min-h-screen bg-[#070b14] text-slate-200 flex items-center justify-center p-4 relative overflow-y-auto py-10" style="background-image: linear-gradient(rgba(7,11,20,0.7), rgba(7,11,20,0.7)), url('../assets/images/ai.jpg'); background-size: cover; background-position: center; background-attachment: fixed;">

    <!-- Neon Container -->
    <div class="w-full max-w-lg neon-card rounded-3xl p-8 md:p-10 space-y-6 z-10 my-auto">
        
        <!-- Logo & Header -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center gap-2 text-2xl font-black text-white tracking-wide">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-emerald-500 to-sky-400 flex items-center justify-center text-white shadow-lg shadow-emerald-500/50">
                    <i class="fa-solid fa-city text-sm"></i>
                </div>
                <span>UrbanPulse <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block shadow-[0_0_10px_#10b981]"></span></span>
            </div>
            <h1 class="text-xl font-bold text-slate-200 tracking-wide">Citizen Sign Up</h1>
            <p class="text-xs text-slate-400">Your request will be reviewed by a Central Manager. You'll be able to log in once it's approved.</p>
        </div>

        <!-- Alerts -->
        <?php if (!empty($error)): ?>
            <div class="bg-rose-950/80 border border-rose-800 text-rose-300 text-xs p-3 rounded-xl text-center shadow-[0_0_10px_rgba(244,63,94,0.3)]">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Form Fields -->
        <form method="POST" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Full Name *</label>
                    <input type="text" name="name" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" placeholder="Enter full name" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Age</label>
                    <input type="number" name="age" min="0" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" placeholder="Age" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Gender</label>
                    <select name="gender" class="w-full bg-slate-900/80 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                        <option value="Male" <?php echo (($_POST['gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (($_POST['gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (($_POST['gender'] ?? '') === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Contact No</label>
                    <input type="text" name="contact_no" value="<?php echo htmlspecialchars($_POST['contact_no'] ?? ''); ?>" placeholder="Phone number" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Address</label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" placeholder="Enter address" 
                    class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="name@example.com" 
                    class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Username *</label>
                    <input type="text" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" placeholder="Choose a username" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Security Question *</label>
                    <input type="text" name="security_question" required value="<?php echo htmlspecialchars($_POST['security_question'] ?? ''); ?>" placeholder="e.g. Favorite color?" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Password *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Min 6 characters" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Confirm Password *</label>
                    <input type="password" name="confirm_password" required minlength="6" placeholder="Re-enter password" 
                        class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Answer *</label>
                <input type="text" name="security_answer" required placeholder="Answer to security question" 
                    class="w-full bg-slate-900/60 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-white focus:outline-none neon-input transition-all">
            </div>

            <button type="submit" 
                class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-sky-400 hover:from-emerald-600 hover:to-sky-500 text-white font-bold rounded-xl text-sm neon-btn mt-2">
                Submit for Approval
            </button>
        </form>

        <!-- Footer link -->
        <div class="text-center text-xs pt-2 text-slate-300">
            Already have an account? <a href="login.php" class="text-emerald-400 hover:underline font-semibold">Log in</a>
        </div>

    </div>

</body>
</html>