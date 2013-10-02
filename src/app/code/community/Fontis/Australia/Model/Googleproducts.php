<?php
/**
 * Fontis Australia Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Jeremy Champion
 * @copyright  Copyright (c) 2011 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping.com data model
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Googleproducts {

    public $available_fields = array(
        'g:id',
        'title',
        'link',
        'g:price',
        'description',
        'g:condition',
        'g:gtin',
        'g:brand',
        'g:mpn',
        'g:image_link',
        'g:product_type',
        'g:quantity',
        'g:availability',
        //'g:shipping',         // TODO: I need to be handled!
        'g:feature',
        'g:online_only',
        'g:manufacturer',
        'g:expiration_date',
        'g:shipping_weight',
        'g:product_review_average',
        'g:product_review_count',
        'g:genre',
        'g:featured_product',
        'g:color',
        'g:size',
        'g:year',
        'g:author',
        'g:edition',
    );

}