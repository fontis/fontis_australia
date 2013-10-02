<?php 

abstract class Doghouse_Australia_Eparcel_Parcel
{
	public $weight = 0; // [Kg]
	public $weightMax = 0; // [Kg]
	
	public $width = 0; // [cm]
	public $height = 0; // [cm]
	public $length = 0; // [cm]
	
	protected $_goodRecords = array();
	
	protected $isInsuranceRequired = false;
	
	public function isInsuranceRequired($isInsuranceRequired=null)
	{
		if (!is_null($isInsuranceRequired))
		{
			$this->isInsuranceRequired = (bool) $isInsuranceRequired;
		}
		
		return (bool) $this->isInsuranceRequired;
	}
	
	public function processArticleRecord(Doghouse_Australia_Eparcel_Record_Article $record)
	{
		$totalValue = $this->getTotalValue();
		
		$record->length					= $this->length;
		$record->width					= $this->width;
		$record->height					= $this->height;
		
		$record->weight					= $this->weight;
		
		$record->numberIdenticalItems 	= 1;
		$record->description			= "";
		$record->valueForCustoms 		= $totalValue;
		
		$record->calculateWeight();
		
		if( $this->isInsuranceRequired() )
		{
			$record->isInsuranceRequired = true;
			$record->insuranceAmount = $totalValue;
		}
		
		return $record;
	}
	
	protected function getTotalValue()
	{
		$totalValue = 0;
	    	
    	foreach( $this->getGoodRecords() as $_goodRecord )
    	{
    		$totalValue += $_goodRecord->totalValue;
    	}
    	
    	return $totalValue;
	}
	
	public function addGood(Doghouse_Australia_Eparcel_Record_Good $goodRecord)
	{
		$this->_goodRecords[] = $goodRecord;
		
		return true;
	}
	
	public function getGoodRecords()
	{
		return $this->_goodRecords;
	}
	
	abstract public function canAddGood(Doghouse_Australia_Eparcel_Record_Good $goodRecord);
}