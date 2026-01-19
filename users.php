<?php
require_once 'header.php';
checkAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $username = escape($_POST['username']);
            $full_name = escape($_POST['full_name']);
            $email = escape($_POST['email']);
            $phone = escape($_POST['phone']);
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($action === 'add') {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $check = $conn->query("SELECT user_id FROM users WHERE username = '$username'");
                if ($check->num_rows > 0) {
                    echo "<script>showError('Username sudah digunakan!');</script>";
                } else {
                    $query = "INSERT INTO users (username, password, full_name, email, phone, role, is_active) 
                             VALUES ('$username', '$password', '$full_name', '$email', '$phone', '$role', $is_active)";
                    if ($conn->query($query)) {
                        echo "<script>showSuccess('User berhasil ditambahkan');</script>";
                    }
                }
            } else {
                $query = "UPDATE users SET 
                         full_name = '$full_name',
                         email = '$email',
                         phone = '$phone',
                         role = '$role',
                         is_active = $is_active";
                if (!empty($_POST['password'])) {
                    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    $query .= ", password = '$password'";
                }
                $query .= " WHERE user_id = $user_id";
                if ($conn->query($query)) {
                    echo "<script>showSuccess('User berhasil diupdate');</script>";
                }
            }
        } elseif ($action === 'delete') {
            $user_id = intval($_POST['user_id']);
            if ($user_id == $_SESSION['user_id']) {
                echo "<script>showError('Tidak bisa menghapus user sendiri!');</script>";
            } else {
                $conn->query("DELETE FROM users WHERE user_id = $user_id");
                echo "<script>showSuccess('User berhasil dihapus');</script>";
            }
        }
    }
}

$query_users = "SELECT * FROM users ORDER BY full_name";
$result_users = $conn->query($query_users);
?>

<style>
.user-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
    padding: 15px;
}

.user-card {
    background: linear-gradient(135deg, rgba(26,26,26,0.95), rgba(45,45,45,0.95));
    border-radius: 12px;
    padding: 15px;
    border: 1px solid rgba(218,165,32,0.2);
    position: relative;
    overflow: hidden;
}

.user-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #B8860B, #DAA520, #FFD700);
}

.user-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(218,165,32,0.1);
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #B8860B, #DAA520);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #0a0a0a;
    font-size: 18px;
    font-weight: 700;
    border: 2px solid rgba(255,215,0,0.2);
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 14px;
    font-weight: 700;
    color: #FFD700;
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-username {
    color: #DAA520;
    font-weight: 600;
    font-size: 11px;
    margin-bottom: 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.user-badges {
    display: flex;
    gap: 4px;
}

.user-badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}

.badge-admin {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.badge-kasir {
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color: white;
}

.badge-active {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.badge-inactive {
    background: rgba(156,163,175,0.3);
    color: #9ca3af;
}

.user-details {
    margin: 10px 0;
}

.user-detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 0;
    font-size: 12px;
    color: rgba(218,165,32,0.8);
}

.user-detail-item i {
    width: 14px;
    color: #DAA520;
    flex-shrink: 0;
}

.user-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 6px;
    margin-top: 12px;
}

.btn-action {
    padding: 8px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    min-height: 36px;
}

.btn-edit {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.current-user-notice {
    background: rgba(59,130,246,0.1);
    border: 1px solid rgba(59,130,246,0.2);
    border-radius: 6px;
    padding: 8px;
    text-align: center;
    color: #60a5fa;
    font-size: 10px;
    margin-top: 12px;
}

.page-header {
    padding: 15px;
    background: rgba(26,26,26,0.9);
    border-bottom: 1px solid rgba(218,165,32,0.2);
}

.empty-state {
    text-align: center;
    padding: 40px 15px;
    color: rgba(218,165,32,0.6);
    grid-column: 1 / -1;
}

.empty-state i {
    font-size: 40px;
    margin-bottom: 10px;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .user-grid {
        grid-template-columns: 1fr;
        padding: 10px;
        gap: 10px;
    }
    
    .user-card {
        padding: 12px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        font-size: 16px;
    }
    
    .user-name {
        font-size: 13px;
    }
    
    .user-username {
        font-size: 10px;
    }
    
    .user-badge {
        padding: 1px 4px;
        font-size: 8px;
    }
    
    .user-detail-item {
        font-size: 11px;
    }
    
    .btn-action {
        padding: 6px;
        font-size: 10px;
        min-height: 32px;
    }
    
    .page-header {
        padding: 12px;
    }
}

@media (max-width: 374px) {
    .user-grid {
        padding: 8px 4px;
    }
    
    .user-card {
        padding: 10px;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        font-size: 14px;
    }
    
    .btn-action {
        font-size: 9px;
    }
}
</style>

<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Manajemen Pengguna</h1>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#userModal">
            <i class="fas fa-plus me-2"></i>Tambah User
        </button>
    </div>
</div>

<div class="user-grid">
    <?php if ($result_users->num_rows > 0): ?>
        <?php while ($user = $result_users->fetch_assoc()): ?>
            <div class="user-card">
                <div class="user-header">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($user['full_name']) ?></div>
                        <div class="user-username">@<?= htmlspecialchars($user['username']) ?></div>
                        <div class="user-badges">
                            <span class="user-badge badge-<?= $user['role'] ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                            <span class="user-badge badge-<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $user['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="user-detail-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= $user['email'] ? htmlspecialchars($user['email']) : 'Tidak ada email' ?></span>
                    </div>
                    <div class="user-detail-item">
                        <i class="fas fa-phone"></i>
                        <span><?= $user['phone'] ? htmlspecialchars($user['phone']) : 'Tidak ada telepon' ?></span>
                    </div>
                </div>
                
                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                    <div class="user-actions">
                        <button class="btn-action btn-edit" onclick='editUser(<?= json_encode($user) ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteUser(<?= $user['user_id'] ?>)">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                <?php else: ?>
                    <div class="current-user-notice">
                        <i class="fas fa-info-circle me-1"></i>
                        Ini adalah akun Anda
                    </div>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>Belum ada user terdaftar</p>
            <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#userModal">
                <i class="fas fa-plus me-2"></i>Tambah User Pertama
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="userForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="user_id" id="userId">
                    
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Username *</label>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="full_name" id="fullName" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">Password <span id="passwordNote">*</span></label>
                            <input type="password" name="password" id="password" class="form-control" required>
                            <small class="text-muted d-block mt-1">Kosongkan jika tidak ingin mengubah password (edit mode)</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-md-4 pt-md-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
                                <label class="form-check-label" for="isActive">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('userId').value = user.user_id;
    document.getElementById('username').value = user.username;
    document.getElementById('username').readOnly = true;
    document.getElementById('fullName').value = user.full_name;
    document.getElementById('email').value = user.email || '';
    document.getElementById('phone').value = user.phone || '';
    document.getElementById('role').value = user.role;
    document.getElementById('isActive').checked = user.is_active == 1;
    document.getElementById('password').required = false;
    document.getElementById('passwordNote').textContent = '(opsional)';
    document.getElementById('password').placeholder = 'Kosongkan jika tidak ingin mengubah';
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

async function deleteUser(id) {
    const confirmed = await confirmDialog('Hapus User?', 'User akan dihapus permanen. Lanjutkan?', 'Ya, Hapus');
    if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('user_id', id);
        await fetch('users.php', { method: 'POST', body: formData });
        location.reload();
    }
}

document.getElementById('userModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('userForm').reset();
    document.getElementById('modalTitle').textContent = 'Tambah User';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userId').value = '';
    document.getElementById('username').readOnly = false;
    document.getElementById('password').required = true;
    document.getElementById('passwordNote').textContent = '*';
    document.getElementById('password').placeholder = '';
});
</script>

<?php require_once 'footer.php'; ?>