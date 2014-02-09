<script type="text/javascript">
	$(function()
	{
		$('#enter-payment').modal('show');

		$('#enter-payment').on('shown', function() {
			$('#payment_amount').focus();
		});

		$('.datepicker').datepicker({autoclose: true, format: '{{ Config::get('fi.datepickerFormat') }}' });

		$('#btn-modal-payment-submit').click(function()
		{
			var custom_fields = {};

			$('.custom-form-field').each(function() {
				custom_fields[$(this).data('field-name')] = $(this).val();
			});

			$.post("{{ route('payments.ajax.store') }}", {
				invoice_id: $('#invoice_id').val(),
				amount: $('#payment_amount').val(),
				payment_method_id: $('#payment_method_id').val(),
				paid_at: $('#payment_date').val(),
				note: $('#payment_note').val(),
				custom: JSON.stringify(custom_fields)
			}).done(function(response) {
				window.location = "{{ $redirectTo }}";
			}).fail(function(response) {
				if (response.status == 400) {
					showErrors($.parseJSON(response.responseText).errors, '#form-status-placeholder');
				} else {
					alert("{{ trans('fi.unknown_error') }}");
				}
			});
		});
	});
</script>

<div id="enter-payment" class="modal hide">
	<div class="modal-header">
		<a data-dismiss="modal" class="close">×</a>
		<h3>{{ trans('fi.enter_payment') }}</h3>
	</div>
	<div class="modal-body">

		<div id="form-status-placeholder"></div>

		<form class="form-horizontal">
			
			<input type="hidden" name="invoice_id" id="invoice_id" value="{{ $invoice_id }}">

			<div class="control-group">

				<label class="control-label">{{ trans('fi.amount') }}: </label>
				<div class="controls">
					<input type="text" name="payment_amount" id="payment_amount" value="{{ $balance }}">
				</div>

			</div>

			<div class="control-group">

				<label class="control-label">{{ trans('fi.payment_date') }}: </label>
				<div class="controls input-append date datepicker">
					<input size="16" type="text" name="payment_date" id="payment_date" value="{{ $date }}" readonly>
					<span class="add-on"><i class="icon-th"></i></span>
				</div>

			</div>

			<div class="control-group">

				<label class="control-label">{{ trans('fi.payment_method') }}: </label>
				<div class="controls">
					<select name="payment_method_id" id="payment_method_id">
						<option value=""></option>
						@foreach ($paymentMethods as $paymentMethod)
						<option value="{{ $paymentMethod->id }}">{{ $paymentMethod->name }}</option>
						@endforeach
					</select>
				</div>
			</div>

			<div class="control-group">

				<label class="control-label">{{ trans('fi.note') }}: </label>
				<div class="controls">
					<textarea name="payment_note" id="payment_note"></textarea>
				</div>

			</div>

			@include('custom_fields._custom_fields')

		</form>
	</div>

	<div class="modal-footer">
		<button class="btn btn-danger" type="button" data-dismiss="modal"><i class="icon-white icon-remove"></i> {{ trans('fi.cancel') }}</button>
		<button class="btn btn-primary" id="btn-modal-payment-submit" type="button"><i class="icon-white icon-ok"></i> {{ trans('fi.submit') }}</button>
	</div>

</div>