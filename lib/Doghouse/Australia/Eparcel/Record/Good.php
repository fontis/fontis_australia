<?php 

class Doghouse_Australia_Eparcel_Record_Good extends Doghouse_Australia_Eparcel_Record
{
	public $type = 'G';
	
	public $originCountryCode;
	public $hsTariffCode;
	public $description;
	public $productType;
	public $productClassification;
	public $quantity;
	public $weight = 0;
	public $unitValue;
	public $totalValue;
}