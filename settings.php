<?php
require_once 'header.php';
checkAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $value = escape($value);
            $check = $conn->query("SELECT * FROM settings WHERE setting_key = '$key'");
            
            if ($check->num_rows > 0) {
                $conn->query("UPDATE settings SET setting_value = '$value' WHERE setting_key = '$key'");
            } else {
                $conn->query("INSERT INTO settings (setting_key, setting_value) VALUES ('$key', '$value')");
            }
        }
    }
    
    echo "<script>showSuccess('Pengaturan berhasil disimpan');</script>";
}

// Get all settings
$query_settings = "SELECT * FROM settings ORDER BY setting_key";
$result_settings = $conn->query($query_settings);

$settings = [];
while ($row = $result_settings->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default settings if not exists
$default_settings = [
    'restaurant_name' => 'Smart Resto POS',
    'restaurant_address' => 'Jl. Raya No. 123, Jakarta',
    'restaurant_phone' => '021-12345678',
    'restaurant_email' => 'info@restopos.com',
    'tax_rate' => '10',
    'points_per_1000' => '1',
    'receipt_footer' => 'Terima kasih atas kunjungan Anda',
    'currency' => 'Rp'
];

foreach ($default_settings as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}
?>

<style>
/* GOLD THEME - Settings Page - FIXED TEXT VISIBILITY */
.settings-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

.settings-section {
    background: linear-gradient(135deg, rgba(26, 26, 26, 0.95), rgba(45, 45, 45, 0.95));
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    border: 2px solid rgba(218, 165, 32, 0.3);
    position: relative;
    overflow: hidden;
}

.settings-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #B8860B, #DAA520, #FFD700);
}

.section-title {
    font-size: 20px;
    font-weight: 700;
    color: #FFD700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid rgba(218, 165, 32, 0.3);
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    color: #0a0a0a;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(218, 165, 32, 0.4);
}

/* FIXED: Form elements dengan kontras tinggi */
.form-label {
    color: #FFD700 !important;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: block;
}

.form-control,
.form-select,
input[type="text"],
input[type="email"],
input[type="number"],
textarea {
    background: #ffffff !important;
    border: 2px solid rgba(218, 165, 32, 0.3) !important;
    color: #0a0a0a !important;
    padding: 12px 15px;
    border-radius: 10px;
    transition: all 0.3s;
    font-weight: 500;
}

.form-control::placeholder,
input::placeholder,
textarea::placeholder {
    color: #6b7280 !important;
    opacity: 0.7;
}

.form-control:focus,
.form-select:focus,
input:focus,
textarea:focus {
    background: #ffffff !important;
    border-color: #DAA520 !important;
    color: #0a0a0a !important;
    box-shadow: 0 0 0 3px rgba(218, 165, 32, 0.2) !important;
    outline: none;
}

.text-muted,
small {
    color: #DAA520 !important;
    font-weight: 500;
}

/* Database Info Cards */
.db-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.db-info-card {
    background: rgba(45, 45, 45, 0.6);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    border: 2px solid rgba(218, 165, 32, 0.2);
    transition: all 0.3s;
}

.db-info-card:hover {
    border-color: #DAA520;
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(218, 165, 32, 0.3);
}

.db-info-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.icon-products {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.icon-transactions {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.icon-members {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.icon-users {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.db-info-value {
    font-size: 28px;
    font-weight: 700;
    color: #FFD700;
    margin-bottom: 5px;
}

.db-info-label {
    font-size: 13px;
    color: #DAA520;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Save Button */
.save-button-wrapper {
    position: sticky;
    bottom: 20px;
    text-align: right;
    margin-top: 30px;
    z-index: 100;
}

.btn-save {
    padding: 16px 40px;
    font-size: 16px;
    font-weight: 700;
    border-radius: 12px;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    border: none;
    color: #0a0a0a;
    box-shadow: 0 8px 25px rgba(218, 165, 32, 0.5);
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.btn-save:hover {
    background: linear-gradient(135deg, #DAA520, #FFD700);
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(218, 165, 32, 0.7);
}

.btn-save:active {
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    .settings-section {
        padding: 20px;
    }
    
    .section-title {
        font-size: 18px;
    }
    
    .section-icon {
        width: 36px;
        height: 36px;
        font-size: 18px;
    }
    
    .db-info-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .btn-save {
        width: 100%;
        padding: 14px 30px;
    }
    
    .save-button-wrapper {
        position: static;
    }
}

@media (max-width: 480px) {
    .db-info-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="settings-wrapper">
    <form method="POST">
        <!-- Restaurant Info -->
        <div class="settings-section">
            <h5 class="section-title">
                <span class="section-icon">
                    <i class="fas fa-store"></i>
                </span>
                Informasi Restoran
            </h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nama Restoran</label>
                    <input type="text" name="restaurant_name" class="form-control" 
                           value="<?= $settings['restaurant_name'] ?>" placeholder="Nama restoran Anda">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Nomor Telepon</label>
                    <input type="text" name="restaurant_phone" class="form-control" 
                           value="<?= $settings['restaurant_phone'] ?>" placeholder="021-xxxxxxxx">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="restaurant_email" class="form-control" 
                           value="<?= $settings['restaurant_email'] ?>" placeholder="email@restoran.com">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Mata Uang</label>
                    <input type="text" name="currency" class="form-control" 
                           value="<?= $settings['currency'] ?>" placeholder="Rp">
                </div>
                
                <div class="col-12 mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="restaurant_address" class="form-control" rows="3" 
                              placeholder="Alamat lengkap restoran"><?= $settings['restaurant_address'] ?></textarea>
                </div>
            </div>
        </div>
        
        <!-- Transaction Settings -->
        <div class="settings-section">
            <h5 class="section-title">
                <span class="section-icon">
                    <i class="fas fa-calculator"></i>
                </span>
                Pengaturan Transaksi
            </h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Persentase Pajak (%)</label>
                    <input type="number" name="tax_rate" class="form-control" step="0.01"
                           value="<?= $settings['tax_rate'] ?>" placeholder="10">
                    <small class="text-muted">Pajak yang dikenakan pada setiap transaksi</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Poin per Rp 1.000</label>
                    <input type="number" name="points_per_1000" class="form-control" step="1"
                           value="<?= $settings['points_per_1000'] ?>" placeholder="1">
                    <small class="text-muted">Poin yang didapat member per Rp 1.000 belanja</small>
                </div>
            </div>
        </div>
        
        <!-- Receipt Settings -->
        <div class="settings-section">
            <h5 class="section-title">
                <span class="section-icon">
                    <i class="fas fa-receipt"></i>
                </span>
                Pengaturan Struk
            </h5>
            
            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">Footer Struk</label>
                    <textarea name="receipt_footer" class="form-control" rows="3" 
                              placeholder="Terima kasih atas kunjungan Anda"><?= $settings['receipt_footer'] ?></textarea>
                    <small class="text-muted">Pesan yang ditampilkan di bagian bawah struk</small>
                </div>
            </div>
        </div>
        
        <!-- Database Info -->
        <div class="settings-section">
            <h5 class="section-title">
                <span class="section-icon">
                    <i class="fas fa-database"></i>
                </span>
                Informasi Database
            </h5>
            
            <div class="db-info-grid">
                <div class="db-info-card">
                    <div class="db-info-icon icon-products">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="db-info-value">
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
                        echo number_format($count);
                        ?>
                    </div>
                    <div class="db-info-label">Total Produk</div>
                </div>
                
                <div class="db-info-card">
                    <div class="db-info-icon icon-transactions">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="db-info-value">
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
                        echo number_format($count);
                        ?>
                    </div>
                    <div class="db-info-label">Total Transaksi</div>
                </div>
                
                <div class="db-info-card">
                    <div class="db-info-icon icon-members">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="db-info-value">
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as total FROM members")->fetch_assoc()['total'];
                        echo number_format($count);
                        ?>
                    </div>
                    <div class="db-info-label">Total Member</div>
                </div>
                
                <div class="db-info-card">
                    <div class="db-info-icon icon-users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="db-info-value">
                        <?php
                        $count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
                        echo number_format($count);
                        ?>
                    </div>
                    <div class="db-info-label">Total User</div>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="save-button-wrapper">
            <button type="submit" name="submit" class="btn btn-save">
                <i class="fas fa-save me-2"></i>Simpan Pengaturan
            </button>
        </div>
    </form>
</div>

<script>
// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const restaurantName = document.querySelector('input[name="restaurant_name"]').value;
    const taxRate = document.querySelector('input[name="tax_rate"]').value;
    
    if (!restaurantName.trim()) {
        e.preventDefault();
        showError('Nama restoran wajib diisi!');
        return false;
    }
    
    if (parseFloat(taxRate) < 0 || parseFloat(taxRate) > 100) {
        e.preventDefault();
        showError('Pajak harus antara 0-100%!');
        return false;
    }
    
    showLoading('Menyimpan pengaturan...');
});
</script>

<?php require_once 'footer.php'; ?>