<?php
require_once '../../config.php';
require_once '../../includes/language.php';

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $id_number = trim($_POST['id_number']);
    $experience = trim($_POST['experience']);
    $message = trim($_POST['message']);
    
    // Validation
    if (empty($full_name)) {
        $errors[] = $current_lang === 'rw' ? 'Amazina ni ngombwa' : 'Full name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = $current_lang === 'rw' ? 'Email ntabwo ari yo' : 'Valid email is required';
    }
    
    if (empty($phone)) {
        $errors[] = $current_lang === 'rw' ? 'Telefoni ni ngombwa' : 'Phone is required';
    }
    
    // Check if already applied
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM agent_applications WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = $current_lang === 'rw' ? 'Usanzwe wasabye' : 'You have already applied';
        }
    }
    
    // Save application
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO agent_applications (full_name, email, phone, address, id_number, experience, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        
        if ($stmt->execute([$full_name, $email, $phone, $address, $id_number, $experience, $message])) {
            $success = $current_lang === 'rw' ? 'Ubusabe bwawe bwoherejwe neza! Tuzakuhamagara vuba.' : 'Application submitted successfully! We will contact you soon.';
        } else {
            $errors[] = $current_lang === 'rw' ? 'Hari ikosa ryabaye' : 'An error occurred';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_lang === 'rw' ? 'Saba kuba Agent' : 'Apply as Agent'; ?> - URUHUSHYA</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .apply-container {
            max-width: 700px;
            margin: 60px auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .apply-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .apply-header h1 {
            color: #1e3a8a;
            margin-bottom: 8px;
        }
        .apply-header p {
            color: #6b7280;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1f2937;
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <div class="apply-container">
        <div class="apply-header">
            <h1><?php echo $current_lang === 'rw' ? 'Saba kuba Agent' : 'Apply as Agent'; ?></h1>
            <p><?php echo $current_lang === 'rw' ? 'Uzuza ifishi hasi maze twoherere' : 'Fill out the form below and we\'ll contact you'; ?></p>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <p>✅ <?php echo $success; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach($errors as $error): ?>
                    <p>❌ <?php echo $error; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Amazina Yawe Yose *' : 'Full Name *'; ?></label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Email *' : 'Email *'; ?></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Telefoni *' : 'Phone *'; ?></label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
            </div>
            
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Aderesi' : 'Address'; ?></label>
                <input type="text" name="address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Numero y\'Indangamuntu' : 'ID Number'; ?></label>
                <input type="text" name="id_number" value="<?php echo htmlspecialchars($_POST['id_number'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Uburambe bwawe' : 'Your Experience'; ?></label>
                <textarea name="experience"><?php echo htmlspecialchars($_POST['experience'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label><?php echo $current_lang === 'rw' ? 'Ubutumwa' : 'Message'; ?></label>
                <textarea name="message"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px;">
                <?php echo $current_lang === 'rw' ? 'Ohereza Ubusabe' : 'Submit Application'; ?>
            </button>
        </form>
        
        <p style="text-align: center; margin-top: 24px;">
            <a href="../../index.php" style="color: #2563eb;">← <?php echo $current_lang === 'rw' ? 'Subira Ahabanza' : 'Back to Home'; ?></a>
        </p>
    </div>
</body>
</html>