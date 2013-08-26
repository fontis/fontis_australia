<?php 

abstract class Doghouse_Australia_Eparcel_Record
{
	public $type;
	
	protected $isAddedToEparcel = false; 
	
	public function isAddedToEparcel($isAddedToEparcel=null)
	{
		if ( !is_null($isAddedToEparcel) )
		{
			$this->isAddedToEparcel = (bool) $isAddedToEparcel;
		}
		
		return (bool) $this->isAddedToEparcel;
	}
	
	public function getValues()
	{
		$values = array_values(
			get_object_vars($this)
		);
		
		foreach( $values as &$value )
		{			
			if ( $value === true ) 		$value = 'Y';
			if ( $value === false ) 	$value = 'N';
		}
		
		return $values;
	}
}