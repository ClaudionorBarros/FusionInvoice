<script type="text/javascript">
	$(function()
	{
		$('.datepicker').datepicker({autoclose: true, format: '{{ Config::get('fi.datepickerFormat') }}' });
		
		// Display the create quote modal
		$('#quote-to-invoice').modal('show');
		
		// Creates the invoice
		$('#btn-quote-to-invoice-confirm').click(function()
		{
			$.post("{{ route('quotes.ajax.quoteToInvoice') }}", { 
				quote_id: {{ $quote_id }},
				client_id: {{ $client_id }},
				created_at: $('#created_at').val(),
				invoice_group_id: $('#invoice_group_id').val(),
				user_id: {{ $user_id }}
			}).done(function(response) {
				window.location = response.redirectTo;
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

<div id="quote-to-invoice" class="modal hide">
	<form class="form-horizontal">
		<div class="modal-header">
			<a data-dismiss="modal" class="close">x</a>
			<h3>{{ trans('fi.quote_to_invoice') }}</h3>
		</div>
		<div class="modal-body">

			<div id="form-status-placeholder"></div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.invoice_date') }}: </label>
				<div class="controls input-append date datepicker">
					{{ Form::text('created_at', $created_at, array('id' => 'created_at', 'readonly' => 'readonly')) }}
					<span class="add-on"><i class="icon-th"></i></span>
				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label">{{ trans('fi.invoice_group') }}: </label>
				<div class="controls">
					{{ Form::select('invoice_group_id', $invoiceGroups, Config::get('fi.invoiceGroup'), array('id' => 'invoice_group_id')) }}
				</div>
			</div>

		</div>

		<div class="modal-footer">
			<button class="btn btn-danger" type="button" data-dismiss="modal"><i class="icon-white icon-remove"></i> {{ trans('fi.cancel') }}</button>
			<button class="btn btn-primary" id="btn-quote-to-invoice-confirm" type="button"><i class="icon-white icon-ok"></i> {{ trans('fi.submit') }}</button>
		</div>

	</form>

</div>