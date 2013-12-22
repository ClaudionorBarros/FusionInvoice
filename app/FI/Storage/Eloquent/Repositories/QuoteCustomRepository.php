<?php namespace FI\Storage\Eloquent\Repositories;

use FI\Storage\Eloquent\Models\QuoteCustom;

class QuoteCustomRepository implements \FI\Storage\Interfaces\QuoteCustomRepositoryInterface {

	public function save($input, $quoteId)
	{
		$record = (QuoteCustom::find($quoteId)) ?: new QuoteCustom;

		$record->quote_id = $quoteId;
		
		$record->fill($input);

		$record->save();
	}

}