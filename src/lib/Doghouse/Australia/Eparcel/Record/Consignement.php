<?php 

class Doghouse_Australia_Eparcel_Record_Consignement extends Doghouse_Australia_Eparcel_Record
{
	public $type = 'C';
	public $consignementId = '';
	
	public $postChargeToAccount;
	public $chargeCode;
	public $merchantConsigneeCode;
	public $consigneeName;
	public $consigneeBusinessName;
	public $consigneeAddressLine1;
	public $consigneeAddressLine2;
	public $consigneeAddressLine3;
	public $consigneeAddressLine4;
	public $consigneeSuburb;
	public $consigneeStateCode;
	public $consigneePostcode;
	public $consigneeCountryCode;
	public $consigneePhoneNumber;
	public $isPhonePrintRequired = false;
	public $consigneeFaxNumber;
	public $deliveryInstructions;
	public $isSignatureRequired;
	public $isPartDelivery = false;
	public $comments;
	public $addToAddressBook = false;
	public $cashToCollectAmount;
	public $ref;
	public $isRefPrintRequired = false;
	public $ref2;
	public $isRef2PrintRequired = false;
	public $chargebackAccount;
	public $isRecurringConsignement = false;
}