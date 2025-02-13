@extends('layouts.admin')
@section('content')
@can('order_create')
    <div style="margin-bottom: 10px;" class="row">
        <div class="col-lg-12">
            <a class="btn btn-success" href="{{ route("admin.material.create") }}">
                {{ trans('global.add') }} Bahan Baku
            </a>
        </div>
    </div>
@endcan
<div class="card">
    <div class="card-header">
        Bahan Baku {{ trans('global.list') }}
    </div>

    <div class="card-body">
        <div class="form-group">
            <form action="" id="filtersForm"> 
                <div class="col-md-12">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group date">
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-th"></span>
                                </div>
                                <input id="from" placeholder="masukkan tanggal Awal" type="date" class="form-control datepicker" name="from" value = "">
                            </div>
                            <div class="row">
                            &nbsp;
                            </div> 
                                <div class="input-group">
                                    <select id="customer" name="customer" class="form-control">
                                    <option value="">== Semua User ==</option>
                                    @foreach($customers as $customer)
                                    <option value="{{$customer->id}}">{{ $customer->code}} - {{ $customer->name}}</option>
                                    @endforeach
                                    </select>
                                </div> 
                            {{-- <div class="input-group">
                                <select id="type" name="type" class="form-control">
                                <option value="">== Semua Type ==</option>
                                <option value="activation_member">Aktivasi Member</option>
                                <option value="activation_agent">Aktivasi Agent</option>
                                <option value="sale">Penjualan</option>
                                <option value="sale_retur">Retur Penjualan</option>
                                <option value="topup">Topup</option>
                                <option value="transfer">Transfer</option>
                                <option value="withdraw">Withdraw</option>
                                <option value="agent_sale">Penjualan Agen</option>
                                <option value="production">Produksi</option>                    
                                <option value="buy">Pembelian</option>                    
                                <option value="buy_retur">Retur Pembelian</option>
                                <option value="stock_trsf">Transfer Stok</option>
                                <option value="point_conversion">Konversi Poin</option>                    
                                </select>
                            </div>                 --}}
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {{-- <label>Dari Tanggal</label> --}}
                                <div class="input-group date">
                                    <div class="input-group-addon">
                                        <span class="glyphicon glyphicon-th"></span>
                                    </div>
                                    <input id="to" placeholder="masukkan tanggal Akhir" type="date" class="form-control datepicker" name="to" value = "{{date('Y-m-d')}}">
                                </div>
                            </div>
                            <div class="form-group">
                                {{-- <label>Sampai Tanggal</label> --}}
                                
                            </div>
                        </div>
                    </div>
                    <span class="input-group-btn">
                        <input type="submit" class="btn btn-primary" value="Filter">
                    </span>
                </div>
            </form>
            <div class="table-responsive">
                <table class=" table table-bordered table-striped table-hover datatable datatable-Material ajaxTable datatable-materials"">
                    <thead>
                        <tr>
                            <th width="10">

                            </th>
                            <th>
                                {{ trans('global.order.fields.id') }}
                            </th>
                            <th>
                                {{ trans('global.order.fields.code') }}
                            </th>
                            <th>
                                {{ trans('global.order.fields.register') }}
                            </th>
                            <th>
                                {{ trans('global.order.fields.customers_id') }}
                            </th>
                            <th>
                                    Status Order
                                </th>
                                <th>
                                    Status Delivery
                                </th>
                            <th>
                                {{ trans('global.order.fields.memo') }}
                            </th>
                            <th>
                                Rekening
                            </th>
                            <th>
                                Total
                            </th>
                            <th>
                                {{ trans('global.order.fields.products') }}
                            </th>
                            <th>
                                &nbsp;
                            </th>
                        </tr>
                    </thead>
                    <tfoot align="left">
                        <tr><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th><th></th></tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
@parent
<script>
    $(function () {
    let searchParams = new URLSearchParams(window.location.search)
    let type = searchParams.get('type')
    if (type) {
        $("#type").val(type);
    }

    let customer = searchParams.get('customer')
    if (customer) {
        $("#customer").val(customer);
    }
    // date from unutk start tanggal 
    let from = searchParams.get('from')
    if (from) {
        $("#from").val(from);
    }

    // date to untuk batas tanggal 
    let to = searchParams.get('to')
    if (to) {
        $("#to").val(to);
    }
  
  let dtButtons = $.extend(true, [], $.fn.dataTable.defaults.buttons)

  let deleteButtonTrans = '{{ trans('global.datatables.delete') }}'
  let deleteButton = {
    text: deleteButtonTrans,
    url: "{{ route('admin.material.massDestroy') }}",
    className: 'btn-danger',
    action: function (e, dt, node, config) {
      var ids = $.map(dt.rows({ selected: true }).nodes(), function (entry) {
          return $(entry).data('entry-id')
      });

      if (ids.length === 0) {
        alert('{{ trans('global.datatables.zero_selected') }}')

        return
      }

      if (confirm('{{ trans('global.areYouSure') }}')) {
        $.ajax({
          headers: {'x-csrf-token': _token},
          method: 'POST',
          url: config.url,
          data: { ids: ids, _method: 'DELETE' }})
          .done(function () { location.reload() })
      }
    }
  }

  

  $.extend(true, $.fn.dataTable.defaults, {
    order: [[ 1, 'desc' ]],
    pageLength: 100,
  });
  dtButtons.push(deleteButton)
  $('.datatable-Material:not(.ajaxTable)').DataTable({ buttons: dtButtons })

  let dtOverrideGlobals = {
    buttons: dtButtons,
    processing: true,
    serverSide: true,
    retrieve: true,
    aaSorting: [],
    ajax: {
      url: "{{ route('admin.material.index') }}",
      dataType: "json",
      headers: {'x-csrf-token': _token},
      method: 'GET',
      data: {
        'type':  $("#type").val(),
        'customer':  $("#customer").val(),
        'from' :   $("#from").val(),
        'to' :  $("#to").val(),
      }
    },
    columns: [
        { data: 'placeholder', name: 'placeholder' },
        { data: 'DT_RowIndex', name: 'no', searchable: false },
        { data: 'code', name: 'code' },
        { data: 'register', name: 'register' },
        { data: 'name', name: 'name', searchable: false  },
        { data: 'status', name: 'status' },
        { data: 'status_delivery', name: 'status_delivery' },
        { data: 'memo', name: 'memo' },        
        { data: 'accpay', name: 'accpay',  searchable: false  },
        { data: 'amount', name: 'amount', searchable: false},
        { data: 'product', name: 'product',searchable: false },
        { data: 'actions', name: '{{ trans('global.actions') }}' }
    ],
    pageLength: 100,
    "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
    "footerCallback": function ( row, data, start, end, display ) {
            var api = this.api(), data;
 
            // converting to interger to find total
            var intVal = function ( i ) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '')*1 :
                    typeof i === 'number' ?
                        i : 0;
            };
 
            // computing column Total of the complete result 
            var Total = api
                .column( 9 )
                .data()
                .reduce( function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0 );
				
	    // Update footer by showing the total with the reference of the column index 
	    $( api.column( 8 ).footer() ).html('Total');
        $( api.column( 9 ).footer() ).html(Total.toLocaleString("en-GB"));
        },
  };

  $('.datatable-materials').DataTable(dtOverrideGlobals);
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e){
        $($.fn.dataTable.tables(true)).DataTable()
            .columns.adjust();
    });
    
})

</script>
@endsection
<script type="text/javascript">
    $(function(){
     $(".datepicker").datepicker({
         format: 'yyyy-mm-dd',
         autoclose: true,
         todayHighlight: true,
     });
    });
</script>