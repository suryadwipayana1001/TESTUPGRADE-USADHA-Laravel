<?php

namespace App\Http\Controllers\Admin;

use App\Account;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Ledger;
use App\NetworkFee;
use App\Order;
use App\Package;
use App\Product;
use App\Traits\TraitModel;
use Berkayk\OneSignal\OneSignalClient;
use Gate;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Requests\MassDestroyOrderRequest;

class MaterialController extends Controller
{
    use TraitModel;
    private $onesignal_client;

    public function __construct()
    {
        $this->onesignal_client = new OneSignalClient(env('ONESIGNAL_APP_ID_MEMBER'), env('ONESIGNAL_REST_API_KEY_MEMBER'), '');
    }

    public function smsApi(Request $request)
    {
        date_default_timezone_set("Asia/Singapore");
        $date = date("Y-m-d H:i:s");
        $number = "+62" . ltrim($request->number, '0');
        $message = '#plg OTP : ' . $request->otp;
        $md5_str = "1f4a449a85" . $date . $number . $message;
        $md5 = md5($md5_str);
        $data = array(
            'outbox' => '',
            'date' => $date,
            'number' => $number,
            'message' => $message,
            'md5' => $md5,
        );

        $url = 'https://tab-jdol.com/gs-gateway-sms-v3/api.php';

        //open connection
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        //execute post
        $result = curl_exec($ch);

        //close connection
        curl_close($ch);
    }

    public function test(Request $request)
    {
        abort_if(Gate::denies('order_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //$data_row = $this->downref_list($request->input('ref_id'),$request->input('deep'));
        $dwn_arr = array();
        // $data_row = $this->ref1_omzet($request->input('ref_id'), 10);
        // $data_row = $this->group_omzet($request->input('ref_id'), 5);        
        // $data_row = $this->pairing(1819, 1050);        
        // return $this->downref_omzet_view($request->input('ref_id'), $request->input('activation_at'), 1);
        //$data_row = $this->recursive_test(5);
        // $data_row = $this->ref_up_list($request->input('ref_id'), $dwn_arr);
        return $this->pairing(1820, 1050);
        // return $data_row;
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('order_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        //$from = !empty($request->from) ? $request->from : date('Y-m-01'); 
        $from = !empty($request->from) ? $request->from : '';
        $to = !empty($request->to) ? $request->to :date('Y-m-d'); 
        $query = Order::with('products')
        ->with('customers')
        ->with('accounts')
        ->where('type','production_request')
        ->FilterCustomer()
        // ->FilterRangeDate(null, $from, $to)
        ->whereBetween('register', [$from, $to])
        ->orderBy("register", "desc")
        ->get();
        // return $query;
        // dd($request->all());
        if ($request->ajax()) {

            $query = Order::with('products')
                ->with('customers')
                ->with('accounts')
                ->where('type','buy')
                ->FilterCustomer()
                // ->FilterRangeDate(null, $from, $to)
                ->whereBetween('register', [$from, $to])
                ->orderBy("register", "desc");

            $table = Datatables::of($query);

            $table->addColumn('placeholder', '&nbsp;');
            $table->addColumn('actions', '&nbsp;');

            $table->editColumn('actions', function ($row) {
                // $viewGate = 'order_show';
                $editGate = 'order_edit';
                $deleteGate = 'order_delete';
                $crudRoutePart = 'material';

                return view('partials.datatablesPermintaan', compact(
                    // 'viewGate',
                    'editGate',
                    'deleteGate',
                    'crudRoutePart',
                    'row'
                ));
            });

            $table->editColumn('register', function ($row) {
                return $row->register ? $row->register : "";
            });

            $table->editColumn('code', function ($row) {
                return $row->code ? $row->code : "";
            });

            $table->editColumn('name', function ($row) {
                if (isset($row->customers->code)) {
                    return $row->customers->code ? $row->customers->code . " - " . $row->customers->name : "";
                } else {
                    return '';
                }
            });

            $table->editColumn('memo', function ($row) {
                return $row->memo ? $row->memo : "";
            });

            $table->editColumn('status', function ($row) {
                return $row->status ? $row->status : "";
            });

            $table->editColumn('status_delivery', function ($row) {
                return $row->status_delivery ? $row->status_delivery : "";
            });

            $table->editColumn('amount', function ($row) {
                return $row->total ? number_format($row->total, 2) : "";
            });

            $table->editColumn('accpay', function ($row) {
                if (isset($row->accounts->code)) {
                    return $row->accounts->name ? $row->accounts->name : "";
                } else {
                    return '';
                }
            });

            $table->editColumn('product', function ($row) {
                $product_list = '<ul>';
                foreach ($row->products as $key => $item) {
                    $product_list .= '<li>' . $item->name . " (" . $item->pivot->quantity . " x " . number_format($item->price, 2) . ")" . '</li>';
                }
                $product_list .= '</ul>';
                return $product_list;
            });

            $table->rawColumns(['actions', 'placeholder', 'product']);

            $table->addIndexColumn();
            return $table->make(true);
        }
        //def view
        $orders = Order::with('products')
            ->with('customers')
            ->with('accounts')
            ->where('type','buy');

        $customers = Customer::select('*')
            ->where(function ($query) {
                $query->where('type', 'member')
                    ->orWhere('type', 'agent')
                    ->orWhere('def', '1');
            })
            ->get();

        //return $orders;

        return view('admin.material.index', compact('orders', 'customers'));
    }

    public function create()
    {
        abort_if(Gate::denies('order_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $products = Product::all();
        $customers = Customer::select('*')
            ->where('def', '=', '0')
            ->where('type', '=', 'agent')
            ->get();
        $accounts = Account::select('*')
            ->where('accounts_group_id', 1)
            ->get();

        $last_code = $this->ord_get_last_code();
        $code = acc_code_generate($last_code, 8, 3);

        return view('admin.material.create', compact('products', 'customers', 'accounts', 'code'));
    }

    public function store(StoreOrderRequest $request)
    {
        //get total
        $total = 0;
        $cogs_total = 0;
        $products = $request->input('products', []);
        $quantities = $request->input('quantities', []);
        $prices = $request->input('prices', []);
        $cogs = $request->input('cogs', []);
        for ($product = 0; $product < count($products); $product++) {
            $total += $quantities[$product] * $prices[$product];
            $cogs_total += $quantities[$product] * $cogs[$product];
        }

        /* proceed ledger */
        $data = ['register' => $request->input('register'), 'title' => 'Transaksi Penjualan Barang', 'memo' => 'Transaksi Penjualan Barang'];
        $ledger = Ledger::create($data);
        $ledger_id = $ledger->id;
        //set ledger entry arr
        $acc_inv_stock = '20';
        $acc_sale = '44';
        $acc_exp_cogs = '45';
        $total_pay = $total;
        $accounts = array($acc_inv_stock, $acc_exp_cogs, $acc_sale);
        $amounts = array($cogs_total, $cogs_total, $total);
        $types = array('C', 'D', 'C');
        //if agent get cashback
        $customer_row = Customer::select('*')
            ->Where('id', '=', $request->input('customers_id'))
            ->get();
        if ($customer_row[0]->type == 'agent') {
            //get cashback 01
            $acc_disc = 68;
            $acc_res_cashback = 70;
            $networkfee_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA01')
                ->get();
            $networkfee2_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA02')
                ->get();
            //LEV 1
            $lev_fee_row = NetworkFee::select('*')
                ->Where('code', '=', 'LEV')
                ->get();
            //set ref fee
            $ref_fee_row = NetworkFee::select('*')
                ->Where('code', '=', 'REF')
                ->get();
            //set cashback member 1
            $cashback_mbr_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBM01')
                ->get();
            //set cashback member 2
            $cashback_mbr2_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBM02')
                ->get();
            $cba1 = (($networkfee_row[0]->amount) / 100) * $total;
            $cba2 = (($networkfee2_row[0]->amount) / 100) * $total;
            $total_disc = $networkfee_row[0]->amount + $networkfee2_row[0]->amount + $lev_fee_row[0]->amount + $ref_fee_row[0]->amount + $cashback_mbr_row[0]->amount + $cashback_mbr2_row[0]->amount;
            $amount_disc = (($total_disc) / 100) * $total;
            $amount_res_cashback = $amount_disc - $cba1;
            $total_pay = $total - $cba1;
            //$acc_points = '67';
            //push array jurnal
            array_push($accounts, $acc_disc, $acc_res_cashback);
            array_push($amounts, $amount_disc, $amount_res_cashback);
            array_push($types, "D", "C");
        }
        //push array jurnal
        array_push($accounts, $request->input('accounts_id'));
        array_push($amounts, $total_pay);
        array_push($types, "D");
        //ledger entries
        for ($account = 0; $account < count($accounts); $account++) {
            if ($accounts[$account] != '') {
                $ledger->accounts()->attach($accounts[$account], ['entry_type' => $types[$account], 'amount' => $amounts[$account]]);
            }
        }

        /* proceed order, order products, order details (inventory stock) */
        $ref_def_id = Customer::select('id')
            ->Where('def', '=', '1')
            ->get();
        $owner_def = $ref_def_id[0]->id;
        $customers_id = $request->input('customers_id');
        $warehouses_id = 1;
        //set order
        $data = array_merge($request->all(), ['total' => $total, 'type' => 'buy', 'status' => 'approved', 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'payment_type' => 'cash']);
        $order = Order::create($data);
        //set order products
        for ($product = 0; $product < count($products); $product++) {
            if ($products[$product] != '') {
                $order->products()->attach($products[$product], ['quantity' => $quantities[$product], 'price' => $prices[$product], 'cogs' => $cogs[$product]]);
            }
        }
        //set order order details (inventory stock)
        for ($product = 0; $product < count($products); $product++) {
            if ($products[$product] != '') {
                //check if package
                $products_type = Product::select('type')
                    ->where('id', $products[$product])
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package') {
                    $package_items = Package::with('products')
                        ->where('id', $products[$product])
                        ->get();
                    $package_items = json_decode($package_items, false);
                    $package_items = $package_items[0]->products;
                    //loop items
                    foreach ($package_items as $key => $value) {
                        $order->productdetails()->attach($value->id, ['quantity' => $quantities[$product] * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                        $order->productdetails()->attach($value->id, ['quantity' => $quantities[$product] * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    }
                } else {
                    $order->productdetails()->attach($products[$product], ['quantity' => $quantities[$product], 'type' => 'C', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                    $order->productdetails()->attach($products[$product], ['quantity' => $quantities[$product], 'type' => 'D', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                }
            }
        }
        return redirect()->route('admin.material.index');
    }

    public function edit(Order $order,$id)
    {
        $order = Order::find($id);
        abort_if(Gate::denies('order_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $products = Product::all();
        $customers = Customer::select('*')
        ->where('def', '=', '0')
        ->where('type', '=', 'agent')
        ->get();
         $accounts = Account::select('*')
        ->where('accounts_group_id', 1)
        ->get();
        $order->load('products');

        // return $order;
        return view('admin.material.edit', compact('products', 'order','customers','accounts'));
        // return redirect()->route('admin.permintaan-produksi.index');
    }

    public function update(UpdateOrderRequest $request, Order $order,$id )
    {
        // return $request;
        $order = Order::find($id);
        $total = 0;
        $cogs_total = 0;
        $products = $request->input('products', []);
        $quantities = $request->input('quantities', []);
        $prices = $request->input('prices', []);
        $cogs = $request->input('cogs', []);
        $memo = $request->input('memo');
        $code = $request->input('code');
        $register = $request->input('register');
        for ($product = 0; $product < count($products); $product++) {
            $total += $quantities[$product] * $prices[$product];
            $cogs_total += $quantities[$product] * $cogs[$product];
        }

        /* proceed ledger */
        // $data = ['register' => $request->input('register'), 'title' => 'Transaksi Penjualan Barang', 'memo' => 'Transaksi Penjualan Barang'];
        // $ledger = Ledger::update($data);
        
        $ledger = Ledger::find($order->ledgers_id);
        $ledger->register = $request->input('register');
        $ledger->title='Transaksi Penjualan Barang';
        $ledger->memo='Transaksi Penjualan Barang';
        $ledger->save();

        $ledger_id = $ledger->id;
        //set ledger entry arr
        $acc_inv_stock = '20';
        $acc_sale = '44';
        $acc_exp_cogs = '45';
        $total_pay = $total;
        $accounts = array($acc_inv_stock, $acc_exp_cogs, $acc_sale);
        $amounts = array($cogs_total, $cogs_total, $total);
        $types = array('C', 'D', 'C');
        //if agent get cashback
        $customer_row = Customer::select('*')
            ->Where('id', '=', $request->input('customers_id'))
            ->get();
        if ($customer_row[0]->type == 'agent') {
            //get cashback 01
            $acc_disc = 68;
            $acc_res_cashback = 70;
            $networkfee_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA01')
                ->get();
            $networkfee2_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBA02')
                ->get();
            //LEV 1
            $lev_fee_row = NetworkFee::select('*')
                ->Where('code', '=', 'LEV')
                ->get();
            //set ref fee
            $ref_fee_row = NetworkFee::select('*')
                ->Where('code', '=', 'REF')
                ->get();
            //set cashback member 1
            $cashback_mbr_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBM01')
                ->get();
            //set cashback member 2
            $cashback_mbr2_row = NetworkFee::select('*')
                ->Where('code', '=', 'CBM02')
                ->get();
            $cba1 = (($networkfee_row[0]->amount) / 100) * $total;
            $cba2 = (($networkfee2_row[0]->amount) / 100) * $total;
            $total_disc = $networkfee_row[0]->amount + $networkfee2_row[0]->amount + $lev_fee_row[0]->amount + $ref_fee_row[0]->amount + $cashback_mbr_row[0]->amount + $cashback_mbr2_row[0]->amount;
            $amount_disc = (($total_disc) / 100) * $total;
            $amount_res_cashback = $amount_disc - $cba1;
            $total_pay = $total - $cba1;
            //$acc_points = '67';
            //push array jurnal
            array_push($accounts, $acc_disc, $acc_res_cashback);
            array_push($amounts, $amount_disc, $amount_res_cashback);
            array_push($types, "D", "C");
        }
        //push array jurnal
        array_push($accounts, $request->input('accounts_id'));
        array_push($amounts, $total_pay);
        array_push($types, "D");
        //ledger entries
        for ($account = 0; $account < count($accounts); $account++) {
            if ($accounts[$account] != '') {
                $ledger->accounts()->attach($accounts[$account], ['entry_type' => $types[$account], 'amount' => $amounts[$account]]);
            }
        }

        /* proceed order, order products, order details (inventory stock) */
        $ref_def_id = Customer::select('id')
            ->Where('def', '=', '1')
            ->get();
        $owner_def = $ref_def_id[0]->id;
        $customers_id = $request->input('customers_id');
        $warehouses_id = 1;
        //set order
        // $data = array_merge($request->all(), ['total' => $total, 'type' => 'production_request', 'status' => 'approved', 'ledgers_id' => $ledger_id, 'customers_id' => $customers_id, 'payment_type' => 'cash']);
        
        $order->total = $total;
        $order->type='buy';
        $order->status='approved';
        $order->memo=$memo;
        $order->code=$code;
        $order->register=$register;
        $order->ledgers_id=$ledger_id;
        $order->customers_id=$customers_id;
        $order->payment_type='cash';
       
        $order->save();
        // $order = Order::update($data);
        //set order products
        $order->products()->detach();
        for ($product = 0; $product < count($products); $product++) {
            if ($products[$product] != '') {
                $order->products()->attach($products[$product], ['quantity' => $quantities[$product], 'price' => $prices[$product], 'cogs' => $cogs[$product]]);
            }
        }
        //set order order details (inventory stock)
        $order->productdetails()->detach();
        for ($product = 0; $product < count($products); $product++) {
            if ($products[$product] != '') {
                //check if package
                $products_type = Product::select('type')
                    ->where('id', $products[$product])
                    ->get();
                $products_type = json_decode($products_type, false);
                if ($products_type[0]->type == 'package') {
                    $package_items = Package::with('products')
                        ->where('id', $products[$product])
                        ->get();
                    $package_items = json_decode($package_items, false);
                    $package_items = $package_items[0]->products;
                    //loop items
                    foreach ($package_items as $key => $value) {
                        $order->productdetails()->attach($value->id, ['quantity' => $quantities[$product] * $value->pivot->quantity, 'type' => 'C', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                        $order->productdetails()->attach($value->id, ['quantity' => $quantities[$product] * $value->pivot->quantity, 'type' => 'D', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                    }
                } else {
                    $order->productdetails()->attach($products[$product], ['quantity' => $quantities[$product], 'type' => 'C', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $owner_def]);
                    $order->productdetails()->attach($products[$product], ['quantity' => $quantities[$product], 'type' => 'D', 'status' => 'onhand', 'warehouses_id' => $warehouses_id, 'owner' => $customers_id]);
                }
            }
        }
        return redirect()->route('admin.material.index');
    }

    public function show(Order $order)
    {
        abort_if(Gate::denies('order_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $order->load('products');

        //return view('admin.orders.show', compact('order'));
        return Redirect()->Route('admin.orders.index');
    }

    public function destroy(Order $order, $id)
    {

        abort_if(Gate::denies('order_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $order = Order::find($id);
        if ($order->ledgers_id > 0) {
            $ledger = Ledger::find($order->ledgers_id);
            $ledger->accounts()->detach();
            $ledger->delete();
        }

        $order->products()->detach();
        $order->productdetails()->detach();
        $order->points()->detach();
        $order->delete();
        return back();
        //return Redirect()->Route('admin.orders.index');
    }
  

    public function massDestroy(MassDestroyOrderRequest $request)
    {

          $test = Order::whereIn('id', request('ids'))->delete();
        return $test;
        // Order::whereIn('id', request('ids'))->delete();
        
        // return response(null, 204);
    }
}
