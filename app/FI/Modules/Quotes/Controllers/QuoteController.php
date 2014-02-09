<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\Quotes\Controllers;

use App;
use Auth;
use Config;
use Event;
use Input;
use Mail;
use Redirect;
use Response;
use View;

use FI\Classes\Date;
use FI\Classes\NumberFormatter;
use FI\Statuses\QuoteStatuses;

class QuoteController extends \BaseController {

	/**
	 * Invoice group repository
	 * @var InvoiceGroupRepository
	 */
	protected $invoiceGroup;

	/**
	 * Quote repository
	 * @var QuoteRepository
	 */
	protected $quote;

	/**
	 * Quote item repository
	 * @var QuoteItemRepository
	 */
	protected $quoteItem;

	/**
	 * Quote tax rate repository
	 * @var QuoteTaxRateRepository
	 */
	protected $quoteTaxRate;

	/**
	 * Quote validator
	 * @var QuoteValidator
	 */
	protected $validator;
	
	/**
	 * Dependency injection
	 * @param InvoiceGroupRepository $invoiceGroup
	 * @param QuoteItemRepository $quoteItem
	 * @param QuoteRepository $quote
	 * @param QuoteTaxRateRepository $quoteTaxRate
	 * @param QuoteValidator $validator
	 */
	public function __construct($invoiceGroup, $quoteItem, $quote, $quoteTaxRate, $validator)
	{
		$this->invoiceGroup = $invoiceGroup;
		$this->quote        = $quote;
		$this->quoteItem    = $quoteItem;
		$this->quoteTaxRate = $quoteTaxRate;
		$this->validator    = $validator;
	}

	/**
	 * Display paginated list
	 * @param  string $status
	 * @return View
	 */
	public function index($status = 'all')
	{
		$quotes   = $this->quote->getPagedByStatus(Input::get('page'), null, $status, Input::get('filter'));
		$statuses = QuoteStatuses::statuses();

		return View::make('quotes.index')
		->with('quotes', $quotes)
		->with('status', $status)
		->with('statuses', $statuses)
		->with('mailConfigured', (Config::get('fi.mailDriver')) ? true : false)
		->with('filterRoute', route('quotes.index', array($status)));
	}

	/**
	 * Accept post data to create quote
	 * @return json
	 */
	public function store()
	{
		$client = \App::make('ClientRepository');

		if (!$this->validator->validate(Input::all(), 'createRules'))
		{
			return Response::json(array(
				'success' => false,
				'errors'  => $this->validator->errors()->toArray()
			), 400);
		}

		$clientId = $client->findIdByName(Input::get('client_name'));

		if (!$clientId)
		{
			$clientId = $client->create(array('name' => Input::get('client_name')));
		}

		$input = array(
			'client_id'        => $clientId,
			'created_at'       => Date::unformat(Input::get('created_at')),
			'expires_at'       => Date::incrementDateByDays(Date::unformat(Input::get('created_at')), Config::get('fi.quotesExpireAfter')),
			'invoice_group_id' => Input::get('invoice_group_id'),
			'number'           => $this->invoiceGroup->generateNumber(Input::get('invoice_group_id')),
			'user_id'          => Auth::user()->id,
			'quote_status_id'  => 1,
			'url_key'          => str_random(32),
			'footer'           => Config::get('fi.quoteFooter')
			);

		$quoteId = $this->quote->create($input);

		return Response::json(array('success' => true, 'id' => $quoteId), 200);
	}

	/**
	 * Accept post data to update quote
	 * @param int $id
	 * @return json
	 */
	public function update($id)
	{
		if (!$this->validator->validate(Input::all(), 'updateRules'))
		{
			return Response::json(array(
				'success' => false,
				'errors' => $this->validator->errors()->toArray()
			), 400);
		}

		$itemValidator = App::make('FI\Validators\ItemValidator');

		if (!$itemValidator->validateMulti(json_decode(Input::get('items'))))
		{
			return Response::json(array(
				'success' => false,
				'errors' => $itemValidator->errors()->toArray()
			), 400);
		}

		$input = Input::all();

		$custom = (array) json_decode($input['custom']);
		unset($input['custom']);

		$quote = array(
			'number'          => $input['number'],
			'created_at'      => Date::unformat($input['created_at']),
			'expires_at'      => Date::unformat($input['expires_at']),
			'quote_status_id' => $input['quote_status_id'],
			'footer'          => $input['footer']
			);

		$this->quote->update($quote, $id);
		App::make('QuoteCustomRepository')->save($custom, $id);

		$items = json_decode(Input::get('items'));

		foreach ($items as $item)
		{
			if ($item->item_name)
			{
				$itemRecord = array(
					'quote_id'      => $item->quote_id,
					'name'          => $item->item_name,
					'description'   => $item->item_description,
					'quantity'      => NumberFormatter::unformat($item->item_quantity),
					'price'         => NumberFormatter::unformat($item->item_price),
					'tax_rate_id'   => $item->item_tax_rate_id,
					'display_order' => $item->item_order
					);

				if (!$item->item_id)
				{
					$itemId = $this->quoteItem->create($itemRecord);
				}
				else
				{
					$this->quoteItem->update($itemRecord, $item->item_id);
				}

				if (isset($item->save_item_as_lookup) and $item->save_item_as_lookup)
				{
					$itemLookup = \App::make('ItemLookupRepository');

					$itemLookupRecord = array(
						'name'        => $item->item_name,
						'description' => $item->item_description,
						'price'       => NumberFormatter::unformat($item->item_price)
						);

					$itemLookup->create($itemLookupRecord);
				}
			}
		}

		Event::fire('quote.modified', $id);

		return Response::json(array('success' => true), 200);
	}

	/**
	 * Display the quote
	 * @param  int $id [description]
	 * @return View
	 */
	public function show($id)
	{
		$quote         = $this->quote->find($id);
		$statuses      = QuoteStatuses::lists();
		$taxRates      = App::make('TaxRateRepository')->lists();
		$quoteTaxRates = $this->quoteTaxRate->findByQuoteId($id);

		return View::make('quotes.show')
		->with('quote', $quote)
		->with('statuses', $statuses)
		->with('taxRates', $taxRates)
		->with('quoteTaxRates', $quoteTaxRates)
		->with('customFields', App::make('CustomFieldRepository')->getByTable('quotes'))
		->with('mailConfigured', (Config::get('fi.mailDriver')) ? true : false);;
	}

	/**
	 * Displays the quote in preview format
	 * @param  int $id
	 * @return View
	 */
	public function preview($id)
	{
		$quote = $this->quote->find($id);

		return View::make('templates.quotes.' . str_replace('.blade.php', '', Config::get('fi.quoteTemplate')))
		->with('quote', $quote);
	}

	/**
	 * Delete an item from a quote
	 * @param  int $quoteId
	 * @param  int $itemId
	 * @return Redirect
	 */
	public function deleteItem($quoteId, $itemId)
	{
		$this->quoteItem->delete($itemId);

		return Redirect::route('quotes.show', array($quoteId));
	}

	/**
	 * Displays create quote modal from ajax request
	 * @return View
	 */
	public function modalCreate()
	{
		return View::make('quotes._modal_create')
		->with('invoiceGroups', $this->invoiceGroup->lists());
	}

	/**
	 * Displays modal to convert quote to invoice
	 * @return view
	 */
	public function modalQuoteToInvoice()
	{
		return View::make('quotes._modal_quote_to_invoice')
		->with('quote_id', Input::get('quote_id'))
		->with('client_id', Input::get('client_id'))
		->with('invoiceGroups', $this->invoiceGroup->lists())
		->with('user_id', Auth::user()->id)
		->with('created_at', Date::format());
	}

	/**
	 * Displays modal to copy quote
	 * @return View
	 */
	public function modalCopyQuote()
	{
		$quote = $this->quote->find(Input::get('quote_id'));

		return View::make('quotes._modal_copy_quote')
		->with('quote', $quote)
		->with('invoiceGroups', $this->invoiceGroup->lists())
		->with('created_at', Date::format())
		->with('user_id', Auth::user()->id);
	}

	/**
	 * Attempt to copy a quote
	 * @return Redirect
	 */
	public function copyQuote()
	{
		if (!$this->validator->validate(Input::all(), 'createRules'))
		{
			return Response::json(array(
				'success' => false,
				'errors'  => $this->validator->errors()->toArray()
			), 400);
		}

		$quoteCopy = App::make('QuoteCopyRepository');

		$quoteId = $quoteCopy->copyQuote(Input::get('quote_id'), Input::get('client_name'), Date::unformat(Input::get('created_at')), Date::incrementDateByDays(Date::unformat(Input::get('created_at')), Config::get('fi.quotesExpireAfter')), Input::get('invoice_group_id'), Auth::user()->id);

		return Response::json(array('success' => true, 'id' => $quoteId), 200);
	}

	/**
	 * Attempt to save quote to invoice
	 * @return view
	 */
	public function quoteToInvoice()
	{
		$input = Input::all();

		if (!$this->validator->validate($input, 'toInvoiceRules'))
		{
			return Response::json(array(
				'success' => false,
				'errors' => $this->validator->errors()->toArray()
			), 400);
		}

		$invoice        = App::make('InvoiceRepository');
		$invoiceItem    = App::make('InvoiceItemRepository');
		$invoiceTaxRate = App::make('InvoiceTaxRateRepository');

		$record = array(
			'client_id'         => $input['client_id'],
			'created_at'        => Date::unformat($input['created_at']),
			'due_at'            => Date::incrementDateByDays(Date::unformat($input['created_at']), Config::get('fi.invoicesDueAfter')),
			'invoice_group_id'  => $input['invoice_group_id'],
			'number'            => $this->invoiceGroup->generateNumber($input['invoice_group_id']),
			'user_id'           => Auth::user()->id,
			'invoice_status_id' => 1,
			'url_key'           => str_random(32)
			);

		$invoiceId = $invoice->create($record);

		$items = $this->quoteItem->findByQuoteId($input['quote_id']);

		foreach ($items as $item)
		{
			$itemRecord = array(
				'invoice_id'    => $invoiceId,
				'name'          => $item->name,
				'description'   => $item->description,
				'quantity'      => $item->quantity,
				'price'         => $item->price,
				'tax_rate_id'   => $item->tax_rate_id,
				'display_order' => $item->display_order
				);

			$itemId = $invoiceItem->create($itemRecord);
		}

		$quoteTaxRates = $this->quoteTaxRate->findByQuoteId($input['quote_id']);

		foreach ($quoteTaxRates as $quoteTaxRate)
		{
			$invoiceTaxRate->create(
				array(
					'invoice_id'       => $invoiceId,
					'tax_rate_id'      => $quoteTaxRate->tax_rate_id,
					'include_item_tax' => $quoteTaxRate->include_item_tax,
					'tax_total'        => $quoteTaxRate->tax_total
					)
				);
		}

		return Response::json(array('success' => true, 'redirectTo' => route('invoices.show', array('invoice' => $invoiceId))), 200);
	}

	/**
	 * Displays modal to add quote taxes from ajax request
	 * @return View
	 */
	public function modalAddQuoteTax()
	{
		$taxRates = App::make('TaxRateRepository')->lists();

		return View::make('quotes._modal_add_quote_tax')
		->with('quote_id', Input::get('quote_id'))
		->with('taxRates', $taxRates)
		->with('includeItemTax', array('0' => trans('fi.apply_before_item_tax'), '1' => trans('fi.apply_after_item_tax')));
	}

	/**
	 * Saves quote tax from ajax request
	 */
	public function saveQuoteTax()
	{
		$validator = App::make('QuoteTaxRateValidator');

		if (!$validator->validate(Input::all()))
		{
			return Response::json(array(
				'success' => false,
				'errors'  => $validator->errors()->toArray()
			), 400);
		}

		$this->quoteTaxRate->create(
			array(
				'quote_id'         => Input::get('quote_id'), 
				'tax_rate_id'      => Input::get('tax_rate_id'), 
				'include_item_tax' => Input::get('include_item_tax')
			)
		);

		return Response::json(array('success' => true), 200);
	}

	/**
	 * Deletes quote tax
	 * @param  int $quoteId
	 * @param  int $quoteTaxRateId
	 * @return Redirect
	 */
	public function deleteQuoteTax($quoteId, $quoteTaxRateId)
	{
		$this->quoteTaxRate->delete($quoteTaxRateId);

		return Redirect::route('quotes.show', array($quoteId));
	}

	/**
	 * Deletes a quote
	 * @param  int $quoteId
	 * @return Redirect
	 */
	public function delete($quoteId)
	{
		$this->quote->delete($quoteId);

		return Redirect::route('quotes.index');
	}

	/**
	 * Display the modal to send mail
	 * @return View
	 */
	public function modalMailQuote()
	{
		$quote = $this->quote->find(Input::get('quote_id'));

		return View::make('quotes._modal_mail')
		->with('quoteId', $quote->id)
		->with('redirectTo', Input::get('redirectTo'))
		->with('to', $quote->client->email)
		->with('cc', \Config::get('fi.mailCcDefault'))
		->with('subject', trans('fi.quote') . ' #' . $quote->number);
	}

	/**
	 * Attempt to send the mail
	 * @return json
	 */
	public function mailQuote()
	{
		$quote = $this->quote->find(Input::get('quote_id'));

		$mailValidator = App::make('MailValidator');

		if (!$mailValidator->validate(Input::all()))
		{
			return Response::json(array(
				'success' => false,
				'errors'  => $mailValidator->errors()->toArray()
			), 400);
		}

		try
		{
			Mail::send('templates.emails.quote', array('quote' => $quote), function($message) use ($quote)
			{
				$message->from($quote->user->email)
				->to(Input::get('to'), $quote->client->name)
				->subject(Input::get('subject'));
			});
		}
		catch (\Swift_TransportException $e)
		{
			return Response::json(array(
				'success' => false,
				'errors'  => array(array($e->getMessage()))
			), 400);
		}
	}
	
}