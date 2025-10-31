<?php
// admin_venues.php
// Admin page to create/edit venues, upload images, set maintenance status, and delete.

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// ✅ ตรวจสอบบทบาท (ต้องเป็นพนักงาน หรือ admin)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    echo "<h2 style='color:red;text-align:center;margin-top:50px;'>❌ คุณไม่มีสิทธิ์เข้าถึงหน้านี้</h2>";
    exit;
}

include 'db_connect.php';

// Fetch venue types for dropdown
$types = [];
$typeSql = "SELECT VenueTypeID, TypeName FROM tbl_venue_type ORDER BY TypeName ASC";
if ($res = $conn->query($typeSql)) {
    while ($row = $res->fetch_assoc()) { $types[] = $row; }
    $res->free();
}

// If editing
$editing = false;
$editRow = null;
if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM tbl_venue WHERE VenueID = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $editRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($editRow) $editing = true;
}

// Fetch all venues
$venues = [];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare("SELECT v.*, t.TypeName FROM tbl_venue v 
        JOIN tbl_venue_type t ON v.VenueTypeID = t.VenueTypeID
        WHERE v.VenueName LIKE ? OR t.TypeName LIKE ? OR v.Status LIKE ?
        ORDER BY v.VenueID DESC");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $venues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $sql = "SELECT v.*, t.TypeName FROM tbl_venue v 
            JOIN tbl_venue_type t ON v.VenueTypeID = t.VenueTypeID
            ORDER BY v.VenueID DESC";
    if ($res = $conn->query($sql)) {
        $venues = $res->fetch_all(MYSQLI_ASSOC);
        $res->free();
    }
}

function h($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="utf-8">
<title>จัดการสนาม (Admin)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #2B27ECFF 100%);
    min-height: 100vh;
    padding: 0;
}

/* Navbar Styles */
.navbar-modern {
    background: rgba(255, 255, 255, 0.98);
    backdrop-filter: blur(10px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    padding: 1rem 0;
    margin-bottom: 2rem;
}

.navbar-brand-modern {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin: 0;
}

.container-main {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 1rem 2rem;
}

/* Alert Styles */
.alert-modern {
    border: none;
    border-radius: 15px;
    padding: 1rem 1.5rem;
    margin-bottom: 1.5rem;
    animation: slideDown 0.3s ease-out;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Search Card */
.search-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    margin-bottom: 2rem;
}

.search-input {
    border: 2px solid #e0e7ff;
    border-radius: 12px;
    padding: 0.8rem 1.2rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

/* Button Styles */
.btn-modern {
    border: none;
    border-radius: 12px;
    padding: 0.8rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary-modern {
    background: linear-gradient(135deg, #667eea 0%, #514BA2FF 100%);
    color: white;
}

.btn-primary-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-outline-modern {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.btn-outline-modern:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
}

/* Card Styles */
.card-modern {
    background: white;
    border: none;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: all 0.3s ease;
    margin-bottom: 2rem;
}

.card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0, 0, 0, 0.15);
}

.card-header-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem;
    font-size: 1.2rem;
    font-weight: 700;
    border: none;
}

.card-body-modern {
    padding: 2rem;
}

/* Form Styles */
.form-label-modern {
    font-weight: 600;
    color: #4a5568;
    margin-bottom: 0.5rem;
    display: block;
}

.form-control-modern,
.form-select-modern {
    border: 2px solid #e0e7ff;
    border-radius: 10px;
    padding: 0.7rem 1rem;
    transition: all 0.3s ease;
    width: 100%;
}

.form-control-modern:focus,
.form-select-modern:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    outline: none;
}

textarea.form-control-modern {
    resize: vertical;
}

/* Table Styles */
.table-modern {
    background: white;
    margin: 0;
}

.table-modern thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table-modern thead th {
    border: none;
    padding: 1rem;
    font-weight: 600;
    vertical-align: middle;
}

.table-modern tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #f0f4ff;
}

.table-modern tbody tr:hover {
    background: #f8faff;
}

.table-modern tbody td {
    padding: 1rem;
    vertical-align: middle;
}

/* Image Thumbnail */
.thumb {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    cursor: pointer;
}

.thumb:hover {
    transform: scale(1.1);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
}

/* Badge Styles */
.badge-modern {
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-block;
}

.badge-success-modern {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.badge-warning-modern {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
    color: white;
}

.badge-secondary-modern {
    background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
    color: white;
}

/* Action Buttons */
.btn-action {
    padding: 0.4rem 1rem;
    border-radius: 8px;
    font-size: 0.85rem;
    margin: 0.2rem;
    border: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-edit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
}

.btn-status-warning {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
    color: white;
}

.btn-status-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(237, 137, 54, 0.4);
    color: white;
}

.btn-status-success {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
}

.btn-status-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
    color: white;
}

.btn-delete {
    background: white;
    color: #f56565;
    border: 2px solid #f56565;
}

.btn-delete:hover {
    background: #f56565;
    color: white;
    transform: translateY(-2px);
}

/* Submit Button */
.btn-submit {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 0.8rem 2rem;
    font-weight: 700;
    width: 100%;
    transition: all 0.3s ease;
    font-size: 1rem;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(72, 187, 120, 0.4);
    color: white;
}

/* Empty State */
.empty-state {
    padding: 3rem;
    text-align: center;
    color: #a0aec0;
}

/* Form Text */
.form-text-modern {
    font-size: 0.875rem;
    color: #718096;
    margin-top: 0.25rem;
}

/* Responsive */
@media (max-width: 768px) {
    .navbar-modern {
        padding: 0.75rem 0;
    }
    
    .navbar-brand-modern {
        font-size: 1.2rem;
    }
    
    .search-card {
        padding: 1.5rem;
    }
    
    .card-body-modern {
        padding: 1.5rem;
    }
    
    .btn-action {
        padding: 0.3rem 0.7rem;
        font-size: 0.75rem;
        margin: 0.1rem;
    }
    
    .table-modern {
        font-size: 0.85rem;
    }
    
    .table-modern thead th,
    .table-modern tbody td {
        padding: 0.75rem 0.5rem;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.card-modern {
    animation: fadeIn 0.5s ease-out;
}
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar-modern">
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="navbar-brand-modern mb-0">
                <i class="fas fa-futbol me-2"></i>จัดการสนาม
            </h1>
            <a href="dashboard.php" class="btn btn-primary-modern btn-modern">
                <i class="fas fa-home me-2"></i>กลับหน้า Dashboard
            </a>
        </div>
    </div>
</div>

<div class="container-main">
    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
      <div class="alert alert-success alert-modern" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= h($_SESSION['flash_success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger alert-modern" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= h($_SESSION['flash_error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Search -->
    <div class="search-card">
        <form class="row g-3" method="get" action="admin_venues.php">
            <div class="col-md-6">
                <input type="text" name="q" class="form-control search-input" placeholder="🔍 ค้นหาตามชื่อ ประเภท หรือสถานะ..." value="<?= h($search) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary-modern btn-modern w-100">
                    <i class="fas fa-search me-2"></i>ค้นหา
                </button>
            </div>
            <div class="col-md-3">
                <a href="admin_venues.php" class="btn btn-outline-modern btn-modern w-100">
                    <i class="fas fa-redo me-2"></i>ล้างตัวกรอง
                </a>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-5">
            <div class="card-modern">
                <div class="card-header-modern">
                    <i class="fas <?= $editing ? 'fa-edit' : 'fa-plus-circle' ?> me-2"></i>
                    <?= $editing ? 'แก้ไขสนาม #' . (int)$editRow['VenueID'] : 'เพิ่มสนามใหม่' ?>
                </div>
                <div class="card-body-modern">
                    <form action="venue_save.php" method="post" enctype="multipart/form-data">
                        <?php if ($editing): ?>
                            <input type="hidden" name="VenueID" value="<?= (int)$editRow['VenueID'] ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label-modern">ชื่อสนาม</label>
                            <input type="text" name="VenueName" class="form-control form-control-modern" required value="<?= h($editRow['VenueName'] ?? '') ?>" placeholder="กรอกชื่อสนาม">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-modern">ประเภทสนาม</label>
                            <select name="VenueTypeID" class="form-select form-select-modern" required>
                                <option value="">-- เลือกประเภท --</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= (int)$t['VenueTypeID'] ?>" <?= ($editing && $editRow['VenueTypeID']==$t['VenueTypeID'])?'selected':'' ?>>
                                        <?= h($t['TypeName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                          <div class="col-md-6 mb-3">
                              <label class="form-label-modern">ราคา/ชั่วโมง (บาท)</label>
                              <input type="number" min="0" step="0.01" name="PricePerHour" class="form-control form-control-modern" required value="<?= h($editRow['PricePerHour'] ?? '') ?>" placeholder="0.00">
                          </div>
                          <div class="col-md-3 mb-3">
                              <label class="form-label-modern">เปิด</label>
                              <input type="time" name="TimeOpen" class="form-control form-control-modern" value="<?= h($editRow['TimeOpen'] ?? '') ?>">
                          </div>
                          <div class="col-md-3 mb-3">
                              <label class="form-label-modern">ปิด</label>
                              <input type="time" name="TimeClose" class="form-control form-control-modern" value="<?= h($editRow['TimeClose'] ?? '') ?>">
                          </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-modern">ที่อยู่</label>
                            <textarea name="Address" class="form-control form-control-modern" rows="2" placeholder="กรอกที่อยู่สนาม"><?= h($editRow['Address'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-modern">รายละเอียด</label>
                            <textarea name="Description" class="form-control form-control-modern" rows="3" placeholder="กรอกรายละเอียดเพิ่มเติม"><?= h($editRow['Description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-modern">รูปภาพ <?= $editing ? '(อัปโหลดใหม่เพื่อเปลี่ยน)' : '' ?></label>
                            <input type="file" name="ImageFile" accept="image/*" class="form-control form-control-modern">
                            <?php if ($editing && !empty($editRow['ImageURL'])): ?>
                                <div class="mt-2">
                                    <img src="<?= h($editRow['ImageURL']) ?>" class="thumb" alt="">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label-modern">สถานะ</label>
                            <select name="Status" class="form-select form-select-modern">
                                <?php 
                                $statuses = ['available' => 'เปิดให้จอง', 'maintenance' => 'ปิดปรับปรุงชั่วคราว', 'closed' => 'ปิดถาวร'];
                                $cur = $editing ? ($editRow['Status'] ?? 'available') : 'available';
                                foreach ($statuses as $value => $label):
                                ?>
                                  <option value="<?= $value ?>" <?= ($cur === $value ? 'selected' : '') ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text-modern">
                                <i class="fas fa-info-circle me-1"></i>เลือก "ปิดปรับปรุงชั่วคราว" เพื่อไม่ให้ลูกค้าจองได้
                            </small>
                        </div>
                        
                        <button class="btn btn-submit">
                            <i class="fas fa-save me-2"></i><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มสนาม' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card-modern">
                <div class="card-header-modern">
                    <i class="fas fa-list me-2"></i>รายการสนามทั้งหมด
                </div>
                <div class="p-0">
                    <div class="table-responsive">
                      <table class="table table-modern mb-0">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>รูป</th>
                            <th>ชื่อ</th>
                            <th>ประเภท</th>
                            <th>ราคา/ชม.</th>
                            <th>สถานะ</th>
                            <th class="text-end">การทำงาน</th>
                          </tr>
                        </thead>
                        <tbody>
<?php $i = count($venues); ?>
<?php foreach ($venues as $v): ?>
  <tr>
    <td><?= $i-- ?></td>
    <td>
      <?php if (!empty($v['ImageURL'])): ?>
        <img class="thumb" src="<?= h($v['ImageURL']) ?>" alt="">
      <?php endif; ?>
    </td>
    <td><?= h($v['VenueName']) ?></td>
    <td><?= h($v['TypeName']) ?></td>
    <td><?= number_format((float)$v['PricePerHour'], 2) ?></td>
    <td>
      <?php 
        $map = ['available'=>'success', 'maintenance'=>'warning', 'closed'=>'secondary'];
        $label = ['available'=>'เปิดให้จอง', 'maintenance'=>'ปิดปรับปรุงชั่วคราว', 'closed'=>'ปิดถาวร'];
        $status = $v['Status'] ?? 'available';
      ?>
      <span class="badge badge-<?= $map[$status] ?? 'secondary' ?>-modern"><?= $label[$status] ?? h($status) ?></span>
    </td>
    <td class="text-end">
        <a class="btn btn-action btn-edit" href="admin_venues.php?id=<?= (int)$v['VenueID'] ?>">
            <i class="fas fa-edit me-1"></i>แก้ไข
        </a>

        <form action="venue_set_status.php" method="post" class="d-inline">
            <input type="hidden" name="VenueID" value="<?= (int)$v['VenueID'] ?>">
            <?php if (($v['Status'] ?? 'available') !== 'maintenance'): ?>
              <input type="hidden" name="Status" value="maintenance">
              <button class="btn btn-action btn-status-warning">
                  <i class="fas fa-tools me-1"></i>ตั้งเป็นปิดปรับปรุง
              </button>
            <?php else: ?>
              <input type="hidden" name="Status" value="available">
              <button class="btn btn-action btn-status-success">
                  <i class="fas fa-check me-1"></i>ตั้งเป็นเปิดให้จอง
              </button>
            <?php endif; ?>
        </form>

        <form action="venue_delete.php" method="post" class="d-inline"
              onsubmit="return confirm('ยืนยันลบสนามนี้หรือไม่? การลบไม่สามารถกู้คืนได้');">
            <input type="hidden" name="VenueID" value="<?= (int)$v['VenueID'] ?>">
            <button class="btn btn-action btn-delete">
                <i class="fas fa-trash me-1"></i>ลบ
            </button>
        </form>
    </td>
  </tr>
<?php endforeach; ?>

<?php if (empty($venues)): ?>
  <tr>
      <td colspan="7" class="empty-state">
          <i class="fas fa-inbox"></i>
          <p class="mb-0 mt-2">ไม่พบข้อมูล</p>
      </td>
  </tr>
<?php endif; ?>
                        </tbody>
                      </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>