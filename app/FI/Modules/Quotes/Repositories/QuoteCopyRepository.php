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

use Auth;
use Config;
use Event;

use FI\Classes\Date;

class QuoteCopyRepository {

	/**
	 * Client repository
	 * @var ClientRepository
	 */
	protected $client;

	/**
	 * Quote repository
	 * @var QuoteRepository
	 */
	protected $quote;

	/**
	 * Invoice group repository
	 * @var InvoiceGroupRepository
	 */
	protected $invoiceGroup;

	/**
	 * Invoice item repository
	 * @var InvoiceItemRepository
	 */
	protected $quoteItem;

	/**
	 * Quote tax rate repository
	 * @var QuoteTaxRateRepository
	 */
	protected $quoteTaxRate;

	/**
	 * Dependency injection
	 * @param ClientRepository $client
	 * @param QuoteRepository $quote
	 * @param InvoiceGroupRepository $invoiceGroup
	 * @param InvoiceItemRepository $quoteItem
	 */
	public function __construct($client, $quote, $invoiceGroup, $quoteItem, $quoteTaxRate)
	{
		$this->client       = $client;
		$this->quote        = $quote;
		$this->invoiceGroup = $invoiceGroup;
		$this->quoteItem    = $quoteItem;
		$this->quoteTaxRate = $quoteTaxRate;
	}

	/**
	 * Copies a quote
	 * @param  int $fromQuoteId
	 * @param  string $clientName
	 * @param  date $createdAt
	 * @param  date $expiresAt
	 * @param  int $invoiceGroupId
	 * @param  int $userId
	 * @return int
	 */
	public function copyQuote($fromQuoteId, $clientName, $createdAt, $expiresAt, $invoiceGroupId, $userId)
	{
		$clientId = $this->client->findIdByName($clientName);

		if (!$clientId)
		{
			$clientId = $this->client->create(array('name' => $clientName));
		}

		$toQuoteId = $this->quote->create(
			array(
				'client_id'        => $clientId,
				'created_at'       => $createdAt,
				'expires_at'       => $expiresAt,
				'invoice_group_id' => $invoiceGroupId,
				'number'           => $this->invoiceGroup->generateNumber($invoiceGroupId),
				'user_id'          => $userId,
				'quote_status_id'  => 1,
				'url_key'          => str_random(32)
			)
		);		

		$items = $this->quoteItem->findByQuoteId($fromQuoteId);

		foreach ($items as $item)
		{
			$this->quoteItem->create(
				array(
					'quote_id'      => $toQuoteId,
					'name'          => $item->name,
					'description'   => $item->description,
					'quantity'      => $item->quantity,
					'price'         => $item->price,
					'tax_rate_id'   => $item->tax_rate_id,
					'display_order' => $item->display_order
				)
			);
		}

		$quoteTaxRates = $this->quoteTaxRate->findByQuoteId($fromQuoteId);

		foreach ($quoteTaxRates as $quoteTaxRate)
		{
			$this->quoteTaxRate->create(
				array(
					'quote_id'         => $toQuoteId,
					'tax_rate_id'      => $quoteTaxRate->tax_rate_id,
					'include_item_tax' => $quoteTaxRate->include_item_tax,
					'tax_total'        => $quoteTaxRate->tax_total
				)
			);
		}

		Event::fire('quote.modified', $toQuoteId);

		return $toQuoteId;
	}
	
}