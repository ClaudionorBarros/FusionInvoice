<?php

/**
 * This file is part of FusionInvoice.
 *
 * (c) FusionInvoice, LLC <jessedterry@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FI\Modules\Quotes\Validators;

class QuoteTaxRateValidator extends \FI\Validators\Validator {

	/**
	 * The validation create rules
	 * @var array
	 */
	static $rules = array(
		'tax_rate_id'         => 'required|numeric|min:1',
		'include_item_tax'    => 'required'
	);

}