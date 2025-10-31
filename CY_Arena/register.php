<?php
include 'db_connect.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = $_POST['firstname'];
    $lastname  = $_POST['lastname'];
    $email     = $_POST['email'];
    $phone     = $_POST['phone'];
    $username  = $_POST['username'];
    $password_plain = $_POST['password'];
    
    // ตรวจสอบเบอร์โทรศัพท์
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $message = "❌ เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลักเท่านั้น";
    } else {
        $password_hashed = password_hash($password_plain, PASSWORD_DEFAULT);

        $sql = "INSERT INTO tbl_customer (FirstName, LastName, Email, Phone, Username, Password) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $firstname, $lastname, $email, $phone, $username, $password_hashed);

        try {
            if ($stmt->execute()) {
                $message = "✅ สมัครสมาชิกสำเร็จ! โปรดเข้าสู่ระบบ";
            }
        } catch (mysqli_sql_exception $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                $message = "⚠️ อีเมล, เบอร์โทร หรือ Username นี้ถูกใช้ไปแล้ว";
            } else {
                $message = "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
            }
        }

        $stmt->close();
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก | CY Arena</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&family=Kanit:wght@700;800&display=swap" rel="stylesheet">
<style>
:root {
  --primary: #2563eb;
  --primary-dark: #1e40af;
  --primary-light: #3b82f6;
  --secondary: #eab308;
  --gray-50: #fafaf9;
  --gray-100: #f5f5f4;
  --gray-700: #44403c;
  --gray-900: #1c1917;
  --danger: #dc2626;
}

body {
  margin: 0;
  font-family: 'Sarabun', sans-serif;
  background: linear-gradient(135deg, var(--primary-dark), var(--primary));
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  color: var(--gray-900);
  padding: 1.5rem;
  box-sizing: border-box;
}

/* ===== REGISTER CARD ===== */
.register-card {
  background: white;
  border-radius: 20px;
  padding: 2.5rem;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  animation: fadeIn 0.8s ease-out;
}

@keyframes fadeIn {
  from {opacity:0; transform:translateY(30px);}
  to {opacity:1; transform:translateY(0);}
}

/* ===== LOGO ===== */
.logo {
  text-align: center;
  margin-bottom: 1.5rem;
}
.logo-icon {
  width: 70px;
  height: 70px;
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  border-radius: 16px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
  transition: transform 0.3s ease;
}
.logo-icon:hover {
  transform: scale(1.05);
}
.logo-text {
  font-family: 'Kanit', sans-serif;
  font-weight: 900;
  font-size: 1.75rem;
  color: var(--primary);
  margin-top: 0.5rem;
}

/* ===== FORM ===== */
h2 {
  text-align: center;
  font-weight: 800;
  font-family: 'Kanit', sans-serif;
  color: var(--gray-900);
  margin-bottom: 1.25rem;
}

p.desc {
  text-align: center;
  color: var(--gray-700);
  margin-bottom: 2rem;
}

.form-group {
  margin-bottom: 1rem;
}
label {
  display: block;
  font-weight: 700;
  margin-bottom: 0.5rem;
}
input {
  width: 100%;
  padding: 0.875rem 1rem;
  border: 2px solid var(--gray-100);
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s;
  box-sizing: border-box;
}
input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
  outline: none;
}

input.error-input {
  border-color: var(--danger);
}

.input-hint {
  font-size: 0.875rem;
  color: var(--gray-700);
  margin-top: 0.25rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.input-error {
  font-size: 0.875rem;
  color: var(--danger);
  margin-top: 0.25rem;
  display: none;
  font-weight: 600;
}

/* ===== BUTTON ===== */
.btn {
  width: 100%;
  padding: 1rem;
  font-weight: 800;
  font-family: 'Kanit', sans-serif;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 1.125rem;
}

.btn-primary {
  background: linear-gradient(135deg, var(--primary), var(--primary-light));
  color: white;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
}
.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.6);
}

.message {
  margin-top: 1rem;
  padding: 0.875rem;
  border-radius: 12px;
  text-align: center;
  font-weight: 700;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-dark);
  border: 2px solid var(--primary-light);
}

.error {
  margin-top: 1rem;
  padding: 0.875rem;
  border-radius: 12px;
  text-align: center;
  font-weight: 700;
  background: rgba(220, 38, 38, 0.1);
  color: var(--danger);
  border: 2px solid var(--danger);
}

.footer-text {
  text-align: center;
  margin-top: 1.75rem;
  color: var(--gray-700);
  font-weight: 600;
}
.footer-text a {
  color: var(--primary);
  text-decoration: none;
  font-weight: 700;
}
.footer-text a:hover { text-decoration: underline; }

@media (max-width: 480px) {
  body { padding: 0; }
  .register-card {
    border-radius: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
}
</style>
</head>
<body>

<div class="register-card">
  <div class="logo">
    <div class="logo-icon">⚽</div>
    <div class="logo-text">CY ARENA</div>
  </div>

  <h2>สมัครสมาชิก</h2>
  <p class="desc">กรอกข้อมูลของคุณเพื่อสร้างบัญชีใหม่</p>

  <?php if (!empty($message)): ?>
    <div class="<?php echo (str_contains($message, '❌') || str_contains($message, '⚠️')) ? 'error' : 'message'; ?>">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <form method="POST" id="registerForm">
    <div class="form-group">
      <label>👤 ชื่อจริง</label>
      <input type="text" name="firstname" id="firstname" required placeholder="กรอกชื่อจริง">
    </div>
    <div class="form-group">
      <label>👤 นามสกุล</label>
      <input type="text" name="lastname" id="lastname" required placeholder="กรอกนามสกุล">
    </div>
    <div class="form-group">
      <label>📧 อีเมล</label>
      <input type="email" name="email" id="email" required placeholder="example@email.com">
    </div>
    <div class="form-group">
      <label>📱 เบอร์โทรศัพท์</label>
      <input type="text" name="phone" id="phone" required placeholder="0812345678" maxlength="10" pattern="[0-9]{10}">
      <div class="input-hint">💡 ตัวเลข 10 หลักเท่านั้น (เช่น 0812345678)</div>
      <div class="input-error" id="phoneError">❌ เบอร์โทรต้องเป็นตัวเลข 10 หลักเท่านั้น</div>
    </div>
    <div class="form-group">
      <label>🔑 ชื่อผู้ใช้ (Username)</label>
      <input type="text" name="username" id="username" required placeholder="กรอกชื่อผู้ใช้">
    </div>
    <div class="form-group">
      <label>🔒 รหัสผ่าน</label>
      <input type="password" name="password" id="password" required placeholder="กรอกรหัสผ่าน" minlength="6">
      <div class="input-hint">💡 รหัสผ่านอย่างน้อย 6 ตัวอักษร</div>
    </div>

    <button type="submit" class="btn btn-primary">สมัครสมาชิก 🚀</button>
  </form>

  <div class="footer-text">
    มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบที่นี่</a>
  </div>
</div>

<script>
// ตรวจสอบเบอร์โทรศัพท์แบบ Real-time
const phoneInput = document.getElementById('phone');
const phoneError = document.getElementById('phoneError');
const registerForm = document.getElementById('registerForm');

phoneInput.addEventListener('input', function(e) {
  // ลบตัวอักษรที่ไม่ใช่ตัวเลขออก
  this.value = this.value.replace(/[^0-9]/g, '');
  
  // ตรวจสอบความถูกต้อง
  if (this.value.length > 0 && this.value.length !== 10) {
    phoneError.style.display = 'block';
    this.classList.add('error-input');
  } else {
    phoneError.style.display = 'none';
    this.classList.remove('error-input');
  }
});

// ตรวจสอบก่อน Submit
registerForm.addEventListener('submit', function(e) {
  const phone = phoneInput.value;
  
  // ตรวจสอบว่าเป็นตัวเลข 10 หลักหรือไม่
  if (!/^[0-9]{10}$/.test(phone)) {
    e.preventDefault();
    phoneError.style.display = 'block';
    phoneInput.classList.add('error-input');
    phoneInput.focus();
    
    // แสดง Alert
    alert('❌ กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง\nต้องเป็นตัวเลข 10 หลักเท่านั้น');
    return false;
  }
});

// ป้องกันการวางข้อความที่ไม่ใช่ตัวเลข
phoneInput.addEventListener('paste', function(e) {
  setTimeout(() => {
    this.value = this.value.replace(/[^0-9]/g, '');
  }, 10);
});
</script>

</body>
</html>