---
name: mms-database-schema
description: Cách thức JOIN các bảng cơ sở dữ liệu MMS (Stock, GRN, Materials, Supplier...) để lấy thông tin nguyên liệu từ Barcode.
---

# MMS Database Schema & Joins

## Mục đích
Skill này hướng dẫn cách truy xuất các thông tin nguyên liệu (Material Name, Material Code, Supplier, v.v.) từ cơ sở dữ liệu `MMS` bằng mã Barcode. 

> **QUY TẮC TUYỆT ĐỐI**: Đối với các DB ngoài (như MMS), chỉ được thực hiện lệnh `SELECT`. **TUYỆT ĐỐI KHÔNG** thực hiện `INSERT`, `UPDATE`, `DELETE`, `CREATE`, `DROP` hoặc bất kỳ câu lệnh nào làm thay đổi dữ liệu/cấu trúc của cơ sở dữ liệu ngoài.

## Logic JOIN dữ liệu từ bảng Stock

Khi người dùng cung cấp một mã Barcode (ví dụ: `86781-0001` trên tem nhãn), dữ liệu có thể được truy xuất theo thứ tự sau:

1. **Bảng `Stock`**: Tìm kiếm theo `Barcode_No`.
   - Bảng `Stock` chứa các thông tin cơ bản về trạng thái lô nguyên liệu: `ACT_QTY`, `QTY` (Số lượng), `LOT` (Số lô), `warehouse_id` (Vị trí), `expiry_date` (Hạn dùng), `Retest_date`, `QC_status` (Trạng thái QC), `SampleID`, `SampleBy`, v.v.
   - Trường liên kết tới phiếu nhập (GRN): `grndetails_id`.
   - *Lưu ý: `grndetails_id` lưu trữ giá trị GRN No (VD: `0764/036P2/170326`).*

2. **Bảng `GRN`**: Liên kết từ `Stock.grndetails_id` = `GRN.GRNNO`
   - Bảng `GRN` chứa thông tin về phiếu nhập kho.
   - Các trường quan trọng để liên kết tiếp: `MatID` (Mã nguyên liệu), `SupID` (Mã nhà cung cấp), `MfgID` (Mã nhà sản xuất).
   - Các thông tin khác: `Mfgdate` (Ngày sản xuất), `Mfgbatchno` (Lô sản xuất), `COA_No`.

3. **Bảng `mstmaterial`**: Liên kết từ `GRN.MatID` = `mstmaterial.MatID`
   - Bảng này chứa tên nguyên liệu và chi tiết danh mục.
   - Trường quan trọng: `MatNM` (Tên hàng).

4. **Bảng `mstSUP`**: Liên kết từ `GRN.SupID` = `mstSUP.SupID`
   - Bảng chứa thông tin nhà cung cấp.
   - Trường quan trọng: `SupNM` (Tên nhà cung cấp).

## Ví dụ Query (Laravel / Tinker)

```php
$barcode = '86781-0001';

$data = DB::connection('mms')
    ->table('Stock')
    ->leftJoin('GRN', 'Stock.grndetails_id', '=', 'GRN.GRNNO')
    ->leftJoin('mstmaterial', 'GRN.MatID', '=', 'mstmaterial.MatID')
    ->leftJoin('mstSUP', 'GRN.SupID', '=', 'mstSUP.SupID')
    ->select(
        'Stock.grndetails_id as GRN_No',
        'Stock.Barcode_No',
        'Stock.LOT',
        'mstmaterial.MatID as Material_Code',
        'mstmaterial.MatNM as Material_Name',
        'Stock.expiry_date as Expiry_Date',
        'Stock.Retest_date as Retest_Date',
        'mstSUP.SupNM as Supplier_Name',
        'GRN.Mfgbatchno as MFG_Batch',
        'Stock.ACT_QTY as Qty',
        'Stock.Sample_Type',
        'Stock.SampleBy',
        'Stock.sample_On',
        'Stock.COA_No',
        'Stock.COA_Date'
    )
    ->where('Stock.Barcode_No', $barcode)
    ->first();
```
