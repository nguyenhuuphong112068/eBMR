<?php

namespace App\Http\Controllers\Pages\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IntermediateCategoryController extends Controller
{

        public function index()
        {

                $productNames = DB::table('product_name')->where('active', true)->orderBy('name', 'asc')->get();
                $dosages = DB::table('dosage')->where('active', true)->get();
                $units = DB::table('unit')->where('active', true)->get();

                $datas = DB::table('intermediate_category')->select('intermediate_category.*', 'dosage.name as dosage_name', 'product_name.name as product_name')
                        ->leftJoin('product_name', 'intermediate_category.product_name_id', 'product_name.id')
                        ->leftJoin('dosage', 'intermediate_category.dosage_id', 'dosage.id')
                        //->where('intermediate_category.deparment_code', session('user')['production_code'] ?? 'PXV1')
                        ->when(
                                !user_has_permission(session('user')['userId'], 'view_Hypothesis_category', 'boolean'),
                                function ($q) {
                                        return $q->where('intermediate_category.IsHypothesis', 0);
                                }
                        )
                        ->where('cancel', 0)
                        ->orderBy('intermediate_category.IsHypothesis', 'desc')
                        ->orderBy('product_name.name', 'asc')->get();

                session()->put(['title' => 'DANH MỤC BÁN THÀNH PHẨM']);

                return view('pages.category.intermediate.list', [
                        'datas' => $datas,
                        'productNames' => $productNames,
                        'dosages' => $dosages,
                        'units' => $units,
                ]);
        }

        public function store(Request $request)
        {
                //dd ($request->all());


                $validator = Validator::make($request->all(), [
                        'intermediate_code' => 'required|unique:intermediate_category,intermediate_code',
                        'product_name_id' => 'required',
                        'batch_size' => 'required',
                        'batch_qty' => 'required',
                        'unit_batch_qty' => 'required',
                ], [
                        'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm.',
                        'intermediate_code.unique' => 'Mã bán thành phẩm đã tồn tại.',
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'batch_size.required' => 'Vui lòng nhập cỡ lô',
                        'batch_qty.required' => 'Vui lòng nhập cỡ lô',
                        'unit_batch_qty.required' => 'Vui lòng chọn đơn vị '
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                $categoryId = DB::table('intermediate_category')->insertGetId([
                        'intermediate_code' => $request->intermediate_code,
                        'product_name_id' => $request->product_name_id,
                        'dosage_id' => $request->dosage_id,
                        'classification' => $request->classification,
                        'batch_size' => $request->batch_size,
                        'unit_batch_size' => $request->unit_batch_size,
                        'batch_qty' => $request->batch_qty,
                        'unit_batch_qty' => $request->unit_batch_qty,
                        
                        'weight_1' => $request->input('weight_1') === "on" ? 1 : 0,
                        'weight_2' => $request->input('weight_2') === "on" ? 1 : 0,
                        'prepering' => $request->input('prepering') === "on" ? 1 : 0,
                        'blending' => $request->input('blending') === "on" ? 1 : 0,
                        'forming' => $request->input('forming') === "on" ? 1 : 0,
                        'coating' => $request->input('coating') === "on" ? 1 : 0,
                        
                        'quarantine_total' => $request->input('quarantine_total') ?? 0,
                        'quarantine_weight' => $request->input('quarantine_weight') ?? 0,
                        'quarantine_preparing' => $request->input('quarantine_preparing') ?? 0,
                        'quarantine_blending' => $request->input('quarantine_blending') ?? 0,
                        'quarantine_forming' => $request->input('quarantine_forming') ?? 0,
                        'quarantine_coating' => $request->input('quarantine_coating') ?? 0,
                        'quarantine_time_unit' => $request->input('quarantine_time_unit') === "on" ? 1 : 0,

                        'IsHypothesis' => $request->is_Hypothesis ?? 0,
                        'deparment_code' => $request->deparment_code ?? (session('user')['production_code'] ?? 'PXV1'),
                        'prepared_by' => session('user')['fullName'] ?? 'System',
                        'created_at' => now(),
                ]);

                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function update(Request $request)
        {

                $validator = Validator::make($request->all(), [
                        //'intermediate_code' => 'required|unique:intermediate_category,intermediate_code',
                        'product_name_id' => 'required',
                        'batch_size' => 'required',
                        'batch_qty' => 'required',
                        'unit_batch_qty' => 'required',
                ], [
                        //'intermediate_code.required' => 'Vui lòng nhập mã bán thành phẩm.',
                        'intermediate_code.unique' => 'Mã bán thành phẩm đã tồn tại.',
                        'product_name_id.required' => 'Vui lòng chọn tên sản phẩm',
                        'batch_size.required' => 'Vui lòng nhập cỡ lô',
                        'batch_qty.required' => 'Vui lòng nhập cỡ lô',
                        'unit_batch_qty.required' => 'Vui lòng chọn đơn vị '
                ]);

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }

                DB::table('intermediate_category')->where('id', $request->id)->update([

                        'intermediate_code' => $request->intermediate_code,
                        'product_name_id' => $request->product_name_id,
                        'dosage_id' => $request->dosage_id,
                        'classification' => $request->classification,
                        'batch_size' => $request->batch_size,
                        'unit_batch_size' => $request->unit_batch_size,
                        'batch_qty' => $request->batch_qty,
                        'unit_batch_qty' => $request->unit_batch_qty,

                        'weight_1' => $request->input('weight_1') === "on" ? 1 : 0,
                        'weight_2' => $request->input('weight_2') === "on" ? 1 : 0,
                        'prepering' => $request->input('prepering') === "on" ? 1 : 0,
                        'blending' => $request->input('blending') === "on" ? 1 : 0,
                        'forming' => $request->input('forming') === "on" ? 1 : 0,
                        'coating' => $request->input('coating') === "on" ? 1 : 0,
                        
                        'quarantine_total' => $request->input('quarantine_total') ?? 0,
                        'quarantine_weight' => $request->input('quarantine_weight') ?? 0,
                        'quarantine_preparing' => $request->input('quarantine_preparing') ?? 0,
                        'quarantine_blending' => $request->input('quarantine_blending') ?? 0,
                        'quarantine_forming' => $request->input('quarantine_forming') ?? 0,
                        'quarantine_coating' => $request->input('quarantine_coating') ?? 0,
                        'quarantine_time_unit' => $request->input('quarantine_time_unit') === "on" ? 1 : 0,

                        'deparment_code' => $request->deparment_code ?? (session('user')['production_code'] ?? 'PXV1'),
                        'prepared_by' => session('user')['fullName'] ?? 'System',
                        'updated_at' => now(),
                ]);



                return redirect()->back()->with('success', 'Đã cập nhật thành công!');
        }

        public function deActive(Request $request)
        {

                if ($request->IsHypothesis == 1) {
                        DB::table('intermediate_category')->where('id', $request->id)->update([
                                'cancel' => 1,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);
                } else {
                        DB::table('intermediate_category')->where('id', $request->id)->update([
                                'Active' => !$request->active,
                                'prepared_by' => session('user')['fullName'],
                                'updated_at' => now(),
                        ]);
                }


                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }

        public function recipe(Request $request)
        {

                if ($request->IsHypothesis == 1) {
                        if (Schema::hasTable('bom_item')) {
                                $datas = DB::table('bom_item')
                                        ->select([
                                                'code as MatID',
                                                'name as MaterialName',
                                                'qty as MatQty',
                                                'uom',
                                                'Revno'
                                        ])
                                        ->where('active', 1)
                                        ->where('product_caterogy_id', $request->product_caterogy_id)
                                        ->get();
                        } else {
                                $datas = collect([]);
                        }
                } else {
                        try {
                                $datas = DB::connection('mms')
                                        ->table('yfBOM_BOMItemHP')
                                        ->where('PrdID', $request->intermediate_code)
                                        ->where('Revno', function ($q) use ($request) {
                                                $q->selectRaw('MAX(Revno)')
                                                        ->from('yfBOM_BOMItemHP')
                                                        ->where('PrdID', $request->intermediate_code);
                                        })
                                        ->distinct()
                                        ->orderBy('PrdStage')
                                        ->orderBy('MatID')
                                        ->get();
                        } catch (\Exception $e) {
                                Log::error('ERP mms connection failed: ' . $e->getMessage());
                                $datas = collect([]);
                        }
                }

                return response()->json($datas);
        }

        public function formulas(Request $request)
        {
                $formulas = DB::table('formula_preparation')
                        ->where('intermediate_category_id', $request->id)
                        ->orderBy('id')
                        ->get();

                foreach ($formulas as $formula) {
                        $formula->materials = DB::table('formula_materials')
                                ->where('preparation_formula_id', $formula->id)
                                ->orderBy('id')
                                ->get();
                                
                        $formula->sub_amounts = DB::table('formula_ingredient_amount')
                                ->where('preparation_formula_id', $formula->id)
                                ->orderBy('id')
                                ->get();
                }

                return response()->json($formulas);
        }
}
