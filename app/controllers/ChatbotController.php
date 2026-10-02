<?php
/**
 * ============================================================
 *  SPORT SHOP – Chatbot AI Controller (Thông minh & Đa nhiệm)
 *  File: app/controllers/ChatbotController.php
 * ============================================================
 */

require_once APP_PATH . '/models/ProductModel.php';

class ChatbotController extends Controller
{
    private ProductModel $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    /**
     * Endpoint nhận tin nhắn từ người dùng và phản hồi qua AI
     * POST /chatbot/send
     */
    public function send(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
            exit;
        }

        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $userMessage = trim((string) ($input['message'] ?? ''));
        $chatHistory = is_array($input['history'] ?? null) ? $input['history'] : [];

        if ($userMessage === '') {
            echo json_encode(['success' => false, 'message' => 'Nội dung tin nhắn không được để trống.']);
            exit;
        }

        // Lấy thông tin người dùng đang đăng nhập (nếu có)
        $authUser = $_SESSION['auth_user'] ?? null;
        $orderContext = '';
        $userRecentOrders = [];
        if ($authUser && !empty($authUser['id'])) {
            try {
                $db = Database::getInstance();
                $userRecentOrders = $db->fetchAll(
                    "SELECT id, total, status_order, date FROM orders WHERE user_id = ? ORDER BY date DESC LIMIT 3",
                    [(int) $authUser['id']]
                );
                if ($userRecentOrders) {
                    $orderContext = "\n- Đơn hàng gần đây của khách hàng ({$authUser['username']}):\n";
                    $statusMap = [
                        'Cho_Thanh_Toan' => 'Chờ thanh toán',
                        'Dang_Xu_Ly' => 'Đang xử lý',
                        'Dang_Giao' => 'Đang giao hàng',
                        'Da_Giao' => 'Đã giao hàng',
                        'Da_Nhan_Hang' => 'Khách đã nhận hàng',
                        'Yeu_Cau_Tra_Hang' => 'Yêu cầu trả hàng',
                        'Da_Tra_Hang' => 'Đã trả hàng',
                        'Yeu_Cau_Huy' => 'Yêu cầu hủy',
                        'Da_Huy' => 'Đã hủy'
                    ];
                    foreach ($userRecentOrders as $ro) {
                        $st = $statusMap[$ro['status_order']] ?? $ro['status_order'];
                        $pr = number_format((int) $ro['total'], 0, ',', '.') . ' đ';
                        $orderContext .= "  + Đơn #{$ro['id']} - Trạng thái: {$st} - Tổng tiền: {$pr} - Ngày đặt: {$ro['date']}\n";
                    }
                }
            } catch (Throwable $e) {}
        }

        // Lấy toàn bộ sản phẩm đang bán tại cửa hàng
        $products = [];
        try {
            $db = Database::getInstance();
            $products = $db->fetchAll(
                "SELECT p.id, p.name, p.price, p.discount, p.color, p.sizes, b.name_brand, c.name_category,
                        (SELECT img.image_link FROM image_product img WHERE img.product_id = p.id ORDER BY img.id DESC LIMIT 1) AS thumbnail
                 FROM product p
                 LEFT JOIN brand_product b ON p.brand_id = b.id
                 LEFT JOIN category_product c ON p.category_id = c.id
                 WHERE p.status_product = 'Active'
                 ORDER BY p.id ASC"
            );
        } catch (Throwable $e) {}

        $productCatalogText = "DANH SÁCH SẢN PHẨM THỜI TRANG TẠI TRENDSTYLE FASHION:\n";
        foreach ($products as $p) {
            $finalPrice = ProductModel::calcFinalPrice((int) $p['price'], (int) ($p['discount'] ?? 0));
            $formattedPrice = number_format($finalPrice, 0, ',', '.') . ' đ';
            $discountText = $p['discount'] > 0 ? " (Giảm {$p['discount']}%, Giá gốc: " . number_format((int) $p['price'], 0, ',', '.') . " đ)" : "";
            $sizeList = !empty($p['sizes']) ? $p['sizes'] : 'S, M, L, XL';
            $productCatalogText .= "- ID: {$p['id']} | Tên: {$p['name']} | Thương hiệu: {$p['name_brand']} | Danh mục: {$p['name_category']} | Giá bán: {$formattedPrice}{$discountText} | Màu: {$p['color']} | Size có sẵn: {$sizeList}\n";
        }

        // System Instruction cho AI Stylist
        $systemPrompt = "Bạn là 'AI Stylist – TrendStyle' - Chuyên gia tư vấn thời trang & Stylist cá nhân thông minh của TrendStyle Fashion.
Quy tắc trả lời:
1. Luôn trả lời bằng Tiếng Việt thân thiện, lịch thiệp, xưng hô 'TrendStyle' (hoặc 'em/mình') và 'bạn' (hoặc 'anh/chị').
2. Trả lời ĐÚNG TRỌNG TÂM & CHUYÊN MÔN THỜI TRANG:
   - Nếu khách hỏi về phối đồ (Mix & Match): Gợi ý set đồ hoàn chỉnh (áo + quần/váy + phụ kiện) theo hoàn cảnh (đi làm công sở, dự tiệc sang trọng, dạo phố năng động, du lịch hè/đông).
   - Nếu khách hỏi về chọn size: Hỏi chiều cao, cân nặng và dáng người, sau đó đối chiếu bảng size chuẩn (S: <53kg, M: 54-62kg, L: 63-70kg, XL: 71-78kg, XXL: >79kg) để tư vấn chính xác.
   - Nếu khách hỏi về thương hiệu (Zara, Uniqlo, Coolmate, H&M, Mango): Giới thiệu các mẫu của thương hiệu đó và ưu điểm chất liệu (Cotton Pima, Lụa Satin, Vải dệt Oxford...).
   - Nếu khách hỏi về giá hoặc khuyến mãi: Lọc sản phẩm đúng tầm giá và nêu bật ưu đãi.
3. ĐỀ XUẤT THÊM LỰA CHỌN & PHỐI OUTFIT: Luôn đề xuất thêm 1 gợi ý phối đồ hoặc hỏi thêm về phong cách/hoàn cảnh sử dụng để phục vụ tốt nhất.
4. Trả lời súc tích, định dạng gạch đầu dòng rõ ràng, sử dụng emoji thời trang sinh động (✨, 👗, 👔, 👖, 💎, 🌟).

{$productCatalogText}
{$orderContext}";

        $apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '';
        $aiResponseText = '';
        $suggestions = [];

        if ($apiKey !== '') {
            $aiResponseText = $this->callGeminiApi($apiKey, $systemPrompt, $userMessage, $chatHistory);
        }

        // Xử lý bằng Bộ não Phân tích Ý định Chuyên sâu Thời Trang
        $smartResult = $this->analyzeIntentAndRespond($userMessage, $products, $authUser, $userRecentOrders);

        // Nếu Gemini không khả dụng hoặc trả lời chung chung, dùng kết quả phân tích chuyên sâu
        if ($aiResponseText === '') {
            $aiResponseText = $smartResult['reply'];
            $suggestions = $smartResult['suggestions'];
            $matchedProducts = $smartResult['products'];
        } else {
            $matchedProducts = $this->findRelevantProducts($userMessage, $aiResponseText, $products);
            $suggestions = $smartResult['suggestions'];
        }

        echo json_encode([
            'success' => true,
            'reply' => $aiResponseText,
            'products' => $matchedProducts,
            'suggestions' => $suggestions
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Bộ não phân tích ý định sâu & tạo phản hồi đúng trọng tâm chuyên môn Stylist
     */
    private function analyzeIntentAndRespond(string $query, array $allProducts, ?array $user, array $orders): array
    {
        $q = mb_strtolower(trim($query), 'UTF-8');
        $reply = '';
        $matched = [];
        $suggestions = [];

        // 1. KIỂM TRA Ý ĐỊNH THƯƠNG HIỆU THỜI TRANG (Zara, Uniqlo, Coolmate, H&M, Mango)
        $brandMap = [
            'zara' => 'Zara',
            'uniqlo' => 'Uniqlo',
            'coolmate' => 'Coolmate',
            'h&m' => 'H&M',
            'hm' => 'H&M',
            'mango' => 'Mango'
        ];

        $detectedBrand = null;
        foreach ($brandMap as $k => $brandName) {
            if (preg_match('/\b' . preg_quote($k, '/') . '\b/u', $q) || str_contains($q, $k)) {
                $detectedBrand = $brandName;
                break;
            }
        }

        if ($detectedBrand) {
            $brandProducts = array_values(array_filter($allProducts, fn($p) => strcasecmp($p['name_brand'] ?? '', $detectedBrand) === 0));

            if (!empty($brandProducts)) {
                $reply = "Dạ, các sản phẩm thời trang **{$detectedBrand} chính hãng** hiện đang có sẵn tại TrendStyle gồm:\n\n";
                foreach ($brandProducts as $p) {
                    $final = ProductModel::calcFinalPrice((int) $p['price'], (int) ($p['discount'] ?? 0));
                    $disc = $p['discount'] > 0 ? " (🔥 Giảm {$p['discount']}%)" : "";
                    $reply .= "✨ **{$p['name']}**\n";
                    $reply .= "   • Giá bán: **" . number_format($final, 0, ',', '.') . " đ**{$disc}\n";
                    $reply .= "   • Danh mục: {$p['name_category']} | Màu: {$p['color']} | Size: " . ($p['sizes'] ?? 'S, M, L, XL') . "\n\n";
                    $matched[] = $this->formatProductCard($p);
                }

                if ($detectedBrand === 'Zara') {
                    $reply .= "💡 **Điểm nổi bật của Zara:** Thiết kế thời thượng, form dáng chuẩn phong cách Minimalism và chất liệu đứng form cao cấp.\n\n";
                    $reply .= "👉 Bạn muốn tìm set đồ đi làm thanh lịch hay outfit dạo phố năng động cùng Zara?";
                    $suggestions = ['Áo Blazer Unisex Zara', 'Áo Khoác Bomber Zara', 'Tư vấn phối đồ cùng Zara', 'Cách chọn size Zara'];
                } elseif ($detectedBrand === 'Uniqlo') {
                    $reply .= "💡 **Điểm nổi bật của Uniqlo:** Chất liệu cao cấp bền bỉ, tối giản và mang lại sự thoải mái tuyệt đối cho người mặc.\n\n";
                    $reply .= "👉 Bạn đang tìm sơ mi công sở hay áo khoác bảo vệ thời tiết?";
                    $suggestions = ['Áo Sơ Mi Oxford Uniqlo', 'Chân Váy Xếp Ly Uniqlo', 'Tư vấn size Uniqlo', 'Sản phẩm Uniqlo hot'];
                } elseif ($detectedBrand === 'Coolmate') {
                    $reply .= "💡 **Điểm nổi bật của Coolmate:** Sợi bông Pima & Cotton Spandex co giãn 4 chiều, siêu thoáng mát và thấm hút mồ hôi cực tốt.\n\n";
                    $reply .= "👉 Bạn thích dòng **Áo Polo Pima Cotton** hay **Quần Kaki Co Giãn** của Coolmate?";
                    $suggestions = ['Áo Polo Pima Cotton', 'Quần Kaki Co Giãn', 'Bảng size Coolmate theo cân nặng', 'Ưu đãi Coolmate'];
                } elseif ($detectedBrand === 'Mango') {
                    $reply .= "💡 **Điểm nổi bật của Mango:** Phong cách quý phái, đường cắt may tỉ mỉ tôn trọn nét quyến rũ và nữ tính.\n\n";
                    $reply .= "👉 Bạn đang tìm đầm dạ hội dự tiệc hay trang phục nhẹ nhàng thanh lịch?";
                    $suggestions = ['Đầm Dạ Hội Lụa Satin', 'Váy thiết kế Mango', 'Tư vấn phối đồ Mango', 'Bảng size đầm Mango'];
                } else {
                    $reply .= "👉 Bạn muốn tư vấn chọn size chuẩn hay muốn Stylist gợi ý phối đồ cùng {$detectedBrand}?";
                    $suggestions = ["Tư vấn size {$detectedBrand}", "Sản phẩm {$detectedBrand} giảm giá", "Gợi ý phối đồ"];
                }

                return ['reply' => $reply, 'products' => $matched, 'suggestions' => $suggestions];
            }
        }

        // 2. Ý ĐỊNH TƯ VẤN PHỐI ĐỒ / MIX & MATCH
        if (str_contains($q, 'phối đồ') || str_contains($q, 'outfit') || str_contains($q, 'mặc gì') || str_contains($q, 'công sở') || str_contains($q, 'dự tiệc') || str_contains($q, 'đi tiệc') || str_contains($q, 'hẹn hò')) {
            $reply = "🌟 **Gợi ý Set Đồ (Outfit) Chuẩn Phong Cách Dành Cho Bạn:**\n\n";
            if (str_contains($q, 'công sở') || str_contains($q, 'đi làm')) {
                $reply .= "👔 **Set Đồ Công Sở Thanh Lịch & Đẳng Cấp:**\n";
                $reply .= "• **Tone Nam:** Áo Sơ Mi Oxford Trắng (Uniqlo) + Quần Kaki Xám Tro (Coolmate) hoặc khoác thêm Blazer Be/Nâu (Zara).\n";
                $reply .= "• **Tone Nữ:** Áo Blazer Unisex + Chân Váy Xếp Ly Kem Be hoặc Đầm Suông thanh thoát.\n";
                $reply .= "• *Mẹo Stylist:* Phối kèm thắt lưng da tối màu và giày tây / loafer để hoàn thiện vẻ ngoài chuyên nghiệp!\n\n";
            } elseif (str_contains($q, 'dự tiệc') || str_contains($q, 'tiệc')) {
                $reply .= "💎 **Set Đồ Dự Tiệc Sang Trọng & Cuốn Hút:**\n";
                $reply .= "• **Dành cho Nữ:** Đầm Dạ Hội Lụa Satin Đỏ Rượu (Mango) – chất lụa bóng nhẹ quý phái, phối cùng giày cao gót và clutch ánh kim.\n";
                $reply .= "• **Dành cho Nam:** Áo Blazer Unisex Form Rộng phối áo sơ mi trắng + Quần Jean Slimfit đen sang trọng.\n\n";
            } else {
                $reply .= "✨ **Outfit Dạo Phố / Hẹn Hò Năng Động (Street Style):**\n";
                $reply .= "• Áo Polo Pima Cotton Xanh Navy + Quần Jean Slimfit Denim + Áo Khoác Bomber Gió.\n";
                $reply .= "• Vừa trẻ trung, thoải mái vận động lại cực kỳ tôn dáng và bắt trọn ánh nhìn!\n\n";
            }
            $reply .= "👉 Dưới đây là các sản phẩm nổi bật trong gợi ý outfit dành cho bạn:";

            foreach (array_slice($allProducts, 0, 3) as $p) {
                $matched[] = $this->formatProductCard($p);
            }

            $suggestions = ['Tư vấn size theo chiều cao cân nặng', 'Xem Áo Blazer Zara', 'Xem Đầm Dạ Hội Mango', 'Áo Polo Pima Cotton'];
            return ['reply' => $reply, 'products' => $matched, 'suggestions' => $suggestions];
        }

        // 3. Ý ĐỊNH TƯ VẤN CHỌN SIZE THEO CHIỀU CAO / CÂN NẶNG
        if (str_contains($q, 'size') || str_contains($q, 'kích cỡ') || str_contains($q, 'cân nặng') || str_contains($q, 'chiều cao') || preg_match('/\d{2,3}\s*kg/i', $q) || preg_match('/1m\d{2}/i', $q)) {
            // Kiểm tra nếu có cân nặng cụ thể (VD: 65kg, 70kg, 55kg)
            $weight = null;
            if (preg_match('/(\d{2,3})\s*kg/i', $q, $wMatch)) {
                $weight = (int) $wMatch[1];
            }

            if ($weight !== null) {
                $suggestedSize = 'M';
                if ($weight <= 53) {
                    $suggestedSize = 'S';
                } elseif ($weight <= 62) {
                    $suggestedSize = 'M';
                } elseif ($weight <= 70) {
                    $suggestedSize = 'L';
                } elseif ($weight <= 78) {
                    $suggestedSize = 'XL';
                } else {
                    $suggestedSize = 'XXL';
                }

                $reply = "📏 **Gợi ý Size Chuẩn Dành Cho Bạn:**\n\n";
                $reply .= "Với mức cân nặng **{$weight}kg**, bạn nên chọn **Size {$suggestedSize}** để mặc vừa vặn và tôn dáng nhất!\n\n";
                $reply .= "💡 **Mẹo chọn form:**\n";
                $reply .= "• Nếu thích mặc vừa vặn, gọn gàng (Slim-fit) ➔ Chọn đúng **Size {$suggestedSize}**.\n";
                $reply .= "• Nếu thích mặc rộng rãi, thoải mái hoặc phong cách Oversize ➔ Bạn có thể cân nhắc tăng **+1 size** nhé!\n\n";
                $reply .= "👉 TrendStyle có đầy đủ **Size {$suggestedSize}** cho tất cả các mẫu áo sơ mi, polo, blazer và váy đầm dưới đây:";

                foreach (array_slice($allProducts, 0, 3) as $p) {
                    $matched[] = $this->formatProductCard($p);
                }

                $suggestions = ["Xem sản phẩm có Size {$suggestedSize}", 'Chính sách đổi trả nếu không vừa', 'Gợi ý phối đồ với Size này'];
                return ['reply' => $reply, 'products' => $matched, 'suggestions' => $suggestions];
            }

            $reply = "📏 **Bảng Quy Đổi Size Quần Áo Chuẩn Tại TrendStyle Fashion:**\n\n";
            $reply .= "• **Size S:** Chiều cao 1m50 - 1m60 | Cân nặng **45 - 53 kg**\n";
            $reply .= "• **Size M:** Chiều cao 1m60 - 1m68 | Cân nặng **54 - 62 kg**\n";
            $reply .= "• **Size L:** Chiều cao 1m68 - 1m75 | Cân nặng **63 - 70 kg**\n";
            $reply .= "• **Size XL:** Chiều cao 1m75 - 1m82 | Cân nặng **71 - 78 kg**\n";
            $reply .= "• **Size XXL:** Chiều cao 1m80 - 1m88 | Cân nặng **79 - 88 kg**\n\n";
            $reply .= "👉 Hãy cho Stylist biết **chiều cao & cân nặng** của bạn (ví dụ: *1m72 65kg*) để được tư vấn size chuẩn xác 100% nhé!";
            $suggestions = ['Tôi cao 1m72 nặng 65kg', 'Tôi cao 1m65 nặng 55kg', 'Tôi cao 1m78 nặng 75kg', 'Chính sách đổi size 30 ngày'];
            return ['reply' => $reply, 'products' => [], 'suggestions' => $suggestions];
        }

        // 4. Ý ĐỊNH KHUYẾN MÃI / GIẢM GIÁ
        if (str_contains($q, 'khuyến mãi') || str_contains($q, 'giảm giá') || str_contains($q, 'sale') || str_contains($q, 'ưu đãi')) {
            $saleProducts = $allProducts;
            usort($saleProducts, fn($a, $b) => ($b['discount'] ?? 0) <=> ($a['discount'] ?? 0));
            $saleProducts = array_slice($saleProducts, 0, 3);

            $reply = "🔥 **Các sản phẩm thời trang cao cấp đang có Ưu Đãi Lớn Nhất hôm nay:**\n\n";
            foreach ($saleProducts as $p) {
                $final = ProductModel::calcFinalPrice((int) $p['price'], (int) ($p['discount'] ?? 0));
                $reply .= "🎁 **{$p['name']}** - **Giảm {$p['discount']}%**\n";
                $reply .= "   • Giá ưu đãi: **" . number_format($final, 0, ',', '.') . " đ** (Giá gốc: " . number_format((int) $p['price'], 0, ',', '.') . " đ)\n\n";
                $matched[] = $this->formatProductCard($p);
            }
            $reply .= "👉 Đơn hàng từ **499.000đ** được **Freeship toàn quốc** và hỗ trợ đổi trả 30 ngày. Bạn muốn đặt giữ size sản phẩm nào?";
            $suggestions = ['Áo Khoác Bomber giảm 25%', 'Áo Blazer Unisex giảm 20%', 'Đầm Dạ Hội giảm 18%', 'Tư vấn chọn size'];
            return ['reply' => $reply, 'products' => $matched, 'suggestions' => $suggestions];
        }

        // 5. Ý ĐỊNH KIỂM TRA ĐƠN HÀNG
        if (str_contains($q, 'đơn hàng') || str_contains($q, 'giao hàng') || str_contains($q, 'vận chuyển') || str_contains($q, 'mã đơn')) {
            if ($user && !empty($orders)) {
                $statusMap = [
                    'Cho_Thanh_Toan' => 'Chờ thanh toán',
                    'Dang_Xu_Ly' => 'Đang chuẩn bị gói hàng',
                    'Dang_Giao' => 'Đang trên đường giao',
                    'Da_Giao' => 'Đã giao - Chờ xác nhận',
                    'Da_Nhan_Hang' => 'Khách đã nhận hàng',
                    'Da_Huy' => 'Đã hủy'
                ];
                $reply = "📦 **Thông tin đơn hàng thời trang của bạn ({$user['username']}):**\n\n";
                foreach ($orders as $od) {
                    $st = $statusMap[$od['status_order']] ?? $od['status_order'];
                    $total = number_format((int) $od['total'], 0, ',', '.') . ' đ';
                    $reply .= "• **Đơn #{$od['id']}** | Trạng thái: **{$st}** | Tổng: {$total}\n";
                }
                $reply .= "\n👉 Bạn có thể xem chi tiết hành trình tại mục **[Lịch sử đơn hàng](" . BASE_URL . "/user/orders)**.";
                $suggestions = ['Thời gian giao hàng bao lâu?', 'Chính sách đổi trả 30 ngày', 'Tiếp tục mua sắm'];
            } else {
                $reply = "🚚 **Chính sách giao hàng của TrendStyle Fashion:**\n\n";
                $reply .= "• **Miễn phí vận chuyển toàn quốc** cho đơn hàng từ 499.000đ.\n";
                $reply .= "• Thời gian giao hàng hỏa tốc: **1 - 3 ngày làm việc**.\n";
                $reply .= "• Được kiểm tra hàng trước khi thanh toán (COD) và đổi trả trong 30 ngày.\n\n";
                $reply .= "👉 Nếu bạn đã đặt hàng, vui lòng **[Đăng nhập](" . BASE_URL . "/auth/login)** để Stylist tra cứu đơn hàng giúp bạn nhé!";
                $suggestions = ['Đăng nhập tài khoản', 'Chính sách đổi trả trong 30 ngày', 'Khám phá bộ sưu tập mới'];
            }
            return ['reply' => $reply, 'products' => [], 'suggestions' => $suggestions];
        }

        // 6. MẶC ĐỊNH / CHÀO HỎI
        $sampleProducts = array_slice($allProducts, 0, 3);
        $reply = "Xin chào bạn! ✨ Tôi là **AI Stylist – TrendStyle**, chuyên gia tư vấn phong cách và thời trang cá nhân của bạn.\n\n";
        $reply .= "Tôi có thể hỗ trợ bạn ngay hôm nay:\n";
        $reply .= "1️⃣ **Tư vấn Outfit:** Gợi ý phối đồ đi làm công sở, dự tiệc sang trọng hay dạo phố năng động.\n";
        $reply .= "2️⃣ **Chọn Size Chuẩn:** Đo lường size theo chiều cao & cân nặng của bạn.\n";
        $reply .= "3️⃣ **Thương hiệu Nổi Bật:** Zara, Uniqlo, Coolmate, Mango, H&M...\n\n";
        $reply .= "👉 Bạn đang tìm trang phục cho dịp nào hoặc cần Stylist tư vấn gì cứ nhắn cho tôi nhé!";

        foreach ($sampleProducts as $p) {
            $matched[] = $this->formatProductCard($p);
        }

        $suggestions = ['Gợi ý set đồ công sở lịch sự', 'Tôi cao 1m72 nặng 65kg', 'Khám phá đầm váy dự tiệc', 'Sản phẩm giảm giá hot'];
        return ['reply' => $reply, 'products' => $matched, 'suggestions' => $suggestions];
    }

    private function formatProductCard(array $p): array
    {
        $finalPrice = ProductModel::calcFinalPrice((int) $p['price'], (int) ($p['discount'] ?? 0));
        $thumbnail = !empty($p['thumbnail'])
            ? BASE_URL . '/public/images/products/' . rawurlencode(basename($p['thumbnail']))
            : BASE_URL . '/public/images/product-placeholder.svg';

        return [
            'id' => (int) $p['id'],
            'name' => $p['name'],
            'brand' => $p['name_brand'] ?? 'TrendStyle Fashion',
            'price' => number_format((int) $p['price'], 0, ',', '.') . ' đ',
            'final_price' => number_format($finalPrice, 0, ',', '.') . ' đ',
            'discount' => (int) ($p['discount'] ?? 0),
            'sizes' => $p['sizes'] ?? 'S, M, L, XL',
            'thumbnail' => $thumbnail,
            'link' => BASE_URL . '/product/detail/' . (int) $p['id']
        ];
    }

    /**
     * Gọi Google Gemini API qua cURL
     */
    private function callGeminiApi(string $apiKey, string $systemPrompt, string $userMessage, array $history): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);

        $contents = [];
        foreach ($history as $h) {
            $role = ($h['sender'] ?? '') === 'user' ? 'user' : 'model';
            $text = trim((string) ($h['text'] ?? ''));
            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]]
                ];
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

        $payload = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024
            ]
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response && $httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            $candidateText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if ($candidateText !== '') {
                return trim($candidateText);
            }
        }

        return '';
    }

    private function findRelevantProducts(string $userQuery, string $aiReply, array $allProducts): array
    {
        $matched = [];
        $queryLower = mb_strtolower($userQuery, 'UTF-8');
        $replyLower = mb_strtolower($aiReply, 'UTF-8');

        foreach ($allProducts as $p) {
            $nameLower = mb_strtolower($p['name'], 'UTF-8');
            $brandLower = mb_strtolower($p['name_brand'] ?? '', 'UTF-8');

            $isMentioned = str_contains($replyLower, $nameLower)
                        || (str_contains($queryLower, $nameLower) && strlen($nameLower) > 3)
                        || (str_contains($queryLower, $brandLower) && strlen($brandLower) > 3 && count($matched) < 2);

            if ($isMentioned && !isset($matched[$p['id']])) {
                $matched[$p['id']] = $this->formatProductCard($p);
                if (count($matched) >= 3) break;
            }
        }

        return array_values($matched);
    }
}
