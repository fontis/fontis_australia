<?php 

class Doghouse_Australia_Eparcel_Record_Article_CubicWeight extends Doghouse_Australia_Eparcel_Record_Article
{
	/**
	 * The cubic weight is the parcel's volume in cubic metres multiplied by 250.
	 * @see http://auspost.com.au/personal/parcel-dimensions.html
	 * @var float
	 */
	public $weight;
	
	public function calculateWeight()
	{
		/**
		 * Convert [cm] to [m]
		 */
		$l = (float) $this->length / 100;
		$w = (float) $this->width / 100;
		$h = (float) $this->height / 100;
		
		$this->weight = round( ($l * $w * $h) * 250, 2);
		
		return true;
	}
}