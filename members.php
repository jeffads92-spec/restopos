<?php
require_once 'header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'add' || $action === 'edit') {
            $member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
            $member_name = escape($_POST['member_name']);
            $phone = escape($_POST['phone']);
            $email = escape($_POST['email']);
            $address = escape($_POST['address']);
            $birth_date = $_POST['birth_date'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($action === 'add') {
                // Generate unique member code
                do {
                    $member_code = generateMemberCode();
                    $check = $conn->query("SELECT member_id FROM members WHERE member_code = '$member_code'");
                } while ($check->num_rows > 0);
                
                $join_date = date('Y-m-d');
                
                $query = "INSERT INTO members (member_code, member_name, phone, email, address, birth_date, join_date, is_active) 
                         VALUES ('$member_code', '$member_name', '$phone', '$email', '$address', '$birth_date', '$join_date', $is_active)";
                
                if ($conn->query($query)) {
                    echo "<script>showSuccess('Member berhasil ditambahkan');</script>";
                }
            } else {
                $query = "UPDATE members SET 
                         member_name = '$member_name',
                         phone = '$phone',
                         email = '$email',
                         address = '$address',
                         birth_date = '$birth_date',
                         is_active = $is_active
                         WHERE member_id = $member_id";
                
                if ($conn->query($query)) {
                    echo "<script>showSuccess('Member berhasil diupdate');</script>";
                }
            }
        } elseif ($action === 'delete') {
            $member_id = intval($_POST['member_id']);
            $conn->query("DELETE FROM members WHERE member_id = $member_id");
            echo "<script>showSuccess('Member berhasil dihapus');</script>";
        }
    }
}

// Get members
$search = isset($_GET['search']) ? escape($_GET['search']) : '';
$where = $search ? "WHERE member_name LIKE '%$search%' OR member_code LIKE '%$search%' OR phone LIKE '%$search%'" : "";

$query_members = "SELECT * FROM members $where ORDER BY member_name";
$result_members = $conn->query($query_members);

// Statistics - FIXED: Convert NULL to 0
$query_stats = "SELECT 
    COUNT(*) as total, 
    COALESCE(SUM(points), 0) as total_points, 
    COALESCE(SUM(total_spent), 0) as total_spent 
    FROM members 
    WHERE is_active = 1";
$result_stats = $conn->query($query_stats);
$stats = $result_stats->fetch_assoc();
?>

<style>
.member-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 5px;
}

.stat-label {
    color: #6b7280;
    font-size: 14px;
}

.member-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: all 0.3s;
}

.member-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    font-weight: 700;
}

.member-info {
    flex: 1;
}

.member-name {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 5px;
}

.member-code {
    color: #667eea;
    font-weight: 600;
    font-size: 14px;
}

.member-details {
    display: flex;
    gap: 20px;
    margin-top: 10px;
    font-size: 13px;
    color: #6b7280;
}

.member-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 10px 15px;
    background: #f9fafb;
    border-radius: 8px;
}

.member-stat-value {
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
}

.member-stat-label {
    font-size: 11px;
    color: #9ca3af;
    text-transform: uppercase;
}
</style>

<!-- Statistics -->
<div class="member-stats">
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white;">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?= number_format(intval($stats['total'] ?? 0)) ?></div>
        <div class="stat-label">Total Member</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706); color: white;">
            <i class="fas fa-star"></i>
        </div>
        <div class="stat-value"><?= number_format(intval($stats['total_points'] ?? 0)) ?></div>
        <div class="stat-label">Total Poin</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-value"><?= formatRupiah(floatval($stats['total_spent'] ?? 0)) ?></div>
        <div class="stat-label">Total Belanja</div>
    </div>
</div>

<!-- Filter & Add -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <form method="GET">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari member..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#memberModal">
                    <i class="fas fa-plus me-2"></i>Tambah Member
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Members List -->
<div class="row">
    <?php if ($result_members->num_rows > 0): ?>
        <?php while ($member = $result_members->fetch_assoc()): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="member-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="member-avatar me-3">
                            <?= strtoupper(substr($member['member_name'], 0, 1)) ?>
                        </div>
                        <div class="member-info">
                            <div class="member-name"><?= htmlspecialchars($member['member_name']) ?></div>
                            <div class="member-code"><?= htmlspecialchars($member['member_code']) ?></div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-<?= $member['is_active'] ? 'success' : 'secondary' ?>">
                                <?= $member['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="member-details">
                        <div>
                            <i class="fas fa-phone me-1"></i><?= htmlspecialchars($member['phone'] ?: '-') ?>
                        </div>
                        <div>
                            <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($member['email'] ?: '-') ?>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <div class="member-stat flex-fill">
                            <div class="member-stat-value"><?= number_format(intval($member['points'] ?? 0)) ?></div>
                            <div class="member-stat-label">Poin</div>
                        </div>
                        <div class="member-stat flex-fill">
                            <div class="member-stat-value"><?= formatRupiah(floatval($member['total_spent'] ?? 0)) ?></div>
                            <div class="member-stat-label">Total Belanja</div>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-warning flex-fill" onclick='editMember(<?= json_encode($member) ?>)'>
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <button class="btn btn-sm btn-danger flex-fill" onclick="deleteMember(<?= $member['member_id'] ?>)">
                            <i class="fas fa-trash me-1"></i>Hapus
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="text-center py-5 text-muted">
                <i class="fas fa-users fa-4x mb-3"></i>
                <p>Belum ada member</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Member Modal -->
<div class="modal fade" id="memberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Tambah Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="memberForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="member_id" id="memberId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nama Lengkap *</label>
                            <input type="text" name="member_name" id="memberName" class="form-control" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nomor Telepon</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tanggal Lahir</label>
                            <input type="date" name="birth_date" id="birthDate" class="form-control">
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="address" id="address" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-check form-switch">
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
function editMember(member) {
    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('memberId').value = member.member_id;
    document.getElementById('memberName').value = member.member_name;
    document.getElementById('phone').value = member.phone || '';
    document.getElementById('email').value = member.email || '';
    document.getElementById('address').value = member.address || '';
    document.getElementById('birthDate').value = member.birth_date || '';
    document.getElementById('isActive').checked = member.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('memberModal')).show();
}

async function deleteMember(id) {
    const confirmed = await confirmDialog(
        'Hapus Member?',
        'Member akan dihapus permanen. Lanjutkan?',
        'Ya, Hapus'
    );
    
    if (confirmed) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('member_id', id);
        
        fetch('members.php', {
            method: 'POST',
            body: formData
        }).then(() => location.reload());
    }
}

// Reset form
document.getElementById('memberModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('memberForm').reset();
    document.getElementById('modalTitle').textContent = 'Tambah Member';
    document.getElementById('formAction').value = 'add';
    document.getElementById('memberId').value = '';
});
</script>

<?php require_once 'footer.php'; ?>