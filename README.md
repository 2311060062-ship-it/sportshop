# TrendStyle Fashion
 
Ứng dụng mua sắm thời trang cao cấp viết bằng PHP MVC thuần, MySQL và JavaScript.
Hỗ trợ quản lý sản phẩm, đơn hàng, thanh toán trực tuyến (MoMo, VNPay) và Trợ lý AI Stylist thông minh.

## Chạy trên XAMPP

1. Đặt thư mục dự án tại `C:\xampp\htdocs\SportShop`.
2. Bật Apache và MySQL.
3. Import `sports_database.sql` vào phpMyAdmin.
4. Truy cập `http://localhost/SportShop`.

Tài khoản mẫu dùng mật khẩu `password`.

Nếu database `sports` đã được tạo từ phiên bản cũ, hãy sao lưu dữ liệu trước rồi
chạy lại các phần `ALTER TABLE orders` và `CREATE TABLE payments` trong script.

## Cấu hình MoMo sandbox

Ứng dụng đọc thông tin MoMo từ biến môi trường:

- `MOMO_PARTNER_CODE`
- `MOMO_ACCESS_KEY`
- `MOMO_SECRET_KEY`
- `MOMO_REDIRECT_URL`
- `MOMO_IPN_URL`
- `MOMO_ENDPOINT` (mặc định là endpoint sandbox chính thức)

Không lưu khóa thật trong repository. Sau khi đặt biến môi trường, cần khởi động
lại Apache để PHP nhận cấu hình mới.

MoMo không gọi được `localhost`. Khi kiểm thử IPN, hãy dùng một HTTPS tunnel hoặc
máy chủ thử nghiệm công khai rồi đặt:

```text
MOMO_REDIRECT_URL=https://your-public-host/checkout/return
MOMO_IPN_URL=https://your-public-host/checkout/ipn
```

Chỉ IPN có chữ ký HMAC hợp lệ mới chuyển giao dịch sang `Paid`. Redirect từ trình
duyệt chỉ hiển thị kết quả, không xác nhận thanh toán.

## Cấu hình VNPay sandbox

Thiết lập các biến môi trường:

- `VNPAY_TMN_CODE`
- `VNPAY_HASH_SECRET`
- `VNPAY_RETURN_URL` (mặc định: `/checkout/vnpayReturn`)
- `VNPAY_ENDPOINT` (đã mặc định tới VNPay sandbox)

Trong trang quản trị VNPay sandbox, cấu hình IPN URL công khai là:

```text
https://your-public-host/checkout/vnpayIpn
```

VNPay dùng chuẩn Pay 2.1.0 và chữ ký HMAC-SHA512. Tương tự MoMo, localhost không
thể nhận IPN từ máy chủ VNPay nên cần HTTPS tunnel hoặc máy chủ thử nghiệm.

## Kiểm tra nhanh

```powershell
C:\xampp\php\php.exe -l app\controllers\CheckoutController.php
C:\xampp\php\php.exe -l app\services\MomoPaymentService.php
C:\xampp\php\php.exe tests\purchase_flow_smoke.php
```

Kiểm thử thủ công: tìm/lọc sản phẩm, thêm giỏ, đổi số lượng, xóa sản phẩm, kiểm
tra hết hàng, tạo thanh toán và gửi lặp cùng một IPN để xác nhận tính idempotent.

## Quản trị sản phẩm

ADMIN và khách hàng dùng chung trang đăng nhập:

```text
http://localhost/SportShop/auth/login
```

Sau khi đăng nhập, tài khoản `ADMIN` vào trang quản trị, tài khoản `USER` về trang chủ. Đăng ký mới luôn có vai trò `USER`. Khách hàng không mở được các trang quản trị bằng URL trực tiếp. Cổng `/admin-auth/login` vẫn chỉ nhận tài khoản ADMIN.

Tài khoản quản trị sau khi import `admin_account_reset.sql`:

```text
Tài khoản: admin
Mật khẩu: Admin@123
```

Module hỗ trợ tìm kiếm, lọc trạng thái, phân trang, tạo, cập nhật, tải ảnh và
đóng/mở sản phẩm. Thao tác xóa là xóa mềm (`Closed`) để không làm mất lịch sử
đơn hàng. Chức năng này dùng schema hiện có nên không cần import thêm SQL.

Kiểm tra CRUD quản trị:

```powershell
C:\xampp\php\php.exe tests\admin_product_smoke.php
```

## Quản lý đánh giá

Trong cổng ADMIN, mở:

```text
http://localhost/SportShop/admin-review/index
```

Chức năng gồm tìm kiếm, lọc số sao, phân trang, phản hồi khách hàng và xóa đánh
giá có xác nhận. Module sử dụng trực tiếp bảng `comment` hiện có nên không cần
import thêm SQL.
