<?php 

class Doghouse_Australia_Eparcel_Parcel_Carton extends Doghouse_Australia_Eparcel_Parcel
{
	public function canAddGood(Doghouse_Australia_Eparcel_Record_Good $goodRecord)
	{
		/**
		 * Check that adding this good to parcel will not make
		 * total parcel weight go over parcel's maxWeight
		 */
		if ( ($goodRecord->weight * $goodRecord->quantity) + $this->weight >= $this->weightMax )
		{
			return false;
		}
		
		return true;
	}
	
	public function addGood(Doghouse_Australia_Eparcel_Record_Good $goodRecord)
	{
		$this->weight += ( $goodRecord->weight * $goodRecord->quantity );
		
		return parent::addGood($goodRecord);
	}
}
