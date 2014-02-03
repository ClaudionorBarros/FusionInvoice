<script type="text/javascript">

	$(function() {

		$('#mail-quote').modal('show');
        
		$('#btn-mail-quote-confirm').click(function() {

			$('#btn-mail-quote-confirm').attr('disabled', 'disabled');
			$('#form-status-placeholder').html('<div class="alert alert-info">{{ trans('fi.sending') }}</div>');

			$.post("{{ route('quotes.ajax.mailQuote') }}", {
				quote_id: {{ $quoteId }},
				to: $('#to').val(),
				cc: $('#cc').val(),
				subject: $('#subject').val()
			}).done(function(response) {
				$('#form-status-placeholder').html('<div class="alert alert-success">{{ trans('fi.sent') }}</div>');
				setTimeout('window.location="{{ $redirectTo }}";', 1000);
			}).fail(function(response) {
				if (response.status == 400) {
					showErrors($.parseJSON(response.responseText).errors, '#form-status-placeholder');
				} else {
					alert("{{ trans('fi.unknown_error') }}");
				}
				$('#btn-mail-quote-confirm').removeAttr('disabled');
			});
		});
	});
	
</script>

<div id="mail-quote" class="modal hide">
	<form class="form-horizontal">
		<div class="modal-header">
			<a data-dismiss="modal" class="close">x</a>
			<h3>{{ trans('fi.email_quote') }}</h3>
		</div>
		<div class="modal-body">

			<div id="form-status-placeholder"></div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.to') }}: </label>
				<div class="controls">
					{{ Form::text('to', $to, array('id' => 'to')) }}
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.cc') }}: </label>
				<div class="controls">
					{{ Form::text('cc', $cc, array('id' => 'cc')) }}
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">{{ trans('fi.subject') }}: </label>
				<div class="controls">
					{{ Form::text('subject', $subject, array('id' => 'subject')) }}
				</div>
			</div>
			
		</div>

		<div class="modal-footer">
            <button class="btn btn-danger" type="button" data-dismiss="modal"><i class="icon-white icon-remove"></i> {{ trans('fi.cancel') }}</button>
			<button class="btn btn-primary" id="btn-mail-quote-confirm" type="button"><i class="icon-white icon-ok"></i> {{ trans('fi.send') }}</button>
		</div>

	</form>

</div>