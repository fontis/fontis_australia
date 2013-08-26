<?php 

class Doghouse_Australia_Eparcel_Record_Article extends Doghouse_Australia_Eparcel_Record
{
	public $type = 'A';
	
	/**
	 * The cubic weight is the parcel's volume in cubic metres multiplied by 250.
	 * @see http://auspost.com.au/personal/parcel-dimensions.html
	 * @var float
	 */
	public $weight;
	
	public $length;
	public $width;
	public $height;
	public $numberIdenticalItems = 1;
	public $description;
	public $isDangerousGoods = false;
	public $isInsuranceRequired = false;
	public $insuranceAmount;
	public $valueForCustoms;
	public $exportReason;
	public $exportClearanceNumber;
	
	public function calculateWeight()
	{
		/*
		 * Everything is already calculated.
		 * This method only exists for consistency with 
		 * Doghouse_Australia_Eparcel_Record_Article_CubicWeight
		 */
		return true;
	}
}