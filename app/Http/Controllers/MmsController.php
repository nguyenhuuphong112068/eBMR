<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class MmsController extends Controller
{
    public function getStockByBarcode($barcode)
    {
        try {
            $data = DB::connection('mms')->table('Stock')
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
                    'GRN.ARNO as ARNO',
                    'Stock.ACT_QTY as Qty',
                    'Stock.Sample_Type',
                    'Stock.SampleBy',
                    'Stock.sample_On',
                    'Stock.COA_No',
                    'Stock.COA_Date',
                    'Stock.Moiture',
                    'Stock.Density',
                    'Stock.Content',
                    'Stock.KLTB'
                )
                ->where('Stock.Barcode_No', $barcode)
                ->first();

            if ($data) {
                // Formatting dates and fields
                if (isset($data->sample_On)) {
                    $data->sample_On = date('d/m/Y', strtotime($data->sample_On));
                }
                if (isset($data->COA_Date)) {
                    $data->COA_Date = date('d/m/Y', strtotime($data->COA_Date));
                }
                if (isset($data->Expiry_Date)) {
                    $data->Expiry_Date = date('d/m/Y', strtotime($data->Expiry_Date));
                }
                if (isset($data->Retest_Date)) {
                    $data->Retest_Date = date('d/m/Y', strtotime($data->Retest_Date));
                }
                // Mocking Mfg Name as "Á Châu" or similar from Supplier if not available or via MFG table.
                // Assuming MFG is Supplier or can be fetched from GRN.MfgID
                $mfgId = DB::connection('mms')->table('GRN')->where('GRNNO', $data->GRN_No)->value('MfgID');
                if ($mfgId) {
                    $mfgName = DB::connection('mms')->table('mstSUP')->where('SupID', $mfgId)->value('SupNM');
                    $data->Mfg_Name = $mfgName ?: $data->Supplier_Name;
                } else {
                    $data->Mfg_Name = $data->Supplier_Name;
                }

                // IntBatchNo from GRNLOCATION
                $intBatch = DB::connection('mms')->table('GRNLOCATION')
                    ->where('GRNNO', $data->GRN_No)
                    ->whereNotNull('IntBatchNo')
                    ->where('IntBatchNo', '<>', '')
                    ->value('IntBatchNo');
                $data->IntBatchNo = $intBatch ?: '';
                
                // MFG Date
                $mfgDate = DB::connection('mms')->table('GRN')->where('GRNNO', $data->GRN_No)->value('Mfgdate');
                $data->MFG_Date = $mfgDate ? date('d/m/Y', strtotime($mfgDate)) : '';

                return response()->json([
                    'success' => true,
                    'data' => $data
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy thông tin cho Barcode này trong MMS.'
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi kết nối hoặc truy vấn MMS: ' . $e->getMessage()
            ], 500);
        }
    }
}
