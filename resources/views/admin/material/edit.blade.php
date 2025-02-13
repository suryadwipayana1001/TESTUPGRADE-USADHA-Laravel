@extends('layouts.admin')
@section('content')

<div class="card">
    <div class="card-header">
        {{ trans('global.edit') }} Bahan
    </div>

    <div class="card-body">
        <form action="{{ route("admin.material.update", [$order->id]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{ csrf_field() }}
            @method('PUT')
            <div class="form-group {{ $errors->has('register') ? 'has-error' : '' }}">
                <label for="register">{{ trans('global.order.fields.register') }}*</label>
                <input type="date" id="register" name="register" class="form-control" value="{{ old('register', isset($order) ? $order->register : date('Y-m-d')) }}" required>
                @if($errors->has('register'))
                    <em class="invalid-feedback">
                        {{ $errors->first('register') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.order.fields.register_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('code') ? 'has-error' : '' }}">
                <label for="code">{{ trans('global.order.fields.code') }}*</label>
                <input type="text" id="code" name="code" class="form-control" value="{{ old('code', isset($order) ? $order->code : '') }}" required>
                @if($errors->has('code'))
                    <em class="invalid-feedback">
                        {{ $errors->first('code') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.order.fields.code_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('customers_id') ? 'has-error' : '' }}">
                <label for="customers_id">{{ trans('global.order.fields.customers_id') }}*</label>
                <select name="customers_id" class="form-control">
                    <option value="">-- choose customer --</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"{{ $order->customers_id == $customer->id ? ' selected' : '' }}>
                        {{ $customer->code }}-{{ $customer->name }} {{ $customer->last_name }}
                        </option>
                    @endforeach
                </select>
                @if($errors->has('customers_id'))
                    <em class="invalid-feedback">
                        {{ $errors->first('customers_id') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.order.fields.customers_id_helper') }}
                </p>
            </div>
            <div class="form-group {{ $errors->has('memo') ? 'has-error' : '' }}">
                <label for="memo">{{ trans('global.order.fields.memo') }}</label>
                <textarea id="memo" name="memo" class="form-control ">{{$order->memo}}</textarea>
                @if($errors->has('memo'))
                    <em class="invalid-feedback">
                        {{ $errors->first('memo') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.order.fields.memo_helper') }}
                </p>
            </div>

            <div class="form-group {{ $errors->has('accounts_id') ? 'has-error' : '' }}">
                <label for="accounts_id">{{ trans('global.order.fields.accounts_id') }}*</label>
                <select name="accounts_id" class="form-control">
                    <option value="">-- choose account --</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}"{{ $account->id ? ' selected' : '' }}>
                        {{ $account->code }}-{{ $account->name }} {{ $account->last_name }}
                        </option>
                    @endforeach
                </select>
                @if($errors->has('accounts_id'))
                    <em class="invalid-feedback">
                        {{ $errors->first('accounts_id') }}
                    </em>
                @endif
                <p class="helper-block">
                    {{ trans('global.order.fields.accounts_id_helper') }}
                </p>
            </div>

            <div class="card">
                <div class="card-header">
                    Products
                </div>

                <div class="card-body">
                    <table class="table" id="products_table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Sub Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->products as $index => $oldProduct)
                              <?php  
                                // var_dump($oldProduct);
                              $sub = $oldProduct->price * $oldProduct->pivot->quantity ?>
                              
                                <tr id="product{{$loop->index }}">
                                    <td>
                                        <select name="products[]" class="form-control product_list">
                                            <option value="">-- choose product --</option>
                                            @foreach ($products as $product)
                                                <option data-cogs="{{ $product->cogs }}" data-price="{{ $product->price }}" value="{{ $product->id }}"{{ $oldProduct->id == $product->id ? ' selected' : '' }}>
                                                    {{ $product->name }} (Rp. {{ number_format($product->price, 2) }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" name="cogs[]" value="{{ old('cogs.' . $index) ?? '0' }}" class="cogs_hidden">
                                    </td>
                                    <td>
                                        <input type="number" name="quantities[]" class="form-control qty_list" value="{{ $oldProduct->pivot->quantity ?? '0' }}" />
                                    </td>
                                    <td>
                                        <input type="number" name="prices[]" class="form-control price_list" value="{{ $oldProduct->price ?? '0' }}" />
                                    </td>
                                    <td>
                                    <input type="number" name="subs[]" class="form-control sub_list" id="subtotal" value="{{ $sub}}" readonly />
                                    </td>
                                </tr>
                            @endforeach
                            
                            <tr id="product{{ count($order->products) }}"></tr>
                        </tbody>
                        <tr>
                                    <td>
                                    </td>
                                    <td>
                                    </td>
                                    <td>
                                    Total
                                    </td>
                                    <td>
                                    <input type="number" name="total" class="form-control" id="total" value={{ $order->total }} readonly />
                                    </td>
                                </tr>
                    </table>

                    <div class="row">
                        <div class="col-md-12">
                            <button id="add_row" class="btn btn-default pull-left">+ Add Row</button>
                            <button id='delete_row' class="pull-right btn btn-danger">- Delete Row</button>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <input class="btn btn-danger" type="submit" value="{{ trans('global.save') }}">
            </div>
        </form>


    </div>
</div>
@endsection

@section('scripts')
<script>
  $(document).ready(function(){
    let row_number = {{ count($order->products) }};
    $("#add_row").click(function(e){
      e.preventDefault();
      let new_row_number = row_number - 1;
    //   $('#subtotal')=$("#subtotal").val();
    //   var total += parseInt(subtotal);
    //   $("#total").val(total);
      $('#product' + row_number).html($('#product' + new_row_number).html()).find('td:first-child');
      $('#products_table').append('<tr id="product' + (row_number + 1) + '"></tr>');
      let next_row_number = new_row_number+1;
      $('tr#product'+next_row_number+' input.cogs_hidden')
        .val(
           0
        );
        $('tr#product'+next_row_number+' input.qty_list')
        .val(
           0
        );
        $('tr#product'+next_row_number+' input.price_list')
        .val(
            0
        );
        $('tr#product'+next_row_number+' input.sub_list')
        .val(0);
      row_number++;
    });

    $("#delete_row").click(function(e){
      e.preventDefault();
      if(row_number > 1){
        $("#product" + (row_number - 1)).html('');
        row_number--;
      }
    });

    $(document).on("change", "select.product_list" , function() {
        let data_key = $(this).closest('tr').attr('id');
        let qty = $('tr#'+data_key+' input.qty_list').val();
        let sub = qty * $(this).find(':selected').data('price');
        //alert(data_key);
        $('tr#'+data_key+' input.cogs_hidden')
        .val(
            $(this).find(':selected').data('cogs')
        );
        $('tr#'+data_key+' input.price_list')
        .val(
            $(this).find(':selected').data('price')
        );
        $('tr#'+data_key+' input.sub_list')
        .val(sub);
        var sum = 0;
        $('.sub_list').each(function () {
            sum += Number($(this).val());
        });
        $("input[name='total']")
        .val(sum);
    });

    $(document).on("change", "input.qty_list" , function() {
        let data_key = $(this).closest('tr').attr('id');
        let price = $('tr#'+data_key+' input.price_list').val();
        let sub = $(this).val() * price;
        $('tr#'+data_key+' input.sub_list')
        .val(sub);
        var sum = 0;
        $('.sub_list').each(function () {
            sum += Number($(this).val());
        });
        $("input[name='total']")
        .val(sum);
    });

    $(document).on("change", "input.price_list" , function() {
        let data_key = $(this).closest('tr').attr('id');
        let qty = $('tr#'+data_key+' input.qty_list').val();
        let sub = $(this).val() * qty;
        $('tr#'+data_key+' input.sub_list')
        .val(sub);
        var sum = 0;
        $('.sub_list').each(function () {
            sum += Number($(this).val());
        });
        $("input[name='total']")
        .val(sum);
    });

    // $(document).ready(function() {
    //     $("#subtotal").keyup(function() {
    //         var subtotal  = $("#subtotal").val();
    //         var total += parseInt(subtotal);
    //         $("#total").val(total);
    //     });
    // });

  });
</script>
@endsection
