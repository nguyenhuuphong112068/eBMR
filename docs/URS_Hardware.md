# Đề xuất Cấu hình Hạ tầng Triển khai eBMR (Hardware URS)

> Phù hợp cho 200 máy trạm hoạt động đồng thời trong môi trường sản xuất dược phẩm (GMP).

---

## 1. Máy Chủ (Server)

### Phương án A – On-Premise (Máy chủ đặt tại nhà máy)
> Phù hợp nếu nhà máy không muốn dữ liệu lưu trên cloud. Đây là lựa chọn phổ biến trong ngành dược do yêu cầu kiểm soát dữ liệu GMP (Data Residency).

#### Cấu hình đề xuất

| Thành phần | Thông số đề xuất | Ghi chú |
| :--- | :--- | :--- |
| **CPU** | Intel Xeon Gold 6334 (8C/16T, 3.6GHz) × 2 socket | Hoặc AMD EPYC 7543 × 2 |
| **RAM** | 256 GB ECC DDR4 (có thể mở rộng lên 512GB) | ECC giúp tránh lỗi bộ nhớ ngẫu nhiên |
| **Storage (OS + App)** | 2× 2TB NVMe SSD (RAID 1) | Boot drive + Laravel App |
| **Storage (Database)** | 4× 4TB NVMe SSD (RAID 10) | MySQL - cho BMS data time-series |
| **Storage (Backup)** | 4× 8TB SATA HDD (RAID 6) | Lưu trữ hình ảnh, backup DB |
| **Network Card** | Dual 25GbE (SFP+) | Khuyến nghị dùng 2 cổng bonding |
| **RAID Controller** | Dell PERC H755 hoặc HPE Smart Array P408i | |
| **UPS** | 10 kVA Online UPS (tự đủ 30 phút) | Bắt buộc cho tính liên tục |
| **IPMI/iDRAC** | Có (Remote Management) | Quản trị từ xa không cần màn hình |

**Model gợi ý:** Dell PowerEdge R750 / HPE ProLiant DL380 Gen10 Plus

**Ước tính chi phí Server:** ~150–220 triệu VND (cấu hình đầy đủ, chưa bao gồm UPS và rack)

---

#### Phần mềm Stack cho Server

| Thành phần | Lựa chọn | Lý do |
| :--- | :--- | :--- |
| **OS** | Ubuntu 22.04 LTS Server | Ổn định, miễn phí, hỗ trợ dài hạn |
| **Web Server** | Nginx + PHP-FPM 8.2 | Hiệu năng cao cho 200 concurrent |
| **Database** | MySQL 8.0 (với InnoDB) | Tương thích hoàn toàn với eBMR |
| **Caching** | Redis 7.x | Session, Queue, BMS polling cache |
| **Queue Worker** | Laravel Queue + Supervisor | Xử lý tác vụ nền (notification, BMS) |
| **WebSocket** | Laravel Reverb hoặc Soketi | Real-time BMS data to clients |
| **Backup** | Percona XtraBackup (Hot backup) | Không cần dừng DB để backup |
| **Monitor** | Grafana + Prometheus | Giám sát hiệu năng Server |

---

## 2. Máy Tính Bảng (Industrial Tablet - Windows)

> **Khuyến nghị Hệ điều hành:** Bắt buộc sử dụng **Windows 11 Pro** thay vì Android để tương thích 100% với Web Serial API (dùng đọc cân điện tử qua cổng RS-232) và trình duyệt Chrome Desktop (tránh lỗi scale Viewport của thiết bị di động, đảm bảo giao diện hiển thị đầy đủ chiều ngang 1920px như thiết kế).

### Các Model được đề xuất (Dưới 35 triệu VND)

#### 🥇 Lựa chọn 1: Microsoft Surface Pro 9 (Bản Business/Commercial)
Đây là lựa chọn cân bằng hoàn hảo nhất giữa hiệu năng, màn hình, trải nghiệm bút viết và giá cả. Tuy không hầm hố như dòng "Rugged" (nồi đồng cối đá), nhưng khi kết hợp với một chiếc ốp lưng bảo vệ chuyên dụng thì nó đáp ứng rất tốt môi trường sản xuất GMP.

| Thông số | Chi tiết |
| :--- | :--- |
| OS | Windows 11 Pro |
| Màn hình | **13" PixelSense Flow (2880 x 1920)**, tỷ lệ 3:2 |
| CPU / RAM | Intel Core i5 (12th Gen) / 8GB hoặc 16GB RAM |
| Bút Stylus | Surface Slim Pen 2 (Viết/ký số như trên giấy) |
| Camera | 10MP (Auto-Focus quét QR/Barcode) |
| Bảo vệ (Bắt buộc) | Ốp UAG Metropolis hoặc Kensington BlackBelt (MIL-STD-810G) |
| **Giá ước tính** | **~30 - 32 triệu VNĐ/bộ (Máy + Ốp + Bút)** |

**Điểm mạnh:**
- Màn hình tỷ lệ 3:2 hiển thị được nhiều dòng nội dung bảng biểu hơn.
- Chrome Desktop mượt mà, không lỗi viewport CSS.
- Nhẹ nhàng, dễ cầm tay.

#### 🥈 Lựa chọn 2: HP Elite x2 G8
Dòng máy tính bảng thuần doanh nghiệp, thiết kế để dễ bảo trì sửa chữa.

| Thông số | Chi tiết |
| :--- | :--- |
| OS | Windows 11 Pro |
| Màn hình | 13" WUXGA+ (1920 x 1280), kính Gorilla Glass 5 |
| CPU / RAM | Intel Core i5 (11th Gen) / 8GB hoặc 16GB |
| Bút Stylus | HP Active Pen |
| Camera | 8MP Auto-Focus |
| Bảo vệ (Bắt buộc) | Ốp Targus Commercial Grade Case |
| **Giá ước tính** | **~32 - 35 triệu VNĐ/bộ** |

#### 🥉 Lựa chọn 3: Emdoor EM-I12U (Máy tính bảng Công nghiệp thực thụ)
Lựa chọn tối ưu nếu xưởng khắc nghiệt, bụi nhiều, yêu cầu lau hóa chất và thay pin liên tục.

| Thông số | Chi tiết |
| :--- | :--- |
| OS | Windows 11 Pro |
| Màn hình | 12.2" (1920 x 1200) IPS, độ sáng 650 nits |
| CPU / RAM | Intel Core i5 (12th Gen) / 16GB RAM |
| Tính năng đặc biệt | Máy quét mã vạch Honeywell/Zebra 2D tích hợp bằng phần cứng |
| Độ bền | Chuẩn IP65, MIL-STD-810H (Không cần ốp ngoài) |
| Pin | **Hot-swappable (Thay pin nóng không tắt máy)** |
| **Giá ước tính** | **~28 - 33 triệu VNĐ/chiếc** |

---

## 3. Tổng Chi Phí Ước Tính (200 máy tính bảng)

| Hạng mục | Đơn giá | Số lượng | Thành tiền |
| :--- | :--- | :--- | :--- |
| Server (On-Premise) + License | ~200 triệu | 1 | **~200 triệu VND** |
| UPS 10kVA | ~35 triệu | 1 | **~35 triệu VND** |
| Switch + WiFi 6 AP (công nghiệp)| ~5 triệu/AP | 20 AP | **~100 triệu VND** |
| Tablet (VD: Surface Pro 9 + Ốp) | ~32 triệu | 200 | **~6,400 triệu VND** |
| Trạm sạc đa cổng (Charging Hub) | ~20 triệu/trạm 20 slot | 10 | **~200 triệu VND** |
| **Tổng chi phí phần cứng** | | | **~6,935 triệu VND (~6.9 tỷ VND)** |

> **Lưu ý:**
> Mức chi phí trên chỉ tính toán phần cứng, chưa bao gồm các khoản phí khảo sát lắp đặt cáp mạng xưởng, phí phần mềm, thuế phí, và phí bảo trì hàng năm. Nên dự trù ngân sách ~7.5 - 8 tỷ VND.

---

## 4. Các Yêu Cầu Về Network Nội Bộ (LAN)

- **Access Point (AP):** Yêu cầu triển khai Wi-Fi 6 (802.11ax) chuyên dụng, đảm bảo băng thông không bị nghẽn khi 200 thiết bị gọi API về Server đồng thời.
- **Tốc độ:** Tối thiểu 20 Mbps cho mỗi client (khoảng 4Gbps thông lượng toàn xưởng).
- **Roaming:** Cấu hình Fast Roaming (802.11r) để kết nối không bị ngắt quãng khi nhân viên cầm máy tính bảng di chuyển giữa các phòng sản xuất.
