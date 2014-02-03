<script type="text/javascript">

	$(function() {

		$('.datepicker').datepicker({autoclose: true, format: '{{ Config::get('fi.datepickerFormat') }}' });
		
		$('#create-invoice').modal('show');

		$('#create-invoice').on('shown', function() {
			$("#client_name").focus();
		});

		$('.client-lookup').keypress(function()	{
			var self = $(this);

			$.post("{{ route('clients.ajax.nameLookup') }}", {

				query: self.val()

			}, function(data) {
				
				self.typeahead().data('typeahead').source = eval('(' + data + ')');

			});
		});

		$('input[name=recurring]:radio').change(function () {
			if ($(this).val() == 1) {
				$('#div-recurring-options').show();
			}
			else {
				$('#div-recurring-options').hide();
			}
		});

		$('#invoice_create_confirm').click(function() {

			$.post("{{ route('invoices.store') }}", { 
				client_name: $('#client_name').val(), 
				created_at: $('#created_at').val(),
				invoice_group_id: $('#invoice_group_id').val(),
				recurring: $("input:radio[name=recurring]:checked").val(),
				recurring_frequency: $('#recurring_frequency').val(),
				recurring_period: $('#recurring_period').val()
			}).done(function(response) {
				window.location = "{{ url('invoices') }}/" + response.id;
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

<div id="create-invoice" class="modal hide">
	<form class="form-horizontal">
		<div class="modal-header">
			<a data-dismiss="modal" class="close">x</a>
			<h3>{{ trans('fi.create_invoice') }}</h3>
		</div>
		<div class="modal-body">

			<div id="form-status-placeholder"></div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.client') }}: </label>
				<div class="controls">
					{{ Form::text('client_name', null, array('id' => 'client_name', 'class' => 'client-lookup', 'style' => 'margin: 0 auto;', 'autocomplete' => 'off')) }}
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.invoice_date') }}: </label>
				<div class="controls input-append date datepicker">
					{{ Form::text('created_at', date(Config::get('fi.dateFormat')), array('id' => 'created_at', 'readonly' => 'readonly')) }}
					<span class="add-on"><i class="icon-th"></i></span>
				</div>
			</div>
			
			<div class="control-group">
				<label class="control-label">{{ trans('fi.invoice_group') }}: </label>
				<div class="controls">
					{{ Form::select('invoice_group_id', $invoiceGroups, Config::get('fi.invoiceGroup'), array('id' => 'invoice_group_id')) }}
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.frequency') }}</label>
				<div class="controls">
					<label class="radio">
						{{ Form::radio('recurring', '0', true) }}
						{{ trans('fi.one_time') }}
					</label>
					<label class="radio">
						{{ Form::radio('recurring', '1') }}
						{{ trans('fi.recurring') }}
					</label>
				</div>
			</div>

			<div class="control-group" id="div-recurring-options" style="display: none;">
				<label class="control-label">{{ trans('fi.every') }}</label>
				<div class="controls">
					{{ Form::text('recurring_frequency', '1', array('id' => 'recurring_frequency', 'class' => 'input-mini')) }}
					{{ Form::select('recurring_period', $frequencies, 3, array('id' => 'recurring_period')) }}
				</div>
			</div>

		</div>

		<div class="modal-footer">
			<button class="btn btn-danger" type="button" data-dismiss="modal"><i class="icon-white icon-remove"></i> {{ trans('fi.cancel') }}</button>
			<button class="btn btn-primary" id="invoice_create_confirm" type="button"><i class="icon-white icon-ok"></i> {{ trans('fi.submit') }}</button>
		</div>

	</form>

</div>