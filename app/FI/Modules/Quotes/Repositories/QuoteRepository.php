<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\Quotes\Repositories;

use Event;

use FI\Modules\Quotes\Models\Quote;

class QuoteRepository {

	/**
	 * Get a list of all records
	 * @return Quote
	 */
	public function all()
	{
		return Quote::all();
	}

	/**
	 * Get a paged list of records
	 * @param  int $page
	 * @param  int  $numPerPage
	 * @param  string  $status
	 * @param  string  $filter
	 * @return Quote
	 */
	public function getPagedByStatus($page = 1, $numPerPage = null, $status = 'all', $filter = null)
	{
		\DB::getPaginator()->setCurrentPage($page);

		$quote = Quote::with(array('amount', 'client'))->orderBy('created_at', 'DESC');

		switch ($status)
		{
			case 'draft':
				$quote->draft();
				break;
			case 'sent':
				$quote->sent();
				break;
			case 'viewed':
				$quote->viewed();
				break;
			case 'approved':
				$quote->approved();
				break;
			case 'rejected':
				$quote->rejected();
				break;
			case 'canceled':
				$quote->canceled();
				break;
		}

		if ($filter)
		{
			$quote->keywords($filter);
		}

		return $quote->paginate($numPerPage ?: \Config::get('defaultNumPerPage'));
	}

	/**
	 * Get a limited list of all records
	 * @param  int $limit
	 * @return Quote
	 */
	public function getRecent($limit)
	{
		return Quote::with(array('amount', 'client'))->orderBy('created_at', 'DESC')->limit($limit)->get();
	}

	/**
	 * Get a single record
	 * @param  int $id
	 * @return Quote
	 */
	public function find($id)
	{
		return Quote::with(array('items.amount', 'custom'))->find($id);
	}

	/**
	 * Get a record by url key
	 * @param  string $urlKey
	 * @return Quote
	 */
	public function findByUrlKey($urlKey)
	{
		return Quote::where('url_key', $urlKey)->first();
	}

	/**
	 * Create a record
	 * @param  array $input
	 * @return int
	 */
	public function create($input)
	{
		$id = Quote::create($input)->id;

		Event::fire('quote.created', array($id, $input['invoice_group_id']));

		return $id;
	}
	
	/**
	 * Update a record
	 * @param  array $input
	 * @param  int $id
	 * @return void
	 */
	public function update($input, $id)
	{
		$quote = Quote::find($id);

		$quote->fill($input);

		$quote->save();
	}

	/**
	 * Delete a record
	 * @param  int $id
	 * @return void
	 */
	public function delete($id)
	{
		Quote::destroy($id);

		\Event::fire('quote.deleted', array($id));
	}

	/**
	 * Mark quote as viewed
	 * @param  int $id
	 * @return void
	 */
	public function markAsViewed($id)
	{
		Quote::find($id)->fill(array('quote_status_id' => 3))->save();
	}

}