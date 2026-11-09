<?php
// PRODUCTS PAGE (partial, di-include index.php)
// Tanggung jawab: tampilan kelola motor saja
// Variabel tersedia: $conn


$filter_search = trim($_GET['s'] ?? '');
$filter_status = trim($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];
$bind_types = "";

if ($filter_search !== '') {
    $where .= " AND (name LIKE ? OR brand LIKE ?)";
    $like = "%" . $filter_search . "%";
    $params[] = $like;
    $params[] = $like;
    $bind_types .= "ss";
}
if ($filter_status !== '' && in_array($filter_status, ['available', 'rented', 'maintenance'], true)) {
    $where .= " AND status=?";
    $params[] = $filter_status;
    $bind_types .= "s";
}

$stmt = $conn->prepare("SELECT * FROM products $where ORDER BY created_at DESC");
if ($stmt === false) {
    error_log("admin products prepare failed: " . $conn->error);
    $products = false;
} else {
    if (!empty($params)) {
        $stmt->bind_param($bind_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result ? $result : false;
}

$status_label = ['available' => 'Tersedia', 'rented' => 'Disewa', 'maintenance' => 'Perawatan'];
?>

<div class="content-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
        <h2 style="margin:0;border:0;padding:0;">Kelola Motor</h2>
        <button class="btn btn-primary" onclick="document.getElementById('addProductModal').classList.add('active')">+ Tambah Motor</button>
    </div>

    <!-- FILTER -->
    <form method="GET" action="" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;">
        <input type="hidden" name="page" value="products">
        <input type="text" name="s" placeholder="Cari nama/brand..." value="<?= htmlspecialchars($filter_search) ?>" style="padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
        <select name="status" onchange="this.form.submit()" style="padding:10px 14px;border:1px solid #ddd;border-radius:8px;font-size:14px;">
            <option value="">Semua Status</option>
            <?php foreach ($status_label as $key => $label): ?>
                <option value="<?= $key ?>" <?= $filter_status === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Cari</button>
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Motor</th>
                    <th>Tipe</th>
                    <th>Harga/Hari</th>
                    <th>Harga/Minggu</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; if ($products !== false) while ($row = $products->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <div class="motor-cell">
                            <div class="motor-thumb">
                                <?php if (!empty($row['image']) && file_exists(__DIR__ . '/../' . $row['image'])): ?>
                                    <img src="../<?= htmlspecialchars($row['image']) ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($row['name']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($row['brand']) ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= htmlspecialchars($row['type']) ?></td>
                    <td><strong>Rp <?= number_format($row['price_per_day'], 0, ',', '.') ?></strong></td>
                    <td>Rp <?= number_format($row['price_per_week'], 0, ',', '.') ?></td>
                    <td><span class="badge badge-<?= htmlspecialchars($row['status']) ?>"><?= $status_label[$row['status']] ?? ucfirst($row['status']) ?></span></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-warning btn-sm" onclick='openEditProduct(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>Edit</button>
                            <form action="process_product.php" method="POST" style="display:inline;" onsubmit="return confirm('Yakin hapus motor ini?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= intval($row['id']) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addProductModal">
    <div class="modal">
        <h2>Tambah Motor</h2>
        <form action="process_product.php?action=add" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Motor</label>
                    <input type="text" name="name" required placeholder="cth: Honda Beat 2023">
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" required placeholder="cth: Honda">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tipe</label>
                    <select name="type" required>
                        <option value="Matic">Matic</option>
                        <option value="Manual">Manual</option>
                        <option value="Sport">Sport</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="available">Tersedia</option>
                        <option value="rented">Disewa</option>
                        <option value="maintenance">Perawatan</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Harga/Hari (Rp)</label>
                    <input type="number" name="price_per_day" min="1" required placeholder="75000">
                </div>
                <div class="form-group">
                    <label>Harga/Minggu (Rp)</label>
                    <input type="number" name="price_per_week" min="0" required placeholder="450000">
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" placeholder="Deskripsi singkat motor">
            </div>
            <div class="form-group">
                <label>Gambar (jpg/png/webp, max 2MB)</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="document.getElementById('addProductModal').classList.remove('active')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editProductModal">
    <div class="modal">
        <h2>Edit Motor</h2>
        <form action="process_product.php?action=edit" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_product_id">
            <div class="form-row">
                <div class="form-group">
                    <label>Nama Motor</label>
                    <input type="text" name="name" id="edit_product_name" required>
                </div>
                <div class="form-group">
                    <label>Brand</label>
                    <input type="text" name="brand" id="edit_product_brand" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tipe</label>
                    <select name="type" id="edit_product_type" required>
                        <option value="Matic">Matic</option>
                        <option value="Manual">Manual</option>
                        <option value="Sport">Sport</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_product_status" required>
                        <option value="available">Tersedia</option>
                        <option value="rented">Disewa</option>
                        <option value="maintenance">Perawatan</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Harga/Hari (Rp)</label>
                    <input type="number" name="price_per_day" id="edit_product_day" min="1" required>
                </div>
                <div class="form-group">
                    <label>Harga/Minggu (Rp)</label>
                    <input type="number" name="price_per_week" id="edit_product_week" min="0" required>
                </div>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <input type="text" name="description" id="edit_product_description">
            </div>
            <div class="form-group">
                <label>Ganti Gambar (kosongkan jika tidak diubah)</label>
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-danger" onclick="document.getElementById('editProductModal').classList.remove('active')">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
<?php if (isset($stmt) && $stmt !== false) $stmt->close(); ?>
