<?php
session_start(); // بنبلّش سيشن عالسريع عشان نخزّن الرسائل والمنتجات


// حطيت منتجين افتراضيين عشان اول ما افتح الصفحة يكونوا موجودين في الجدول
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [ // بنجهّز مصفوفة المنتجات افتراضيًا
        [
            'id' => 1, // رقم تعريف فريد
            'name' => 'apple', // اسم المنتج
            'description' => 'apple is the best in the world', // وصف سريع
            'price' => 149.99, // السعر كرقم عشري
            'category' => 'category 1', // التصنيف

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

// اختصار عشان نوصل للمصفوفة مباشرة ونعدّل عليها
$products = &$_SESSION['products'];

// لائحة التصنيفات عشان نبني خيارات القائمة بـ لوب بدل الثابت
$categories = ['category 1', 'category 2', 'category 3']; // لو حاب تزود، بس ضيف هون


$errors = []; // بنلمّ الأخطاء ونرجع نعرضها فوق الفورم
$submittedData = []; // بنحفظ المدخلات اللي كتبها المستخدم عشان ما تروح لو صار خطأ
$successMessage = null; // رسالة نجاح مؤقتة (بتنمسح بعد العرض)

/*  حذف منتج (POST) */
// لو المستخدم كبس حذف من المودال، بنوصل الـ id وبنشيله من المصفوفة
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id']; // بنحوّل لـ int للتأكد
    foreach ($products as $k => $p) { // بنلف على المنتجات
        if ((int)$p['id'] === $deleteId) { // لقينا نفس الـ id
            unset($products[$k]); // بنمسح العنصر
            $_SESSION['products'] = array_values($products); // بنرتّب الفهارس من جديد
            $_SESSION['successMessage'] = 'Product deleted successfully.'; // رسالة نجاح
            header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); // ريفريش مع إزالة أي باراميتر
            exit; // لازم نوقف السكربت بعد الهيدر
        }
    }
}

/*  نمط التعديل عبر ?edit_id (Challenge) */
// لو في edit_id بالرابط، بنجيب المنتج ونعبّي نفس فورم الإضافة بالقيم تبعته
$editRecord = null; // هذا اللي رح يعبّي الفورم لو وضع تعديل
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id']; // منحوّله لعدد
    foreach ($products as $p) { // بندوّر على المنتج
        if ((int)$p['id'] === $editId) {
            $editRecord = $p; // لقيناه — بنجهّزه للفورم
            break;
        }
    }
}

/* إضافة/تحديث منتج (POST) */
// نفس الفورم بيخدم الإضافة والتعديل — بنقرّر حسب وجود hidden id
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_id'])) {
    // تنظيف أولي للمدخلات — بنشيل فراغات أول/آخر
    $name        = trim($_POST['name'] ?? ''); // اسم المنتج
    $description = trim($_POST['description'] ?? ''); // وصف
    $price       = trim($_POST['price'] ?? ''); // سعر كنص — رح نتأكد إنه رقم
    $category    = trim($_POST['category'] ?? ''); // تصنيف


    // بنحفظ اللي كتبه المستخدم عشان ما يروح إذا صار خطأ
    $submittedData = [
        'name' => $name,
        'description' => $description,
        'price' => $price,
        'category' => $category,

    ];

    // تحقق الاسم: لازم يكون موجود وطوله معقول
    if ($name === '' || mb_strlen($name) < 3) {
        $errors['name'] = 'Please enter a valid product name (min 3 chars).'; // خطأ للاسم
    }
    // تحقق الوصف: ما ينفع فاضي وقصير
    if ($description === '' || mb_strlen($description) < 5) {
        $errors['description'] = 'Please enter a meaningful description (min 5 chars).'; // خطأ للوصف
    }
    // تحقق السعر: لازم رقم موجب
    if ($price === '' || !is_numeric($price) || (float)$price <= 0) {
        $errors['price'] = 'Price must be a positive number.'; // خطأ للسعر
    }
    // تحقق التصنيف: لازم يكون واحد من القائمة المعرفة فوق
    if (!in_array($category, $categories, true)) {
        $errors['category'] = 'Please choose a valid category.'; // خطأ للتصنيف
    }
   


    // لو ما في ولا خطأ — منكمّل إضافة/تعديل
    if (empty($errors)) {
        if (isset($_POST['id']) && $_POST['id'] !== '') {
            // وضع تعديل: بنمسك المنتج ونحدّث حقوله
            $pid = (int)$_POST['id'];
            foreach ($products as &$p) { // بالمرجع عشان نعدّل مباشرة
                if ((int)$p['id'] === $pid) {
                    $p['name'] = $name;
                    $p['description'] = $description;
                    $p['price'] = (float)$price;
                    $p['category'] = $category;

                    break; // خلص عدّلنا
                }
            }
            $_SESSION['successMessage'] = 'Product updated successfully.'; // رسالة نجاح للتعديل
        } else {
            // وضع إضافة: بنولّد id جديد بناءً على أعلى id موجود
            $newId = count($products) ? (max(array_column($products, 'id')) + 1) : 1; // id جديد
            $products[] = [ // بنضيف العنصر للمصفوفة
                'id' => $newId,
                'name' => $name,
                'description' => $description,
                'price' => (float)$price,
                'category' => $category,

            ];
            $_SESSION['successMessage'] = 'Product added successfully.'; // رسالة نجاح للإضافة
        }

        // بنصفّر المدخلات عشان يرجع الفورم نظيف بعد التمرير
        $submittedData = [];
        // وبنشيل أي edit_id من الرابط قبل ما نرجع
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?')); // ريفريش نظيف
        exit; // لازم نوقف بعد الهيدر
    }
}

// لو في رسالة نجاح متخزّنة بالسيشن بنسحبها ونمسحها
if (isset($_SESSION['successMessage'])) {
    $successMessage = $_SESSION['successMessage']; // بنعرضها مرة واحدة
    unset($_SESSION['successMessage']); // بعدين بنشيلها من السيشن
}
?>
<!DOCTYPE html>


<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- عرض مناسب للموبايل -->
    <title>Products Management</title> <!-- عنوان الصفحة اللي بيظهر بالتبويبة -->

    <!-- Bootstrap CDN: بنستدعي CSS من الإنترنت مباشرة -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- ستايلات جاهزة -->

    <style>
        :root {
            --brand: #556b2f;
          
            --brand2: #445626;
          
            --accent: #e6b800;
           
            --border: #e2d7c3;
          
            --txt: #1c1c1c;
        }

        body {
            /* ستايل الجسم العام */
            background: linear-gradient(135deg, #fffdf9, #f8f1e4);
            min-height: 100vh;
            /* طول الشاشة كامل */
            padding: 20px;
            /* مسافة حوالين المحتوى */
            color: var(--txt);
            /* لون النص الافتراضي */
        }

        .container {
            /* الكارد الرئيسي */
            background: linear-gradient(135deg, #faf4ea, #f2e7d8);
            /* تدرّج خفيف */
            padding: 24px;
            /* مسافة داخلية */
            border-radius: 14px;
            /* زوايا ناعمة */
            border: 1px solid var(--border);
            /* حدود خفيفة */
        }

        .table {
            /* تحسينات بسيطة للجدول */
            --bs-table-striped-bg: rgba(0, 0, 0, .02);
            /* شرائط خفيفة */
            --bs-table-hover-bg: rgba(0, 0, 0, .04);
            /* لون هوفر للسطر */
        }

        .btn-primary {
            background: var(--brand);
            border-color: var(--brand2)
        }

        /* زر أساسي */
        .btn-warning {
            background: var(--accent);
            border-color: var(--accent);
            color: #111
        }

        /* زر تعديل */
        img.thumb {
            /* صورة المنتج بالجدول */
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border)
        }
    </style>
</head>

<body>
    <div class="container mt-3"> <!-- كونتينر مرتب مع مارجن فوق -->
        <h1 class="text-center mb-4">Products Management</h1> <!-- العنوان الرئيسي بالنص -->

        <?php if ($successMessage): ?> <!-- لو في رسالة نجاح نعرضها -->
            <div class="alert alert-success"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?></div> <!-- طباعة آمنة -->
        <?php endif; ?>

        <?php if (!empty($errors)): ?> <!-- لو في أخطاء، رسالة عامة فوق -->
            <div class="alert alert-danger">
                Please fix the errors below. <!-- نص بسيط للمستخدم -->
            </div>
        <?php endif; ?>

        <!-- فورم الإضافة/التعديل (نفسه للاثنين) -->
         
        <?php
        // منحدّد مصدر تعبئة الحقول: لو في editRecord بنستخدمه، غير هيك submittedData
        $formData = $editRecord ?: $submittedData; // بيانات للفورم
        $isEditing = $editRecord !== null; // لو true معناها تعديل
        ?>
        <h3><?= $isEditing ? 'Edit product' : 'Add new product' ?></h3> <!-- عنوان ديناميكي حسب الحالة -->
        <form method="POST" class="mb-4"> <!-- بنبعت لنفس الصفحة -->
            <?php if ($isEditing): ?> <!-- لو تعديل، بنمرر hidden id -->
                <input type="hidden" name="id" value="<?= (int)$editRecord['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label class="form-label">name product</label> <!-- لابل -->
                <input type="text" name="name"
                    class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($formData['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"> <!-- بنعيد القيمة المكتوبة -->
                <div class="invalid-feedback"><?= htmlspecialchars($errors['name'] ?? '', ENT_QUOTES, 'UTF-8') ?></div> <!-- نص الخطأ -->
            </div>

            <div class="mb-3">
                <label class="form-label">description product</label> <!-- لابل الوصف -->
                <textarea name="description"
                    class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                    rows="3"><?= htmlspecialchars($formData['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea> <!-- القيمة -->
                <div class="invalid-feedback"><?= htmlspecialchars($errors['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></div> <!-- خطأ -->
            </div>

            <div class="mb-3">
                <label class="form-label">the price</label> <!-- لابل السعر -->
                <input type="text" name="price"
                    class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                    value="<?= htmlspecialchars($formData['price'] ?? '', ENT_QUOTES, 'UTF-8') ?>"> <!-- القيمة -->
                <div class="invalid-feedback"><?= htmlspecialchars($errors['price'] ?? '', ENT_QUOTES, 'UTF-8') ?></div> <!-- خطأ -->
            </div>

            <div class="mb-3">
                <label class="form-label">category</label> <!-- لابل التصنيف -->
                <select name="category" class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>"> <!-- قائمة -->
                    <?php foreach ($categories as $cat): ?> <!-- بنبني الخيارات بـ لوب -->
                        <?php
                        $selectedVal = $formData['category'] ?? ''; // القيمة المختارة
                        $sel = ($selectedVal === $cat) ? 'selected' : ''; // تحديد الخيار
                        ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $sel ?>>
                            <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['category'] ?? '', ENT_QUOTES, 'UTF-8') ?></div> <!-- خطأ -->
            </div>



            <button type="submit" class="btn btn-primary">
                <?= $isEditing ? 'Update Product' : 'Add Product' ?> <!-- نص الزر حسب الحالة -->
            </button>
            <?php if ($isEditing): ?> <!-- زر إلغاء برجعك لوضع إضافة -->
                <a class="btn btn-secondary ms-2" href="<?= htmlspecialchars(strtok($_SERVER['REQUEST_URI'], '?'), ENT_QUOTES, 'UTF-8') ?>">Cancel</a>
            <?php endif; ?>
        </form>

        <!-- جدول المنتجات -->
        <h3 class="mt-4">List Products</h3> <!-- عنوان الجدول -->
        <div class="table-responsive"> <!-- يسهّل العرض بالموبايل -->
            <table class="table table-striped align-middle"> <!-- جدول بخطوط وهوفر -->
                <thead>
                    <tr>
                        <th>ID</th> <!-- عمود رقم -->
                        <th>name</th> <!-- اسم -->
                        <th>description</th> <!-- وصف -->
                        <th>price</th> <!-- سعر -->
                        <th>category</th> <!-- تصنيف -->
                        <th>procedures</th> <!-- إجراءات -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?> <!-- لو القائمة فاضية -->
                        <tr>
                            <td colspan="7" class="text-center text-muted">No products yet — add your first product.</td>
                        </tr> <!-- رسالة تشجيع -->
                    <?php else: ?> <!-- غير هيك بنعرض العناصر -->
                        <?php foreach ($products as $p): ?> <!-- بنلف على المنتجات -->
                            <tr>
                                <td><?= (int)$p['id'] ?></td> <!-- id آمن -->

                                <td><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></td> <!-- اسم آمن -->
                                <td><?= htmlspecialchars($p['description'], ENT_QUOTES, 'UTF-8') ?></td> <!-- وصف آمن -->
                                <td>
                                    <?php
                                    $fmt = number_format((float)$p['price'], 2); // تنسيق السعر بنقطتين عشريتين
                                    echo htmlspecialchars($fmt, ENT_QUOTES, 'UTF-8'); // طباعة آمنة
                                    ?>
                                </td>
                                <td><?= htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8') ?></td> <!-- تصنيف آمن -->
                                <td class="d-flex gap-2"> <!-- أزرار جنبا لجنب -->
                                    <!-- زر تعديل بالطريقة المطلوبة: باراميتر بالرابط -->
                                    <a class="btn btn-warning btn-sm" href="?edit_id=<?= (int)$p['id'] ?>">edit</a> <!-- يفتح نفس الصفحة بوضع تعديل -->

                                    <!-- زر حذف بمودال تأكيد عشان الأمان -->
                                    <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#del<?= (int)$p['id'] ?>">delete</button>

                                    <!-- مودال الحذف: بيطلب تأكيد قبل المسح -->
                                    <div class="modal fade" id="del<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true"> <!-- مودال لكل منتج -->
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">confirm deletion</h5> <!-- عنوان المودال -->
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button> <!-- إغلاق -->
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete "<?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?>"? <!-- نص التأكيد -->
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST"> <!-- POST للحذف -->
                                                        <input type="hidden" name="delete_id" value="<?= (int)$p['id'] ?>"> <!-- بنمرر id المنتج -->
                                                        <button type="submit" class="btn btn-danger">delete</button> <!-- تأكيد -->
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">cancel</button> <!-- تراجع -->
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?> <!-- نهاية لف المنتجات -->
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS Bundle: ضروري لتشغيل المودالات والأزرار المنسدلة -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script> <!-- بوبّبر مدموج -->
</body>

</html>