<?php
/**
 * ============================================================
 *  SPORT SHOP – Product Model
 *  File: app/models/ProductModel.php
 *  Bảng: product, image_product, brand_product, category_product
 * ============================================================
 */

class ProductModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Lấy sản phẩm mới nhất (theo create_date)
     */
    public function getNewProducts(int $limit = 8): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.status_product = 'Active'
             ORDER BY p.create_date DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Lọc sản phẩm với các điều kiện có thể kết hợp.
     */
    public function filter(
        string $keyword = '',
        int $brandId = 0,
        int $categoryId = 0,
        bool $saleOnly = false,
        int $limit = 20,
        int $offset = 0
    ): array {
        $where = ["p.status_product = 'Active'"];
        $params = [];

        if ($keyword !== '') {
            $where[] = '(p.name LIKE ? OR b.name_brand LIKE ? OR c.name_category LIKE ?)';
            $term = '%' . $keyword . '%';
            array_push($params, $term, $term, $term);
        }
        if ($brandId > 0) {
            $where[] = 'p.brand_id = ?';
            $params[] = $brandId;
        }
        if ($categoryId > 0) {
            $where[] = 'p.category_id = ?';
            $params[] = $categoryId;
        }
        if ($saleOnly) {
            $where[] = 'p.discount > 0';
        }

        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b ON p.brand_id = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE " . implode(' AND ', $where) . "
             ORDER BY p.create_date DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    /**
     * Lấy trạng thái và tồn kho để xác thực trước khi thêm giỏ.
     */
    public function findPurchasableById(int $id): array|false
    {
        return $this->db->fetchOne(
            "SELECT id, name, quantity, status_product
             FROM product
             WHERE id = ? AND status_product = 'Active'
             LIMIT 1",
            [$id]
        );
    }

    /**
     * Lấy sản phẩm bán chạy (theo quantity_sell)
     */
    public function getHotProducts(int $limit = 8): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.status_product = 'Active'
             ORDER BY p.quantity_sell DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Lấy sản phẩm có khuyến mãi (discount > 0)
     */
    public function getSaleProducts(int $limit = 8): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.status_product = 'Active' AND p.discount > 0
             ORDER BY p.discount DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Tìm sản phẩm theo ID (kèm ảnh đầy đủ)
     */
    public function findById(int $id): array|false
    {
        $product = $this->db->fetchOne(
            "SELECT p.*, b.name_brand, c.name_category
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.id = ? AND p.status_product = 'Active'
             LIMIT 1",
            [$id]
        );

        if ($product) {
            // Lấy tất cả ảnh của sản phẩm
            $product['images'] = $this->db->fetchAll(
                "SELECT image_link
                 FROM image_product
                 WHERE product_id = ?
                 ORDER BY id DESC",
                [$id]
            );
        }

        return $product;
    }

    /**
     * Tìm kiếm sản phẩm theo tên
     */
    public function search(string $keyword, int $limit = 20, int $offset = 0): array
    {
        $kw = '%' . $keyword . '%';
        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.status_product = 'Active'
               AND (p.name LIKE ? OR b.name_brand LIKE ? OR c.name_category LIKE ?)
             ORDER BY p.create_date DESC
             LIMIT ? OFFSET ?",
            [$kw, $kw, $kw, $limit, $offset]
        );
    }

    /**
     * Lấy sản phẩm theo thương hiệu
     */
    public function getByBrand(int $brandId, int $limit = 12, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.status_product = 'Active' AND p.brand_id = ?
             ORDER BY p.create_date DESC
             LIMIT ? OFFSET ?",
            [$brandId, $limit, $offset]
        );
    }

    /**
     * Lấy sản phẩm theo danh mục
     */
    public function getByCategory(int $categoryId, int $limit = 12, int $offset = 0): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b    ON p.brand_id    = b.id
             LEFT JOIN category_product c ON p.category_id = c.id
             WHERE p.status_product = 'Active' AND p.category_id = ?
             ORDER BY p.create_date DESC
             LIMIT ? OFFSET ?",
            [$categoryId, $limit, $offset]
        );
    }

    /**
     * Lấy tất cả thương hiệu đang Active
     */
    public function getAllBrands(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM brand_product WHERE status = 'Active' ORDER BY name_brand"
        );
    }

    /**
     * Lấy tất cả danh mục đang Active
     */
    public function getAllCategories(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM category_product WHERE status = 'Active' ORDER BY name_category"
        );
    }

    /**
     * Tính giá sau giảm
     */
    public static function calcFinalPrice(int $price, int $discount): int
    {
        return (int) round($price * (1 - $discount / 100));
    }

    /**
     * Format giá tiền VND
     */
    public static function formatPrice(int $price): string
    {
        return number_format($price, 0, ',', '.') . ' đ';
    }
}
