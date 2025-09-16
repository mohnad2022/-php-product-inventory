<?php
session_start(); // Start session to store messages and products

// Seed two default products so the table isn't empty on first load
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [ // Prepare the products array by default
        [
            'id' => 1,            // Unique identifier
            'name' => 'apple',    // Product name
            'description' => 'apple is the best in the world', // Short description
            'price' => 149.99,    // Decimal price
            'category' => 'category 1', // Category
        ],
        [
            'id' => 2,
            'name' => 'nike',
            'description' => 'Nike is the better than Puma',
            'price' => 59.50,
            'category' => 'category 2',
        ],
    ];
}

// Shortcut reference to the products array for direct read/write
$products = &$_SESSION['products'];

// Category list to build the <select> options via loop (instead of hardcoding)
$categories = ['category 1', 'category 2', 'category 3']; // Add more if you like

$errors = [];          // Collect validation errors to display above the form
$submittedData = [];   // Preserve user inputs in case validation fails
$successMessage = null; // One-time success message (cleared after display)

/* Delete product (POST) */
// If user confirmed deletion from the modal, get the id and remove it
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id']; // Cast to int for safety
    foreach ($products as $k => $p) {     // Iterate products
        if ((int)$p['id'] === $deleteId) { // Match by id
            unset($products[$k]);          // Remove item
            $_SESSION['products'] = array_values($products); // Reindex
            $_SESSION['successMessage'] = 'Product deleted successfully.'; // Flash success
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));   // Clean refresh
            exit; // Always exit after header redirect
        }
    }
}

/* Edit mode via ?edit_id (Challenge) */
// If edit_id exists in the URL, fetch that product to prefill the same add form
$editRecord = null; // Will hold the record to prefill the form in edit mode
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id']; // Cast to int
    foreach ($products as $p) {      // Find the product
        if ((int)$p['id'] === $editId) {
            $editRecord = $p;        // Found — use it to prefill
            break;
        }
    }
}

/* Add/Update product (POST) */
// One form handles both add and update — decided by the presence of hidden id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    // Trim inputs to remove leading/trailing spaces
    $name        = trim($_POST['name'] ?? '');        // Product name
    $description = trim($_POST['description'] ?? ''); // Description
    $price       = trim($_POST['price'] ?? '');       // Price as text — will validate numeric
    $category    = trim($_POST['category'] ?? '');    // Category

    // Preserve user entries if validation fails
    $submittedData = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'category' => $category,
    ];

    // Name: required with reasonable length
    if ($name === '' || mb_strlen($name) < 3) {
        $errors['name'] = 'Please enter a valid product name (min 3 chars).';
    }
    // Description: required and not too short
    if ($description === '' || mb_strlen($description) < 5) {
        $errors['description'] = 'Please enter a meaningful description (min 5 chars).';
    }
    // Price: must be a positive number
    if ($price === '' || !is_numeric($price) || (float)$price <= 0) {
        $errors['price'] = 'Price must be a positive number.';
    }
    // Category: must be one of the predefined list
    if (!in_array($category, $categories, true)) {
        $errors['category'] = 'Please choose a valid category.';
    }

    // If no errors — proceed with add/update
    if (empty($errors)) {
        if (isset($_POST['id']) && $_POST['id'] !== '') {
            // Update mode: locate the product and update fields
            $pid = (int)$_POST['id'];
            foreach ($products as &$p) { // By reference to edit in place
                if ((int)$p['id'] === $pid) {
                    $p['name'] = $name;
                    $p['description'] = $description;
                    $p['price'] = (float)$price;
                    $p['category'] = $category;
                    break; // Done updating
                }
            }
            $_SESSION['successMessage'] = 'Product updated successfully.'; // Flash success
        } else {
            // Add mode: generate a new id based on max existing id
            $newId = count($products) ? (max(array_column($products, 'id')) + 1) : 1;
            $products[] = [ // Append new product
                'id' => $newId,
                'name' => $name,
                'description' => $description,
                'price' => (float)$price,
                'category' => $category,
            ];
            $_SESSION['successMessage'] = 'Product added successfully.'; // Flash success
        }

        // Reset preserved inputs so the form becomes clean after redirect
        $submittedData = [];
        // Remove any edit_id from the URL before returning
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); // Clean redirect
        exit; // Always exit after redirect
    }
}

// Pull one-time success message from session, then clear it
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage']; // Display once
    unset($_SESSION['successMessage']);            // Then clear
}
?>
<!DOCTYPE html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Mobile-friendly viewport -->
    <title>Products Management</title> <!-- Browser tab title -->

    <!-- Bootstrap CDN: load CSS from the internet -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --brand: #556b2f;
            --brand2: #445626;
            --accent: #e6b800;
            --border: #e2d7c3;
            --txt: #1c1c1c;
        }

        body {
            /* Base page styling */
            background: linear-gradient(135deg, #fffdf9, #f8f1e4);
            min-height: 100vh;   /* Full viewport height */
            padding: 20px;       /* Spacing around content */
            color: var(--txt);   /* Default text color */
        }

        .container {
            /* Main card-like container */
            background: linear-gradient(135deg, #faf4ea, #f2e7d8);
            padding: 24px;
            border-radius: 14px;
            border: 1px solid var(--border);
        }

        .table {
            /* Subtle table enhancements */
            --bs-table-striped-bg: rgba(0, 0, 0, .02);
            --bs-table-hover-bg: rgba(0, 0, 0, .04);
        }

        .btn-primary {
            background: var(--brand);
            border-color: var(--brand2)
        }

        /* Edit button */
        .btn-warning {
            background: var(--accent);
            border-color: var(--accent);
            color: #111
        }

        /* Optional product thumbnail in table (if added later) */
        img.thumb {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border)
        }
    </style>
</head>

<body>
    <div class="container mt-3"> <!-- Neat container with top margin -->
        <h1 class="text-center mb-4">Products Management</h1> <!-- Main title -->

        <?php if ($successMessage): ?> <!-- Show success flash if present -->
            <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?> <!-- Generic error alert if there are validation issues -->
            <div class="alert alert-danger">
                Please fix the errors below.
            </div>
        <?php endif; ?>

        <!-- One form for both Add and Edit -->

        <?php
        // Determine source of form values: editRecord (edit mode) or submittedData (failed validation)
        $formData = $editRecord ?: $submittedData;
        $isEditing = $editRecord !== null; // true => edit mode
        ?>
        <h3><?= $isEditing ? 'Edit product' : 'Add new product' ?></h3>
        <form method="POST" class="mb-4"> <!-- Post back to the same page -->
            <?php if ($isEditing): ?> <!-- In edit mode, pass hidden id -->
                <input type="hidden" name="id" value="<?= (int)$editRecord['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">name product</label>
                <input type="text" name="name"
                    class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($formData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">description product</label>
                <textarea name="description"
                    class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                    rows="3"><?= htmlspecialchars($formData['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">the price</label>
                <input type="text" name="price"
                    class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($formData['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="invalid-feedback"><?= htmlspecialchars($errors['price'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <div class="mb-3">
                <label class="form-label">category</label>
                <select name="category" class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>">
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $selectedVal = $formData['category'] ?? '';
                        $sel = ($selectedVal === $cat) ? 'selected' : '';
                        ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                            <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>

            <button type="submit" class="btn btn-primary">
                <?= $isEditing ? 'Update Product' : 'Add Product' ?>
            </button>
            <?php if ($isEditing): ?> <!-- Cancel returns to clean add mode -->
                <a class="btn btn-secondary ms-2" href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            <?php endif; ?>
        </form>

        <!-- Products table -->
        <h3 class="mt-4">List Products</h3>
        <div class="table-responsive"> <!-- Better on mobile -->
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>name</th>
                        <th>description</th>
                        <th>price</th>
                        <th>category</th>
                        <th>procedures</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?> <!-- Empty state -->
                        <tr>
                            <td colspan="7" class="text-center text-muted">No products yet — add your first product.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($p['description'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php
                                    $fmt = number_format((float)$p['price'], 2); // Format to 2 decimals
                                    echo htmlspecialchars($fmt, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="d-flex gap-2">
                                    <!-- Edit button via URL parameter -->
                                    <a class="btn btn-warning btn-sm" href="?edit_id=<?= (int)$p['id'] ?>">edit</a>

                                    <!-- Delete button triggers confirmation modal -->
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#del<?= (int)$p['id'] ?>">delete</button>

                                    <!-- Delete confirmation modal -->
                                    <div class="modal fade" id="del<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">confirm deletion</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete "<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>"?
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST">
                                                        <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>">
                                                        <button type="submit" class="btn btn-danger">delete</button>
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">cancel</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS Bundle for modals, dropdowns, etc. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
