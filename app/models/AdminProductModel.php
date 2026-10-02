<?php

/**
 * Truy cập dữ liệu sản phẩm dành riêng cho khu vực quản trị.
 */
class AdminProductModel
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getProducts(
        string $keyword = '',
        string $status = 'all',
        int $limit = 10,
        int $offset = 0
    ): array {
        [$whereSql, $params] = $this->buildFilters($keyword, $status);
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link
                     FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC
                     LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b ON b.id = p.brand_id
             LEFT JOIN category_product c ON c.id = p.category_id
             {$whereSql}
             ORDER BY p.id DESC
             LIMIT ? OFFSET ?",
            $params
        );
    }

    public function countProducts(string $keyword = '', string $status = 'all'): int
    {
        [$whereSql, $params] = $this->buildFilters($keyword, $status);
        $row = $this->db->fetchOne(
            "SELECT COUNT(*) AS total
             FROM product p
             LEFT JOIN brand_product b ON b.id = p.brand_id
             LEFT JOIN category_product c ON c.id = p.category_id
             {$whereSql}",
            $params
        );

        return (int) ($row['total'] ?? 0);
    }

    public function findById(int $id): array|false
    {
        $product = $this->db->fetchOne(
            "SELECT p.*, b.name_brand, c.name_category,
                    (SELECT img.image_link
                     FROM image_product img
                     WHERE img.product_id = p.id
                     ORDER BY img.id DESC
                     LIMIT 1) AS thumbnail
             FROM product p
             LEFT JOIN brand_product b ON b.id = p.brand_id
             LEFT JOIN category_product c ON c.id = p.category_id
             WHERE p.id = ?
             LIMIT 1",
            [$id]
        );

        if ($product) {
            $product['images'] = $this->getImages($id);
        }

        return $product;
    }

    public function getBrands(): array
    {
        return $this->db->fetchAll(
            'SELECT id, name_brand, status FROM brand_product ORDER BY name_brand ASC'
        );
    }

    public function getCategories(): array
    {
        return $this->db->fetchAll(
            'SELECT id, name_category, status FROM category_product ORDER BY name_category ASC'
        );
    }

    public function brandExists(int $id): bool
    {
        return $this->db->fetchOne(
            'SELECT id FROM brand_product WHERE id = ? LIMIT 1',
            [$id]
        ) !== false;
    }

    public function categoryExists(int $id): bool
    {
        return $this->db->fetchOne(
            'SELECT id FROM category_product WHERE id = ? LIMIT 1',
            [$id]
        ) !== false;
    }

    public function findOrCreateCategory(string $name): int
    {
        $name = trim($name);
        $existing = $this->db->fetchOne(
            'SELECT id, status FROM category_product WHERE name_category = ? LIMIT 1',
            [$name]
        );
        if ($existing) {
            if ($existing['status'] !== 'Active') {
                $this->db->execute(
                    "UPDATE category_product SET status = 'Active' WHERE id = ?",
                    [$existing['id']]
                );
            }
            return (int) $existing['id'];
        }

        $this->db->execute(
            "INSERT INTO category_product (name_category, status) VALUES (?, 'Active')",
            [$name]
        );
        return (int) $this->db->lastInsertId();
    }

    public function create(array $data): int
    {
        $sizes = !empty($data['sizes']) ? trim((string) $data['sizes']) : '38,39,40,41,42,43';
        $this->db->query(
            'INSERT INTO product
                (name, price, discount, quantity, quantity_sell, color, sizes, description,
                 status_product, brand_id, category_id, create_date, update_date)
             VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, NOW(6), NOW(6))',
            [
                $data['name'],
                $data['price'],
                $data['discount'],
                $data['quantity'],
                $data['color'],
                $sizes,
                $data['description'],
                $data['status_product'],
                $data['brand_id'],
                $data['category_id'],
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sizes = !empty($data['sizes']) ? trim((string) $data['sizes']) : '38,39,40,41,42,43';
        $this->db->query(
            'UPDATE product
             SET name = ?, price = ?, discount = ?, quantity = ?, color = ?, sizes = ?,
                 description = ?, status_product = ?, brand_id = ?, category_id = ?,
                 update_date = NOW(6)
             WHERE id = ?',
            [
                $data['name'],
                $data['price'],
                $data['discount'],
                $data['quantity'],
                $data['color'],
                $sizes,
                $data['description'],
                $data['status_product'],
                $data['brand_id'],
                $data['category_id'],
                $id,
            ]
        );

        return true;
    }

    public function createWithImage(array $data, ?string $imageLink): int
    {
        $this->db->beginTransaction();

        try {
            $productId = $this->create($data);
            if ($imageLink !== null) {
                $this->addImage($productId, $imageLink);
            }
            $this->db->commit();

            return $productId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateWithImage(int $id, array $data, ?string $imageLink): bool
    {
        $this->db->beginTransaction();

        try {
            $this->update($id, $data);
            if ($imageLink !== null) {
                $this->addImage($id, $imageLink);
            }
            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function toggleStatus(int $id): bool
    {
        return $this->db->execute(
            "UPDATE product
             SET status_product = CASE
                 WHEN status_product = 'Active' THEN 'Closed'
                 ELSE 'Active'
             END,
             update_date = NOW(6)
             WHERE id = ?",
            [$id]
        ) > 0;
    }

    public function softDelete(int $id): bool
    {
        $this->db->execute(
            "UPDATE product
             SET status_product = 'Closed', update_date = NOW(6)
             WHERE id = ?",
            [$id]
        );

        return $this->findById($id) !== false;
    }

    public function getImages(int $productId): array
    {
        return $this->db->fetchAll(
            'SELECT id, image_link, product_id
             FROM image_product
             WHERE product_id = ?
             ORDER BY id DESC',
            [$productId]
        );
    }

    public function addImage(int $productId, string $imageLink): int
    {
        $this->db->query(
            'INSERT INTO image_product (image_link, product_id) VALUES (?, ?)',
            [$imageLink, $productId]
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateImage(int $imageId, string $imageLink): bool
    {
        return $this->db->execute(
            'UPDATE image_product SET image_link = ? WHERE id = ?',
            [$imageLink, $imageId]
        ) > 0;
    }

    public function deleteImage(int $imageId): bool
    {
        return $this->db->execute(
            'DELETE FROM image_product WHERE id = ?',
            [$imageId]
        ) > 0;
    }

    private function buildFilters(string $keyword, string $status): array
    {
        $where = [];
        $params = [];

        if ($keyword !== '') {
            $term = '%' . $keyword . '%';
            $where[] = '(p.name LIKE ? OR b.name_brand LIKE ? OR c.name_category LIKE ?)';
            array_push($params, $term, $term, $term);
        }

        if (in_array($status, ['Active', 'Closed'], true)) {
            $where[] = 'p.status_product = ?';
            $params[] = $status;
        }

        return [
            $where ? 'WHERE ' . implode(' AND ', $where) : '',
            $params,
        ];
    }
}
